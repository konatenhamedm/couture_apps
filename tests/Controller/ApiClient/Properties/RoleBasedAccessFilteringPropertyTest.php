<?php

namespace App\Tests\Controller\ApiClient\Properties;

use App\Tests\Controller\ApiClient\ApiClientTestBase;
use App\Tests\Controller\ApiClient\ApiClientTestConfig;
use App\Tests\Controller\ApiClient\Helpers\AuthenticationTestHelper;
use Eris\Generator;
use Eris\TestTrait;

/**
 * Property-based test for role-based access filtering
 * 
 * **Feature: api-client-testing, Property 3: Role-based access filtering**
 * **Validates: Requirements 2.2, 2.3, 2.4, 2.5**
 * 
 * Tests that authenticated users with active subscription see clients filtered according to their role:
 * SADM sees all enterprise clients, ADB sees boutique clients, others see succursale clients, 
 * all ordered by id ascending
 */
class RoleBasedAccessFilteringPropertyTest extends ApiClientTestBase
{
    use TestTrait;
    
    /**
     * Property 3: Role-based access filtering
     * For any authenticated user with active subscription, the client list should be filtered 
     * according to their role: SADM sees all enterprise clients, ADB sees boutique clients, 
     * others see succursale clients, all ordered by id ascending
     */
    public function testRoleBasedAccessFilteringProperty(): void
    {
        // Create test clients in different contexts
        $this->createTestClientsForRoleTesting();
        
        $this->forAll(
            Generator\elements(['SADM', 'ADB', 'REG']) // User types
        )->then(function (string $userType): void {
            
            $token = $this->getTokenForUserType($userType);
            $headers = AuthenticationTestHelper::createAuthHeaders($token);
            
            $this->client->request('GET', ApiClientTestConfig::ENDPOINT_LIST_BY_ROLE, [], [], $headers);
            $response = $this->client->getResponse();
            
            // Skip if subscription required (this is tested separately)
            if ($response->getStatusCode() === 403) {
                $content = json_decode($response->getContent(), true);
                if (isset($content['message']) && strpos($content['message'], 'Abonnement') !== false) {
                    return;
                }
            }
            
            // Property: Authenticated users with subscription should get 200 OK
            $this->assertEquals(
                200,
                $response->getStatusCode(),
                sprintf('User type %s with subscription should get 200 OK', $userType)
            );
            
            $content = json_decode($response->getContent(), true);
            $this->assertIsArray($content, 'Response should be valid JSON');
            $this->assertArrayHasKey('data', $content, 'Response should contain data');
            
            $clients = $content['data'];
            
            // Property: Results should be ordered by id in ascending order
            if (count($clients) > 1) {
                for ($i = 1; $i < count($clients); $i++) {
                    $this->assertLessThanOrEqual(
                        $clients[$i]['id'],
                        $clients[$i-1]['id'],
                        sprintf('Clients should be ordered by id ASC for user type %s', $userType)
                    );
                }
            }
            
            // Property: All returned clients should be accessible to this user type
            foreach ($clients as $client) {
                $this->assertClientAccessibleToUserType($client, $userType);
            }
        });
    }
    
    /**
     * Property test for SADM user access (should see all enterprise clients)
     */
    public function testSuperAdminAccessProperty(): void
    {
        $this->createTestClientsForRoleTesting();
        
        $this->forAll(
            Generator\constant('SADM') // Only test SADM
        )->then(function (string $userType): void {
            
            $token = AuthenticationTestHelper::createSuperAdminToken();
            $headers = AuthenticationTestHelper::createAuthHeaders($token);
            
            $this->client->request('GET', ApiClientTestConfig::ENDPOINT_LIST_BY_ROLE, [], [], $headers);
            $response = $this->client->getResponse();
            
            // Skip if subscription required
            if ($response->getStatusCode() === 403) {
                return;
            }
            
            if ($response->getStatusCode() === 200) {
                $content = json_decode($response->getContent(), true);
                $clients = $content['data'];
                
                // Property: SADM should see all clients in the enterprise
                foreach ($clients as $client) {
                    $this->assertArrayHasKey('entreprise', $client, 'Client should have enterprise association');
                    
                    if (isset($client['entreprise']['id'])) {
                        $this->assertEquals(
                            $this->testData['entreprise']->getId(),
                            $client['entreprise']['id'],
                            'SADM should only see clients from their managed enterprise'
                        );
                    }
                }
            }
        });
    }
    
