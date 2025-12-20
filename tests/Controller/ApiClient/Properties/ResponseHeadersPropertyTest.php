<?php

namespace App\Tests\Controller\ApiClient\Properties;

use App\Tests\Controller\ApiClient\ApiClientTestBase;
use App\Tests\Controller\ApiClient\ApiClientTestConfig;
use App\Tests\Controller\ApiClient\Helpers\AuthenticationTestHelper;
use App\Tests\Controller\ApiClient\Factories\ClientTestDataFactory;
use Eris\Generator;
use Eris\TestTrait;

/**
 * Property-based test for response headers consistency
 * 
 * **Feature: api-client-testing, Property 2: Response headers consistency**
 * **Validates: Requirements 1.5**
 * 
 * Tests that all successful API responses have proper Content-Type headers set to application/json
 */
class ResponseHeadersPropertyTest extends ApiClientTestBase
{
    use TestTrait;
    
    /**
     * Property 2: Response headers consistency
     * For any successful API response, the Content-Type header should be set to application/json
     */
    public function testResponseHeadersConsistencyProperty(): void
    {
        $this->forAll(
            Generator\choose(0, 7), // Endpoint selector
            Generator\choose(0, 2)   // User type selector
        )->then(function (int $endpointIndex, int $userTypeIndex): void {
            
            // Create test client for endpoints that need it
            $testClient = $this->createTestClient();
            
            // Select endpoint and user type
            $endpoints = [
                ApiClientTestConfig::ENDPOINT_LIST_ALL,
                ApiClientTestConfig::ENDPOINT_LIST_BY_ROLE,
                sprintf(ApiClientTestConfig::ENDPOINT_GET_ONE, $testClient->getId()),
                ApiClientTestConfig::ENDPOINT_CREATE,
                ApiClientTestConfig::ENDPOINT_CREATE_BOUTIQUE,
                sprintf(ApiClientTestConfig::ENDPOINT_UPDATE, $testClient->getId()),
                sprintf(ApiClientTestConfig::ENDPOINT_DELETE, $testClient->getId()),
                ApiClientTestConfig::ENDPOINT_BULK_DELETE
            ];
            
            $userTypes = ['sadm', 'adb', 'regular'];
            $endpoint = $endpoints[$endpointIndex];
            $userType = $userTypes[$userTypeIndex];
            
            // Prepare request data based on endpoint
            $requestData = $this->prepareRequestData($endpoint);
            $method = $this->getHttpMethod($endpoint);
            
            // Make authenticated request
            $token = $this->getTokenForUserType($userType);
            $headers = AuthenticationTestHelper::createAuthHeaders($token);
            
            $this->client->request(
                $method,
                $endpoint,
                $requestData['parameters'],
                $requestData['files'],
                $headers,
                $requestData['content']
            );
            
            $response = $this->client->getResponse();
            
            // Only test successful responses (2xx status codes)
            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                // Property: Content-Type header should be application/json
                $this->assertTrue(
                    $response->headers->contains('Content-Type', 'application/json'),
                    sprintf(
                        'Expected Content-Type header to be application/json for endpoint %s with user type %s, got: %s',
                        $endpoint,
                        $userType,
                        $response->headers->get('Content-Type')
                    )
                );
                
                // Additional check: Response should be valid JSON
                $content = $response->getContent();
                $decodedContent = json_decode($content, true);
                $this->assertNotNull(
                    $decodedContent,
                    sprintf(
                        'Response content should be valid JSON for endpoint %s with user type %s',
                        $endpoint,
                        $userType
                    )
                );
            }
        });
    }
    
    /**
     * Property test for specific successful endpoints
     * Tests only endpoints that are guaranteed to return 200/201 with proper authentication
     */
    public function testGuaranteedSuccessfulEndpointsHeadersProperty(): void
    {
        $this->forAll(
            Generator\elements(['sadm']) // Only test with super admin to ensure success
        )->then(function (string $userType): void {
            
            // Test LIST ALL endpoint (should always work for authenticated users)
            $token = AuthenticationTestHelper::createSuperAdminToken();
            $headers = AuthenticationTestHelper::createAuthHeaders($token);
            
            $this->client->request('GET', ApiClientTestConfig::ENDPOINT_LIST_ALL, [], [], $headers);
            $response = $this->client->getResponse();
            
            // This should be successful
            $this->assertEquals(200, $response->getStatusCode());
            
            // Property: Content-Type must be application/json
            $this->assertTrue(
                $response->headers->contains('Content-Type', 'application/json'),
                'List all clients endpoint must return application/json Content-Type'
            );
            
            // Property: Response must be valid JSON
            $content = json_decode($response->getContent(), true);
            $this->assertIsArray($content, 'Response must be valid JSON array');
        });
    }
    
    /**
     * Property test for client creation with valid data
     * Tests that successful client creation returns proper headers
     */
    public function testClientCreationSuccessHeadersProperty(): void
    {
        $this->forAll(
            Generator\string()->withMaxSize(50), // nom
            Generator\string()->withMaxSize(50), // prenoms  
            Generator\string()->withMaxSize(20)  // numero
        )->then(function (string $nom, string $prenoms, string $numero): void {
            
            // Skip empty strings to ensure valid data
            if (empty(trim($nom)) || empty(trim($prenoms)) || empty(trim($numero))) {
                return;
            }
            
            $clientData = [
                'nom' => trim($nom),
                'prenoms' => trim($prenoms),
                'numero' => '+225 ' . preg_replace('/[^0-9]/', '', $numero),
                'boutique' => $this->testData['boutique']->getId(),
                'succursale' => $this->testData['succursale']->getId()
            ];
            
            $token = AuthenticationTestHelper::createSuperAdminToken();
            $headers = AuthenticationTestHelper::createMultipartAuthHeaders($token);
            
            $this->client->request(
                'POST',
                ApiClientTestConfig::ENDPOINT_CREATE,
                $clientData,
                [],
                $headers
            );
            
            $response = $this->client->getResponse();
            
            // If creation is successful (201), check headers
            if ($response->getStatusCode() === 201) {
                // Property: Content-Type must be application/json
                $this->assertTrue(
                    $response->headers->contains('Content-Type', 'application/json'),
                    'Successful client creation must return application/json Content-Type'
                );
                
                // Property: Response must contain valid client data
                $content = json_decode($response->getContent(), true);
                $this->assertIsArray($content, 'Response must be valid JSON');
                $this->assertArrayHasKey('data', $content, 'Response must contain data field');
            }
        });
    }
    
    /**
     * Property test for error responses headers
     * Tests that even error responses have consistent headers
     */
    public function testErrorResponsesHeadersProperty(): void
    {
        $this->forAll(
            Generator\elements([400, 401, 403, 404, 500]) // Error status codes
        )->then(function (int $expectedErrorCode): void {
            
            // Create scenarios that should produce specific error codes
            switch ($expectedErrorCode) {
                case 401:
                    // No authentication
                    $this->client->request('GET', ApiClientTestConfig::ENDPOINT_LIST_BY_ROLE);
                    break;
                    
                case 403:
                    // User without subscription
                    $token = AuthenticationTestHelper::createUserWithoutSubscription();
                    $headers = AuthenticationTestHelper::createAuthHeaders($token);
                    $this->client->request('GET', ApiClientTestConfig::ENDPOINT_LIST_BY_ROLE, [], [], $headers);
                    break;
                    
                case 404:
                    // Non-existent client
                    $token = AuthenticationTestHelper::createSuperAdminToken();
                    $headers = AuthenticationTestHelper::createAuthHeaders($token);
                    $this->client->request('GET', sprintf(ApiClientTestConfig::ENDPOINT_GET_ONE, 99999), [], [], $headers);
                    break;
                    
                case 400:
                    // Invalid data
                    $token = AuthenticationTestHelper::createSuperAdminToken();
                    $headers = AuthenticationTestHelper::createMultipartAuthHeaders($token);
                    $this->client->request('POST', ApiClientTestConfig::ENDPOINT_CREATE, [], [], $headers);
                    break;
                    
                default:
                    return; // Skip other codes for now
            }
            
            $response = $this->client->getResponse();
            
            // Property: Even error responses should have proper Content-Type
            if ($response->getStatusCode() >= 400) {
                $this->assertTrue(
                    $response->headers->contains('Content-Type', 'application/json'),
                    sprintf(
                        'Error response with status %d should have application/json Content-Type, got: %s',
                        $response->getStatusCode(),
                        $response->headers->get('Content-Type')
                    )
                );
                
                // Property: Error responses should be valid JSON
                $content = json_decode($response->getContent(), true);
                $this->assertIsArray($content, 'Error response must be valid JSON');
            }
        });
    }
    
    /**
     * Prepare request data based on endpoint
     */
    private function prepareRequestData(string $endpoint): array
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
    private function getHttpMethod(string $endpoint): string
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
     * Get token for user type
     */
    private function getTokenForUserType(string $userType): string
    {
        switch ($userType) {
            case 'sadm':
                return AuthenticationTestHelper::createSuperAdminToken();
            case 'adb':
                return AuthenticationTestHelper::createBoutiqueAdminToken();
            case 'regular':
                return AuthenticationTestHelper::createRegularUserToken();
            default:
                return AuthenticationTestHelper::createSuperAdminToken();
        }
    }
}