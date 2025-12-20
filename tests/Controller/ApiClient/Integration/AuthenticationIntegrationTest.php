<?php

namespace App\Tests\Controller\ApiClient\Integration;

use App\Tests\Controller\ApiClient\ApiClientTestBase;
use App\Tests\Controller\ApiClient\ApiClientTestConfig;
use App\Tests\Controller\ApiClient\Helpers\AuthenticationTestHelper;

/**
 * Integration tests for authentication and authorization in API Client endpoints
 */
class AuthenticationIntegrationTest extends ApiClientTestBase
{
    /**
     * Test authentication with valid tokens
     */
    public function testValidTokenAuthentication(): void
    {
        $testClient = $this->createTestClient();
        
        // Test different user types with valid tokens
        $userTypes = [
            'sadm' => AuthenticationTestHelper::createSuperAdminToken(),
            'adb' => AuthenticationTestHelper::createBoutiqueAdminToken(),
            'regular' => AuthenticationTestHelper::createRegularUserToken()
        ];
        
        foreach ($userTypes as $userType => $token) {
            $headers = AuthenticationTestHelper::createAuthHeaders($token);
            
            $this->client->request(
                'GET',
                sprintf(ApiClientTestConfig::ENDPOINT_GET_ONE, $testClient->getId()),
                [],
                [],
                $headers
            );
            
            $response = $this->client->getResponse();
            
            // Should not get 401 (authentication error)
            $this->assertNotEquals(
                401,
                $response->getStatusCode(),
                "Valid {$userType} token should not result in 401 authentication error"
            );
        }
    }
    
    /**
     * Test authentication with invalid tokens
     */
    public function testInvalidTokenAuthentication(): void
    {
        $testClient = $this->createTestClient();
        
        $invalidTokens = [
            'invalid_format' => 'invalid.token.format',
            'expired' => AuthenticationTestHelper::createExpiredToken(),
            'malformed' => 'not.a.jwt',
            'empty' => '',
        ];
        
        foreach ($invalidTokens as $tokenType => $token) {
            $headers = AuthenticationTestHelper::createAuthHeaders($token);
            
            $this->client->request(
                'GET',
                sprintf(ApiClientTestConfig::ENDPOINT_GET_ONE, $testClient->getId()),
                [],
                [],
                $headers
            );
            
            $response = $this->client->getResponse();
            
            // Should get 401 (authentication error)
            $this->assertEquals(
                401,
                $response->getStatusCode(),
                "Invalid token ({$tokenType}) should result in 401 authentication error"
            );
        }
    }
    
    /**
     * Test authentication without token
     */
    public function testNoTokenAuthentication(): void
    {
        $testClient = $this->createTestClient();
        
        // Request without Authorization header
        $this->client->request(
            'GET',
            sprintf(ApiClientTestConfig::ENDPOINT_GET_ONE, $testClient->getId())
        );
        
        $response = $this->client->getResponse();
        
        // Should get 401 (authentication required)
        $this->assertEquals(
            401,
            $response->getStatusCode(),
            'Request without token should result in 401 authentication error'
        );
    }
    
    /**
     * Test role-based authorization for Super Admin
     */
    public function testSuperAdminAuthorization(): void
    {
        $token = AuthenticationTestHelper::createSuperAdminToken();
        $headers = AuthenticationTestHelper::createAuthHeaders($token);
        
        // Super admin should be able to access enterprise-level endpoint
        $this->client->request(
            'GET',
            ApiClientTestConfig::ENDPOINT_LIST_BY_ROLE,
            [],
            [],
            $headers
        );
        
        $response = $this->client->getResponse();
        
        // Should not get 403 (authorization error) due to role
        $this->assertNotEquals(
            403,
            $response->getStatusCode(),
            'Super admin should have access to enterprise client list'
        );
        
        // If it's 403, it should be due to subscription, not role
        if ($response->getStatusCode() === 403) {
            $content = json_decode($response->getContent(), true);
            if ($content && isset($content['message'])) {
                $this->assertStringContainsString(
                    'Abonnement',
                    $content['message'],
                    'Super admin 403 should be due to subscription, not role'
                );
            }
        }
    }
    
    /**
     * Test role-based authorization for Boutique Admin
     */
    public function testBoutiqueAdminAuthorization(): void
    {
        $token = AuthenticationTestHelper::createBoutiqueAdminToken();
        $headers = AuthenticationTestHelper::createAuthHeaders($token);
        
        // Boutique admin should be able to access boutique-level endpoints
        $this->client->request(
            'GET',
            ApiClientTestConfig::ENDPOINT_LIST_BY_ROLE,
            [],
            [],
            $headers
        );
        
        $response = $this->client->getResponse();
        
        // Should not get 403 due to role (may get 403 due to subscription)
        if ($response->getStatusCode() === 403) {
            $content = json_decode($response->getContent(), true);
            if ($content && isset($content['message'])) {
                $this->assertStringContainsString(
                    'Abonnement',
                    $content['message'],
                    'Boutique admin 403 should be due to subscription, not role'
                );
            }
        }
    }
    