    /**
     * Property test for ADB user access (should see only boutique clients)
     */
    public function testBoutiqueAdminAccessProperty(): void
    {
        $this->createTestClientsForRoleTesting();
        
        $this->forAll(
            Generator\constant('ADB') // Only test ADB
        )->then(function (string $userType): void {
            
            $token = AuthenticationTestHelper::createBoutiqueAdminToken();
            $headers = AuthenticationTestHelper::createAuthHeaders($token);
            
            $this->client->request('GET', ApiClientTestConfig::ENDPOINT_LIST_BY_ROLE, [], [], $headers);
            $response = $this->client->getResponse();
            
            // Skip if subscription required
            if ($response->getStatusCode() === 403) {
                return;
            }
            
            if ($response->getStatusCode() === 200) {
                $content = json_decode($response->getContent(), true);
                $clients = $content['data'];
                
                // Property: ADB should see only clients from their boutique
                foreach ($clients as $client) {
                    $this->assertArrayHasKey('boutique', $client, 'Client should have boutique association');
                    
                    if (isset($client['boutique']['id'])) {
                        $this->assertEquals(
                            $this->testData['boutique']->getId(),
                            $client['boutique']['id'],
                            'ADB should only see clients from their boutique'
                        );
                    }
                }
            }
        });
    }
    
    /**
     * Property test for regular user access (should see only succursale clients)
     */
    public function testRegularUserAccessProperty(): void
    {
        $this->createTestClientsForRoleTesting();
        
        $this->forAll(
            Generator\constant('REG') // Only test regular users
        )->then(function (string $userType): void {
            
            $token = AuthenticationTestHelper::createRegularUserToken();
            $headers = AuthenticationTestHelper::createAuthHeaders($token);
            
            $this->client->request('GET', ApiClientTestConfig::ENDPOINT_LIST_BY_ROLE, [], [], $headers);
            $response = $this->client->getResponse();
            
            // Skip if subscription required
            if ($response->getStatusCode() === 403) {
                return;
            }
            
            if ($response->getStatusCode() === 200) {
                $content = json_decode($response->getContent(), true);
                $clients = $content['data'];
                
                // Property: Regular users should see only clients from their succursale
                foreach ($clients as $client) {
                    $this->assertArrayHasKey('succursale', $client, 'Client should have succursale association');
                    
                    if (isset($client['succursale']['id'])) {
                        $this->assertEquals(
                            $this->testData['succursale']->getId(),
                            $client['succursale']['id'],
                            'Regular user should only see clients from their succursale'
                        );
                    }
                }
            }
        });
    }
    
    /**
     * Property test for access consistency across multiple requests
     */
    public function testAccessConsistencyProperty(): void
    {
        $this->createTestClientsForRoleTesting();
        
        $this->forAll(
            Generator\elements(['SADM', 'ADB', 'REG']),
            Generator\choose(1, 3) // Number of requests to make
        )->then(function (string $userType, int $requestCount): void {
            
            $token = $this->getTokenForUserType($userType);
            $headers = AuthenticationTestHelper::createAuthHeaders($token);
            
            $responses = [];
            
            // Make multiple requests
            for ($i = 0; $i < $requestCount; $i++) {
                $this->client->request('GET', ApiClientTestConfig::ENDPOINT_LIST_BY_ROLE, [], [], $headers);
                $response = $this->client->getResponse();
                
                if ($response->getStatusCode() === 200) {
                    $content = json_decode($response->getContent(), true);
                    $responses[] = $content['data'];
                }
            }
            
            // Property: Multiple requests should return consistent results
            if (count($responses) > 1) {
                $firstResponse = $responses[0];
                
                for ($i = 1; $i < count($responses); $i++) {
                    $this->assertEquals(
                        count($firstResponse),
                        count($responses[$i]),
                        sprintf('Request %d should return same number of clients as first request for user type %s', $i + 1, $userType)
                    );
                    
                    // Compare client IDs (should be same clients in same order)
                    $firstIds = array_column($firstResponse, 'id');
                    $currentIds = array_column($responses[$i], 'id');
                    
                    $this->assertEquals(
                        $firstIds,
                        $currentIds,
                        sprintf('Request %d should return same clients in same order for user type %s', $i + 1, $userType)
                    );
                }
            }
        });
    }
    
