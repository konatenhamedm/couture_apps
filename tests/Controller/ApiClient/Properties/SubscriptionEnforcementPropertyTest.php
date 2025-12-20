<?php

namespace App\Tests\Controller\ApiClient\Properties;

use App\Tests\Controller\ApiClient\ApiClientTestBase;
use App\Tests\Controller\ApiClient\ApiClientTestConfig;
use App\Tests\Controller\ApiClient\Helpers\AuthenticationTestHelper;
use App\Tests\Controller\ApiClient\Factories\ClientTestDataFactory;
use Eris\Generator;
use Eris\TestTrait;

/**
 * Property-based test for subscription requirement enforcement
 * 
 * **Feature: api-client-testing, Property 14: Subscription requirement enforcement**
 * **Validates: Requirements 2.1, 3.3, 4.5, 7.5**
 * 
 * Tests that all API endpoints requiring subscription return 403 error for users without active subscription
 */
class SubscriptionEnforcementPropertyTest extends ApiClientTestBase
{
    use TestTrait;
    
    /**
     * Property 14: Subscription requirement enforcement
     * For any API endpoint requiring subscription, requests from users without active subscription 
     * should return 403 error with subscription required message
     */
    public function testSubscriptionRequirementEnforcementProperty(): void
    {
        $this->forAll(
            Generator\elements($this->getSubscriptionRequiredEndpoints()),
            Generator\elements(['SADM', 'ADB', 'REG']) // Different user types
        )->then(function (array $endpointInfo, string $userType): void {
            
            // Create test client for endpoints that need it
            $testClient = $this->createTestClient();
            
            // Prepare endpoint URL with parameters if needed
            $endpoint = $this->prepareEndpointUrl($endpointInfo['endpoint'], $testClient->getId());
            
            // Create user without subscription
            $token = AuthenticationTestHelper::createUserWithoutSubscription($userType);
            $headers = $this->prepareHeaders($endpointInfo['method'], $token);
            
            // Prepare request data
            $requestData = $this->prepareRequestDataForEndpoint($endpointInfo['endpoint']);
            
            // Make request
            $this->client->request(
                $endpointInfo['method'],
                $endpoint,
                $requestData['parameters'],
                $requestData['files'],
                $headers,
                $requestData['content']
            );
            
            $response = $this->client->getResponse();
            
            // Property: Must return 403 Forbidden
            $this->assertEquals(
                403,
                $response->getStatusCode(),
                sprintf(
                    'Endpoint %s with method %s should return 403 for user without subscription (user type: %s)',
                    $endpoint,
                    $endpointInfo['method'],
                    $userType
                )
            );
            
            // Property: Response must contain subscription required message
            $content = json_decode($response->getContent(), true);
            $this->assertIsArray($content, 'Response must be valid JSON');
            $this->assertArrayHasKey('message', $content, 'Response must contain message field');
            
            $expectedMessage = ApiClientTestConfig::ERROR_SUBSCRIPTION_REQUIRED;
            $this->assertStringContainsString(
                'Abonnement requis',
                $content['message'],
                sprintf(
                    'Response message should contain subscription requirement text for endpoint %s',
                    $endpoint
                )
            );
        });
    }
    
