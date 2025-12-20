<?php

namespace App\Tests\Controller\ApiClient\Integration;

use App\Tests\Controller\ApiClient\ApiClientTestBase;
use App\Tests\Controller\ApiClient\ApiClientTestConfig;
use App\Tests\Controller\ApiClient\Helpers\AuthenticationTestHelper;

/**
 * Integration tests for client listing endpoints
 */
class ClientListingIntegrationTest extends ApiClientTestBase
{
    /**
     * Test GET /api/client/ endpoint (list all clients)
     */
    public function testListAllClientsEndpoint(): void
    {
        // Create some test clients
        $client1 = $this->createTestClient(['nom' => 'Client1', 'prenom' => 'Test1']);
        $client2 = $this->createTestClient(['nom' => 'Client2', 'prenom' => 'Test2']);
        
        $token = AuthenticationTestHelper::createSuperAdminToken();
        $headers = AuthenticationTestHelper::createAuthHeaders($token);
        
        $this->client->request('GET', ApiClientTestConfig::ENDPOINT_LIST_ALL, [], [], $headers);
        $response = $this->client->getResponse();
        
        // Should return 200 OK
        $this->assertEquals(200, $response->getStatusCode());
        
        // Should return JSON
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        
        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content);
        
        // Should contain pagination structure
        $this->assertPaginationStructure($content);
        
        // Should contain client data
        $this->assertArrayHasKey('data', $content);
        $this->assertIsArray($content['data']);
        