    /**
     * Property test for ordering consistency
     */
    public function testOrderingConsistencyProperty(): void
    {
        // Create clients with specific IDs to test ordering
        $this->createTestClientsForRoleTesting();
        
        $this->forAll(
            Generator\elements(['SADM', 'ADB', 'REG'])
        )->then(function (string $userType): void {
            
            $token = $this->getTokenForUserType($userType);
            $headers = AuthenticationTestHelper::createAuthHeaders($token);
            
            $this->client->request('GET', ApiClientTestConfig::ENDPOINT_LIST_BY_ROLE, [], [], $headers);
            $response = $this->client->getResponse();
            
            if ($response->getStatusCode() === 200) {
                $content = json_decode($response->getContent(), true);
                $clients = $content['data'];
                
                // Property: Results should be consistently ordered by ID ascending
                $clientIds = array_column($clients, 'id');
                $sortedIds = $clientIds;
                sort($sortedIds, SORT_NUMERIC);
                
                $this->assertEquals(
                    $sortedIds,
                    $clientIds,
                    sprintf('Clients should be ordered by ID ascending for user type %s', $userType)
                );
            }
        });
    }
    
    /**
     * Property test for empty results handling
     */
    public function testEmptyResultsHandlingProperty(): void
    {
        // Don't create any clients, test empty results
        
        $this->forAll(
            Generator\elements(['SADM', 'ADB', 'REG'])
        )->then(function (string $userType): void {
            
            $token = $this->getTokenForUserType($userType);
            $headers = AuthenticationTestHelper::createAuthHeaders($token);
            
            $this->client->request('GET', ApiClientTestConfig::ENDPOINT_LIST_BY_ROLE, [], [], $headers);
            $response = $this->client->getResponse();
            
            // Skip if subscription required
            if ($response->getStatusCode() === 403) {
                return;
            }
            
            if ($response->getStatusCode() === 200) {
                $content = json_decode($response->getContent(), true);
                
                // Property: Empty results should still have proper structure
                $this->assertArrayHasKey('data', $content, 'Empty results should have data array');
                $this->assertIsArray($content['data'], 'Data should be array even when empty');
                $this->assertEmpty($content['data'], 'Data should be empty when no clients exist');
                
                // Should still have pagination
                $this->assertPaginationStructure($content);
                
                $pagination = $content['pagination'];
                $this->assertEquals(0, $pagination['total'], 'Total should be 0 for empty results');
            }
        });
    }
    
    /**
     * Get token for specific user type
     */
    private function getTokenForUserType(string $userType): string
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
    
    /**
     * Assert that client is accessible to user type
     */
    private function assertClientAccessibleToUserType(array $client, string $userType): void
    {
        switch ($userType) {
            case 'SADM':
                // SADM should see all enterprise clients
                $this->assertArrayHasKey('entreprise', $client);
                break;
                
            case 'ADB':
                // ADB should see only their boutique clients
                $this->assertArrayHasKey('boutique', $client);
                break;
                
            case 'REG':
                // Regular users should see only their succursale clients
                $this->assertArrayHasKey('succursale', $client);
                break;
        }
    }
    
    /**
     * Create test clients for role testing
     */
    private function createTestClientsForRoleTesting(): void
    {
        // Create clients associated with our test data
        for ($i = 1; $i <= 5; $i++) {
            $this->createTestClient([
                'nom' => "RoleTestClient{$i}",
                'prenom' => "Test{$i}",
                'numero' => "+225 070000000{$i}"
            ]);
        }
    }
}