    /**
     * Property test for subscription enforcement across all user types
     */
    public function testSubscriptionEnforcementAcrossUserTypesProperty(): void
    {
        $this->forAll(
            Generator\elements(['SADM', 'ADB', 'REG']),
            Generator\elements([
                ApiClientTestConfig::ENDPOINT_LIST_BY_ROLE,
                ApiClientTestConfig::ENDPOINT_GET_ONE,
                ApiClientTestConfig::ENDPOINT_CREATE,
                ApiClientTestConfig::ENDPOINT_DELETE
            ])
        )->then(function (string $userType, string $endpoint): void {
            
            $testClient = $this->createTestClient();
            $endpointUrl = $this->prepareEndpointUrl($endpoint, $testClient->getId());
            
            // Test with user without subscription
            $tokenWithoutSub = AuthenticationTestHelper::createUserWithoutSubscription($userType);
            $headersWithoutSub = AuthenticationTestHelper::createAuthHeaders($tokenWithoutSub);
            
            $method = $this->getMethodForEndpoint($endpoint);
            $requestData = $this->prepareRequestDataForEndpoint($endpoint);
            
            $this->client->request(
                $method,
                $endpointUrl,
                $requestData['parameters'],
                $requestData['files'],
                $headersWithoutSub,
                $requestData['content']
            );
            
            $responseWithoutSub = $this->client->getResponse();
            
            // Property: User without subscription gets 403
            $this->assertEquals(
                403,
                $responseWithoutSub->getStatusCode(),
                sprintf('User type %s without subscription should get 403 for %s', $userType, $endpoint)
            );
            
            // Test with user with subscription (should not get 403 for subscription reasons)
            $tokenWithSub = $this->getTokenWithSubscription($userType);
            $headersWithSub = AuthenticationTestHelper::createAuthHeaders($tokenWithSub);
            
            $this->client->request(
                $method,
                $endpointUrl,
                $requestData['parameters'],
                $requestData['files'],
                $headersWithSub,
                $requestData['content']
            );
            
            $responseWithSub = $this->client->getResponse();
            
            // Property: User with subscription should not get 403 due to subscription
            // (may get other errors like 404, 400, but not 403 subscription error)
            if ($responseWithSub->getStatusCode() === 403) {
                $content = json_decode($responseWithSub->getContent(), true);
                if ($content && isset($content['message'])) {
                    $this->assertStringNotContainsString(
                        'Abonnement requis',
                        $content['message'],
                        sprintf('User type %s with subscription should not get subscription error for %s', $userType, $endpoint)
                    );
                }
            }
        });
    }
    
    /**
     * Property test for consistent subscription error format
     */
    public function testSubscriptionErrorFormatConsistencyProperty(): void
    {
        $this->forAll(
            Generator\elements($this->getSubscriptionRequiredEndpoints())
        )->then(function (array $endpointInfo): void {
            
            $testClient = $this->createTestClient();
            $endpoint = $this->prepareEndpointUrl($endpointInfo['endpoint'], $testClient->getId());
            
            $token = AuthenticationTestHelper::createUserWithoutSubscription();
            $headers = $this->prepareHeaders($endpointInfo['method'], $token);
            $requestData = $this->prepareRequestDataForEndpoint($endpointInfo['endpoint']);
            
            $this->client->request(
                $endpointInfo['method'],
                $endpoint,
                $requestData['parameters'],
                $requestData['files'],
                $headers,
                $requestData['content']
            );
            
            $response = $this->client->getResponse();
            
            // Property: All subscription errors have consistent format
            $this->assertEquals(403, $response->getStatusCode());
            
            $content = json_decode($response->getContent(), true);
            $this->assertIsArray($content);
            
            // Property: Response structure is consistent
            $this->assertArrayHasKey('message', $content);
            $this->assertArrayHasKey('statusCode', $content);
            
            // Property: Status code in response matches HTTP status
            $this->assertEquals(403, $content['statusCode']);
            
            // Property: Message contains subscription requirement
            $this->assertStringContainsString('Abonnement', $content['message']);
        });
    }
    