    /**
     * Test role-based authorization for Regular User
     */
    public function testRegularUserAuthorization(): void
    {
        $token = AuthenticationTestHelper::createRegularUserToken();
        $headers = AuthenticationTestHelper::createAuthHeaders($token);
        
        // Regular user should be able to access their succursale clients
        $this->client->request(
            'GET',
            ApiClientTestConfig::ENDPOINT_LIST_BY_ROLE,
            [],
            [],
            $headers
        );
        
        $response = $this->client->getResponse();
        
        // Should not get 403 due to role (may get 403 due to subscription)
        if ($response->getStatusCode() === 403) {
            $content = json_decode($response->getContent(), true);
            if ($content && isset($content['message'])) {
                $this->assertStringContainsString(
                    'Abonnement',
                    $content['message'],
                    'Regular user 403 should be due to subscription, not role'
                );
            }
        }
    }
    
    /**
     * Test subscription requirement enforcement
     */
    public function testSubscriptionRequirement(): void
    {
        $endpoints = [
            ['method' => 'GET', 'url' => ApiClientTestConfig::ENDPOINT_LIST_BY_ROLE],
            ['method' => 'POST', 'url' => ApiClientTestConfig::ENDPOINT_CREATE],
            ['method' => 'DELETE', 'url' => sprintf(ApiClientTestConfig::ENDPOINT_DELETE, 1)]
        ];
        
        foreach ($endpoints as $endpoint) {
            $token = AuthenticationTestHelper::createUserWithoutSubscription();
            $headers = AuthenticationTestHelper::createAuthHeaders($token);
            
            $requestData = [];
            if ($endpoint['method'] === 'POST') {
                $requestData = [
                    'nom' => 'Test',
                    'prenoms' => 'Client',
                    'numero' => '+225 0123456789'
                ];
            }
            
            $this->client->request(
                $endpoint['method'],
                $endpoint['url'],
                $requestData,
                [],
                $headers
            );
            
            $response = $this->client->getResponse();
            
            // Should get 403 due to subscription requirement
            $this->assertEquals(
                403,
                $response->getStatusCode(),
                "Endpoint {$endpoint['method']} {$endpoint['url']} should require subscription"
            );
            
            $content = json_decode($response->getContent(), true);
            $this->assertArrayHasKey('message', $content);
            $this->assertStringContainsString(
                'Abonnement requis',
                $content['message'],
                'Subscription error message should be in French'
            );
        }
    }
    
    /**
     * Test token structure validation
     */
    public function testTokenStructureValidation(): void
    {
        $validToken = AuthenticationTestHelper::createSuperAdminToken();
        
        // Verify token has proper structure
        $this->assertTrue(
            AuthenticationTestHelper::verifyTokenStructure($validToken),
            'Generated token should have valid structure'
        );
        
        // Test token decoding
        $payload = AuthenticationTestHelper::decodeToken($validToken);
        
        $this->assertArrayHasKey('user_id', $payload);
        $this->assertArrayHasKey('user_type', $payload);
        $this->assertArrayHasKey('email', $payload);
        $this->assertArrayHasKey('exp', $payload);
        $this->assertArrayHasKey('iat', $payload);
    }
    
    /**
     * Test custom token creation
     */
    public function testCustomTokenCreation(): void
    {
        $customPayload = [
            'user_id' => 999,
            'user_type' => 'CUSTOM',
            'email' => 'custom@test.com',
            'custom_field' => 'custom_value'
        ];
        
        $token = AuthenticationTestHelper::createCustomToken($customPayload);
        $decodedPayload = AuthenticationTestHelper::decodeToken($token);
        
        $this->assertEquals(999, $decodedPayload['user_id']);
        $this->assertEquals('CUSTOM', $decodedPayload['user_type']);
        $this->assertEquals('custom@test.com', $decodedPayload['email']);
        $this->assertEquals('custom_value', $decodedPayload['custom_field']);
    }
    
    /**
     * Test authentication helper methods
     */
    public function testAuthenticationHelperMethods(): void
    {
        $token = AuthenticationTestHelper::createSuperAdminToken();
        
        // Test client authentication
        AuthenticationTestHelper::authenticateClient($this->client, $token);
        
        // Verify the client has the authorization header
        $this->assertEquals(
            'Bearer ' . $token,
            $this->client->getServerParameter('HTTP_AUTHORIZATION')
        );
        
        // Test removing authentication
        AuthenticationTestHelper::removeAuthentication($this->client);
        
        $this->assertNull($this->client->getServerParameter('HTTP_AUTHORIZATION'));
    }
    
    /**
     * Test header creation methods
     */
    public function testHeaderCreationMethods(): void
    {
        $token = 'test_token_123';
        
        // Test auth headers
        $authHeaders = AuthenticationTestHelper::createAuthHeaders($token);
        
        $this->assertArrayHasKey('HTTP_AUTHORIZATION', $authHeaders);
        $this->assertEquals('Bearer ' . $token, $authHeaders['HTTP_AUTHORIZATION']);
        $this->assertArrayHasKey('CONTENT_TYPE', $authHeaders);
        $this->assertEquals('application/json', $authHeaders['CONTENT_TYPE']);
        
        // Test multipart headers
        $multipartHeaders = AuthenticationTestHelper::createMultipartAuthHeaders($token);
        
        $this->assertArrayHasKey('HTTP_AUTHORIZATION', $multipartHeaders);
        $this->assertEquals('Bearer ' . $token, $multipartHeaders['HTTP_AUTHORIZATION']);
        $this->assertArrayNotHasKey('CONTENT_TYPE', $multipartHeaders); // Should not set content type for multipart
    }
}