        // Verify client data structure
        if (!empty($content['data'])) {
            foreach ($content['data'] as $clientData) {
                $this->assertClientDataStructure($clientData);
            }
        }
    }
    
    /**
     * Test GET /api/client/entreprise endpoint with different user roles
     */
    public function testListClientsByRoleEndpoint(): void
    {
        // Create test clients
        $this->createTestClient(['nom' => 'ClientSADM', 'prenom' => 'Test']);
        
        // Test with Super Admin (should see all enterprise clients)
        $sadmToken = AuthenticationTestHelper::createSuperAdminToken();
        $sadmHeaders = AuthenticationTestHelper::createAuthHeaders($sadmToken);
        
        $this->client->request('GET', ApiClientTestConfig::ENDPOINT_LIST_BY_ROLE, [], [], $sadmHeaders);
        $sadmResponse = $this->client->getResponse();
        
        // Should not get 401 (authentication error)
        $this->assertNotEquals(401, $sadmResponse->getStatusCode());
        
        // If 403, should be due to subscription, not role
        if ($sadmResponse->getStatusCode() === 403) {
            $content = json_decode($sadmResponse->getContent(), true);
            if ($content && isset($content['message'])) {
                $this->assertStringContainsString('Abonnement', $content['message']);
            }
        }
        
        // Test with Boutique Admin
        $adbToken = AuthenticationTestHelper::createBoutiqueAdminToken();
        $adbHeaders = AuthenticationTestHelper::createAuthHeaders($adbToken);
        
        $this->client->request('GET', ApiClientTestConfig::ENDPOINT_LIST_BY_ROLE, [], [], $adbHeaders);
        $adbResponse = $this->client->getResponse();
        
        $this->assertNotEquals(401, $adbResponse->getStatusCode());
        
        // Test with Regular User
        $regToken = AuthenticationTestHelper::createRegularUserToken();
        $regHeaders = AuthenticationTestHelper::createAuthHeaders($regToken);
        
        $this->client->request('GET', ApiClientTestConfig::ENDPOINT_LIST_BY_ROLE, [], [], $regHeaders);
        $regResponse = $this->client->getResponse();
        
        $this->assertNotEquals(401, $regResponse->getStatusCode());
    }
    
    /**
     * Test pagination functionality
     */
    public function testPaginationFunctionality(): void
    {
        // Create multiple test clients
        for ($i = 1; $i <= 15; $i++) {
            $this->createTestClient([
                'nom' => "Client{$i}",
                'prenom' => "Test{$i}",
                'numero' => "+225 012345678{$i}"
            ]);
        }
        
        $token = AuthenticationTestHelper::createSuperAdminToken();
        $headers = AuthenticationTestHelper::createAuthHeaders($token);
        
        // Test first page
        $this->client->request(
            'GET',
            ApiClientTestConfig::ENDPOINT_LIST_ALL . '?page=1&size=5',
            [],
            [],
            $headers
        );
        
        $response = $this->client->getResponse();
        
        if ($response->getStatusCode() === 200) {
            $content = json_decode($response->getContent(), true);
            
            $this->assertPaginationStructure($content);
            
            $pagination = $content['pagination'];
            $this->assertEquals(1, $pagination['current_page']);
            $this->assertEquals(5, $pagination['per_page']);
            $this->assertGreaterThanOrEqual(15, $pagination['total']);
            
            // Should have 5 items or less
            $this->assertLessThanOrEqual(5, count($content['data']));
        }
    }
    
    /**
     * Test error scenarios for listing endpoints
     */
    public function testListingErrorScenarios(): void
    {
        // Test without authentication
        $this->client->request('GET', ApiClientTestConfig::ENDPOINT_LIST_BY_ROLE);
        $response = $this->client->getResponse();
        
        $this->assertEquals(401, $response->getStatusCode());
        
        // Test with invalid token
        $invalidHeaders = AuthenticationTestHelper::createAuthHeaders('invalid.token.here');
        
        $this->client->request('GET', ApiClientTestConfig::ENDPOINT_LIST_BY_ROLE, [], [], $invalidHeaders);
        $response = $this->client->getResponse();
        
        $this->assertEquals(401, $response->getStatusCode());
        
        // Test with user without subscription
        $noSubToken = AuthenticationTestHelper::createUserWithoutSubscription();
        $noSubHeaders = AuthenticationTestHelper::createAuthHeaders($noSubToken);
        
        $this->client->request('GET', ApiClientTestConfig::ENDPOINT_LIST_BY_ROLE, [], [], $noSubHeaders);
        $response = $this->client->getResponse();
        
        $this->assertEquals(403, $response->getStatusCode());
        
        $content = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $content);
        $this->assertStringContainsString('Abonnement requis', $content['message']);
    }
    
    /**
     * Test response structure consistency
     */
    public function testResponseStructureConsistency(): void
    {
        $this->createTestClient(['nom' => 'StructureTest', 'prenom' => 'Client']);
        
        $endpoints = [
            ApiClientTestConfig::ENDPOINT_LIST_ALL,
            ApiClientTestConfig::ENDPOINT_LIST_BY_ROLE
        ];
        
        $token = AuthenticationTestHelper::createSuperAdminToken();
        $headers = AuthenticationTestHelper::createAuthHeaders($token);
        
        foreach ($endpoints as $endpoint) {
            $this->client->request('GET', $endpoint, [], [], $headers);
            $response = $this->client->getResponse();
            
            // Skip if subscription required
            if ($response->getStatusCode() === 403) {
                continue;
            }
            
            if ($response->getStatusCode() === 200) {
                $content = json_decode($response->getContent(), true);
                
                // Should have consistent structure
                $this->assertIsArray($content);
                $this->assertArrayHasKey('data', $content);
                $this->assertIsArray($content['data']);
                
                // Should have pagination
                $this->assertPaginationStructure($content);
                
                // Each client should have consistent structure
                foreach ($content['data'] as $clientData) {
                    $this->assertClientDataStructure($clientData);
                }
            }
        }
    }
    
    /**
     * Test filtering by user role (if accessible)
     */
    public function testRoleBasedFiltering(): void
    {
        // Create clients for different contexts
        $client1 = $this->createTestClient(['nom' => 'Enterprise', 'prenom' => 'Client']);
        
        $userTypes = [
            'sadm' => AuthenticationTestHelper::createSuperAdminToken(),
            'adb' => AuthenticationTestHelper::createBoutiqueAdminToken(),
            'regular' => AuthenticationTestHelper::createRegularUserToken()
        ];
        
        foreach ($userTypes as $userType => $token) {
            $headers = AuthenticationTestHelper::createAuthHeaders($token);
            
            $this->client->request('GET', ApiClientTestConfig::ENDPOINT_LIST_BY_ROLE, [], [], $headers);
            $response = $this->client->getResponse();
            
            // Skip if subscription required
            if ($response->getStatusCode() === 403) {
                $content = json_decode($response->getContent(), true);
                if (isset($content['message']) && strpos($content['message'], 'Abonnement') !== false) {
                    continue;
                }
            }
            
            if ($response->getStatusCode() === 200) {
                $content = json_decode($response->getContent(), true);
                
                // Verify response structure
                $this->assertIsArray($content);
                $this->assertArrayHasKey('data', $content);
                
                // Verify ordering (should be by id ASC)
                $clients = $content['data'];
                if (count($clients) > 1) {
                    for ($i = 1; $i < count($clients); $i++) {
                        $this->assertGreaterThanOrEqual(
                            $clients[$i-1]['id'],
                            $clients[$i]['id'],
                            'Clients should be ordered by id in ascending order'
                        );
                    }
                }
            }
        }
    }
    
    /**
     * Test empty result scenarios
     */
    public function testEmptyResultScenarios(): void
    {
        // Don't create any clients, test empty results
        $token = AuthenticationTestHelper::createSuperAdminToken();
        $headers = AuthenticationTestHelper::createAuthHeaders($token);
        
        $this->client->request('GET', ApiClientTestConfig::ENDPOINT_LIST_ALL, [], [], $headers);
        $response = $this->client->getResponse();
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content);
        $this->assertArrayHasKey('data', $content);
        $this->assertIsArray($content['data']);
        
        // Empty results should still have pagination structure
        $this->assertPaginationStructure($content);
        
        $pagination = $content['pagination'];
        $this->assertEquals(0, $pagination['total']);
    }
    
    /**
     * Test large dataset handling
     */
    public function testLargeDatasetHandling(): void
    {
        // Create many clients to test performance
        for ($i = 1; $i <= 50; $i++) {
            $this->createTestClient([
                'nom' => "BulkClient{$i}",
                'prenom' => "Test{$i}",
                'numero' => "+225 " . str_pad($i, 10, '0', STR_PAD_LEFT)
            ]);
        }
        
        $token = AuthenticationTestHelper::createSuperAdminToken();
        $headers = AuthenticationTestHelper::createAuthHeaders($token);
        
        $startTime = microtime(true);
        
        $this->client->request('GET', ApiClientTestConfig::ENDPOINT_LIST_ALL, [], [], $headers);
        $response = $this->client->getResponse();
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        $this->assertEquals(200, $response->getStatusCode());
        
        // Should complete within reasonable time (5 seconds)
        $this->assertLessThan(5.0, $executionTime, 'Large dataset listing should complete within 5 seconds');
        
        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content);
        $this->assertArrayHasKey('data', $content);
        
        // Should have pagination
        $this->assertPaginationStructure($content);
        
        $pagination = $content['pagination'];
        $this->assertGreaterThanOrEqual(50, $pagination['total']);
    }
}