    /**
     * Property test for endpoints that don't require subscription
     */
    public function testNonSubscriptionEndpointsProperty(): void
    {
        // Test endpoints that should work without subscription (if any)
        $nonSubscriptionEndpoints = [
            ApiClientTestConfig::ENDPOINT_LIST_ALL // This one might not require subscription
        ];
        
        $this->forAll(
            Generator\elements($nonSubscriptionEndpoints)
        )->then(function (string $endpoint): void {
            
            $token = AuthenticationTestHelper::createUserWithoutSubscription();
            $headers = AuthenticationTestHelper::createAuthHeaders($token);
            
            $this->client->request('GET', $endpoint, [], [], $headers);
            $response = $this->client->getResponse();
            
            // Property: Non-subscription endpoints should not return 403 subscription error
            if ($response->getStatusCode() === 403) {
                $content = json_decode($response->getContent(), true);
                if ($content && isset($content['message'])) {
                    $this->assertStringNotContainsString(
                        'Abonnement requis',
                        $content['message'],
                        sprintf('Endpoint %s should not require subscription', $endpoint)
                    );
                }
            }
        });
    }
    
    /**
     * Get endpoints that require subscription
     */
    private function getSubscriptionRequiredEndpoints(): array
    {
        return [
            ['endpoint' => ApiClientTestConfig::ENDPOINT_LIST_BY_ROLE, 'method' => 'GET'],
            ['endpoint' => ApiClientTestConfig::ENDPOINT_GET_ONE, 'method' => 'GET'],
            ['endpoint' => ApiClientTestConfig::ENDPOINT_CREATE, 'method' => 'POST'],
            ['endpoint' => ApiClientTestConfig::ENDPOINT_CREATE_BOUTIQUE, 'method' => 'POST'],
            ['endpoint' => ApiClientTestConfig::ENDPOINT_UPDATE, 'method' => 'PUT'],
            ['endpoint' => ApiClientTestConfig::ENDPOINT_DELETE, 'method' => 'DELETE'],
            ['endpoint' => ApiClientTestConfig::ENDPOINT_BULK_DELETE, 'method' => 'DELETE']
        ];
    }
    
    /**
     * Prepare endpoint URL with parameters
     */
    private function prepareEndpointUrl(string $endpoint, int $clientId): string
    {
        if (strpos($endpoint, '%d') !== false) {
            return sprintf($endpoint, $clientId);
        }
        return $endpoint;
    }
    
    /**
     * Prepare headers based on method and token
     */
    private function prepareHeaders(string $method, string $token): array
    {
        if (in_array($method, ['POST', 'PUT']) && strpos($method, 'create') !== false) {
            return AuthenticationTestHelper::createMultipartAuthHeaders($token);
        }
        return AuthenticationTestHelper::createAuthHeaders($token);
    }
    
    /**
     * Prepare request data for endpoint
     */
    private function prepareRequestDataForEndpoint(string $endpoint): array
    {
        $defaultData = [
            'parameters' => [],
            'files' => [],
            'content' => null
        ];
        
        if (strpos($endpoint, '/create') !== false) {
            $defaultData['parameters'] = ClientTestDataFactory::createValidClientData();
        } elseif (strpos($endpoint, '/update') !== false) {
            $defaultData['parameters'] = ClientTestDataFactory::createUpdateData();
        } elseif (strpos($endpoint, '/delete/all/items') !== false) {
            $defaultData['content'] = json_encode(['ids' => [1, 2, 3]]);
        }
        
        return $defaultData;
    }
    
    /**
     * Get HTTP method for endpoint
     */
    private function getMethodForEndpoint(string $endpoint): string
    {
        if (strpos($endpoint, '/create') !== false) {
            return 'POST';
        } elseif (strpos($endpoint, '/update') !== false) {
            return 'PUT';
        } elseif (strpos($endpoint, '/delete') !== false) {
            return 'DELETE';
        }
        return 'GET';
    }
    
    /**
     * Get token with subscription for user type
     */
    private function getTokenWithSubscription(string $userType): string
    {
        switch ($userType) {
            case 'SADM':
                return AuthenticationTestHelper::createSuperAdminToken();
            case 'ADB':
                return AuthenticationTestHelper::createBoutiqueAdminToken();
            case 'REG':
                return AuthenticationTestHelper::createRegularUserToken();
            default:
                return AuthenticationTestHelper::createSuperAdminToken();
        }
    }
}