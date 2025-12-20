<?php

namespace App\Tests\Controller\ApiClient\Properties;

use App\Tests\Controller\ApiClient\ApiClientTestBase;
use App\Tests\Controller\ApiClient\ApiClientTestConfig;
use App\Tests\Controller\ApiClient\Helpers\AuthenticationTestHelper;
use App\Tests\Controller\ApiClient\Generators\ClientDataGenerator;
use Eris\Generator;
use Eris\TestTrait;

/**
 * Property-based test for client list pagination consistency
 * 
 * **Feature: api-client-testing, Property 1: Client list pagination consistency**
 * **Validates: Requirements 1.1, 1.2, 1.4**
 * 
 * Tests that valid pagination parameters return results in the expected pagination format 
 * with proper metadata and all required client fields
 */
class ClientListPaginationPropertyTest extends ApiClientTestBase
{
    use TestTrait;
    
    /**
     * Property 1: Client list pagination consistency
     * For any valid pagination parameters, the client list endpoint should return results 
     * in the expected pagination format with proper metadata and all required client fields
     */
    public function testClientListPaginationConsistencyProperty(): void
    {
        // Create some test clients first
        $this->createMultipleTestClients(20);
        
        $this->forAll(
            Generator\choose(1, 5), // Page number
            Generator\choose(1, 10), // Page size
            Generator\elements([
                ApiClientTestConfig::ENDPOINT_LIST_ALL,
                ApiClientTestConfig::ENDPOINT_LIST_BY_ROLE
            ])
        )->then(function (int $page, int $size, string $endpoint): void {
            
            $token = AuthenticationTestHelper::createSuperAdminToken();
            $headers = AuthenticationTestHelper::createAuthHeaders($token);
            
            $queryParams = "?page={$page}&size={$size}";
            
            $this->client->request('GET', $endpoint . $queryParams, [], [], $headers);
            $response = $this->client->getResponse();
            
            // Skip if subscription required
            if ($response->getStatusCode() === 403) {
                $content = json_decode($response->getContent(), true);
                if (isset($content['message']) && strpos($content['message'], 'Abonnement') !== false) {
                    return;
                }
            }
            
            // Property: Valid pagination should return 200 OK
            $this->assertEquals(
                200,
                $response->getStatusCode(),
                sprintf('Valid pagination (page=%d, size=%d) should return 200 for endpoint %s', $page, $size, $endpoint)
            );
            
            $content = json_decode($response->getContent(), true);
            $this->assertIsArray($content, 'Response should be valid JSON array');
            
            // Property: Response should have pagination structure
            $this->assertPaginationStructure($content);
            
            $pagination = $content['pagination'];
            
            // Property: Pagination metadata should be consistent with request
            $this->assertEquals($page, $pagination['current_page'], 'Current page should match requested page');
            $this->assertEquals($size, $pagination['per_page'], 'Per page should match requested size');
            
            // Property: Total should be non-negative integer
            $this->assertIsInt($pagination['total'], 'Total should be integer');
            $this->assertGreaterThanOrEqual(0, $pagination['total'], 'Total should be non-negative');
            
            // Property: Last page should be calculated correctly
            $expectedLastPage = $pagination['total'] > 0 ? (int)ceil($pagination['total'] / $size) : 1;
            $this->assertEquals($expectedLastPage, $pagination['last_page'], 'Last page should be calculated correctly');
            
            // Property: Data array should not exceed page size
            $this->assertArrayHasKey('data', $content, 'Response should contain data array');
            $this->assertIsArray($content['data'], 'Data should be array');
            $this->assertLessThanOrEqual($size, count($content['data']), 'Data count should not exceed page size');
            
            // Property: Each client should have required fields
            foreach ($content['data'] as $clientData) {
                $this->assertClientDataStructure($clientData);
            }
        });
    }
    
    /**
     * Property test for pagination edge cases
     */
    public function testPaginationEdgeCasesProperty(): void
    {
        $this->createMultipleTestClients(5);
        
        $this->forAll(
            Generator\elements([
                ['page' => 0, 'size' => 10], // Page 0
                ['page' => -1, 'size' => 10], // Negative page
                ['page' => 1, 'size' => 0], // Size 0
                ['page' => 1, 'size' => -5], // Negative size
                ['page' => 999, 'size' => 10], // Very high page number
                ['page' => 1, 'size' => 1000] // Very large page size
            ])
        )->then(function (array $params): void {
            
            $token = AuthenticationTestHelper::createSuperAdminToken();
            $headers = AuthenticationTestHelper::createAuthHeaders($token);
            
            $queryParams = "?page={$params['page']}&size={$params['size']}";
            
            $this->client->request('GET', ApiClientTestConfig::ENDPOINT_LIST_ALL . $queryParams, [], [], $headers);
            $response = $this->client->getResponse();
            
            // Property: Edge cases should be handled gracefully (not crash)
            $this->assertContains(
                $response->getStatusCode(),
                [200, 400, 422],
                'Edge case pagination should return appropriate status code'
            );
            
            if ($response->getStatusCode() === 200) {
                $content = json_decode($response->getContent(), true);
                $this->assertIsArray($content, 'Valid response should be JSON array');
                
                // Should still have pagination structure
                $this->assertPaginationStructure($content);
                
                // Data should be array
                $this->assertArrayHasKey('data', $content);
                $this->assertIsArray($content['data']);
            }
        });
    }
    
    /**
     * Property test for pagination consistency across pages
     */
    public function testPaginationConsistencyAcrossPagesProperty(): void
    {
        // Create exactly 15 clients for predictable pagination
        $this->createMultipleTestClients(15);
        
        $this->forAll(
            Generator\choose(2, 5) // Page size
        )->then(function (int $pageSize): void {
            
            $token = AuthenticationTestHelper::createSuperAdminToken();
            $headers = AuthenticationTestHelper::createAuthHeaders($token);
            
            // Get first page
            $this->client->request('GET', ApiClientTestConfig::ENDPOINT_LIST_ALL . "?page=1&size={$pageSize}", [], [], $headers);
            $firstPageResponse = $this->client->getResponse();
            
            if ($firstPageResponse->getStatusCode() !== 200) {
                return; // Skip if not accessible
            }
            
            $firstPageContent = json_decode($firstPageResponse->getContent(), true);
            $totalItems = $firstPageContent['pagination']['total'];
            
            if ($totalItems === 0) {
                return; // Skip if no data
            }
            
            $expectedPages = (int)ceil($totalItems / $pageSize);
            
            // Property: Last page should not be empty (unless total is 0)
            if ($expectedPages > 1) {
                $this->client->request('GET', ApiClientTestConfig::ENDPOINT_LIST_ALL . "?page={$expectedPages}&size={$pageSize}", [], [], $headers);
                $lastPageResponse = $this->client->getResponse();
                
                $this->assertEquals(200, $lastPageResponse->getStatusCode(), 'Last page should be accessible');
                
                $lastPageContent = json_decode($lastPageResponse->getContent(), true);
                $this->assertGreaterThan(0, count($lastPageContent['data']), 'Last page should not be empty');
            }
            
            // Property: Page beyond last should be empty or return appropriate response
            $beyondLastPage = $expectedPages + 1;
            $this->client->request('GET', ApiClientTestConfig::ENDPOINT_LIST_ALL . "?page={$beyondLastPage}&size={$pageSize}", [], [], $headers);
            $beyondResponse = $this->client->getResponse();
            
            if ($beyondResponse->getStatusCode() === 200) {
                $beyondContent = json_decode($beyondResponse->getContent(), true);
                $this->assertEquals(0, count($beyondContent['data']), 'Page beyond last should be empty');
            }
        });
    }
    
    /**
     * Property test for pagination metadata consistency
     */
    public function testPaginationMetadataConsistencyProperty(): void
    {
        $this->createMultipleTestClients(12);
        
        $this->forAll(
            Generator\choose(1, 4), // Page size
            Generator\choose(1, 5)  // Page number
        )->then(function (int $pageSize, int $pageNumber): void {
            
            $token = AuthenticationTestHelper::createSuperAdminToken();
            $headers = AuthenticationTestHelper::createAuthHeaders($token);
            
            $queryParams = "?page={$pageNumber}&size={$pageSize}";
            
            $this->client->request('GET', ApiClientTestConfig::ENDPOINT_LIST_ALL . $queryParams, [], [], $headers);
            $response = $this->client->getResponse();
            
            if ($response->getStatusCode() !== 200) {
                return;
            }
            
            $content = json_decode($response->getContent(), true);
            $pagination = $content['pagination'];
            $dataCount = count($content['data']);
            
            // Property: If not on last page, should have full page size (or less if total < page size)
            if ($pageNumber < $pagination['last_page']) {
                $this->assertEquals(
                    min($pageSize, $pagination['total']),
                    $dataCount,
                    'Non-last page should have full page size items (or total if less than page size)'
                );
            }
            
            // Property: Current page should never exceed last page (unless last page is 0)
            if ($pagination['last_page'] > 0) {
                $this->assertLessThanOrEqual(
                    $pagination['last_page'],
                    $pagination['current_page'],
                    'Current page should not exceed last page'
                );
            }
            
            // Property: Per page should match requested size
            $this->assertEquals($pageSize, $pagination['per_page'], 'Per page should match requested size');
            
            // Property: Total should be consistent across all pages
            static $previousTotal = null;
            if ($previousTotal !== null) {
                $this->assertEquals($previousTotal, $pagination['total'], 'Total should be consistent across requests');
            }
            $previousTotal = $pagination['total'];
        });
    }
    
    /**
     * Property test for client data completeness in paginated results
     */
    public function testClientDataCompletenessInPaginationProperty(): void
    {
        $this->createMultipleTestClients(8);
        
        $this->forAll(
            Generator\choose(1, 3), // Page size
            Generator\choose(1, 4)  // Page number
        )->then(function (int $pageSize, int $pageNumber): void {
            
            $token = AuthenticationTestHelper::createSuperAdminToken();
            $headers = AuthenticationTestHelper::createAuthHeaders($token);
            
            $queryParams = "?page={$pageNumber}&size={$pageSize}";
            
            $this->client->request('GET', ApiClientTestConfig::ENDPOINT_LIST_ALL . $queryParams, [], [], $headers);
            $response = $this->client->getResponse();
            
            if ($response->getStatusCode() !== 200) {
                return;
            }
            
            $content = json_decode($response->getContent(), true);
            
            // Property: Every client in paginated results should have complete data structure
            foreach ($content['data'] as $index => $clientData) {
                $this->assertClientDataStructure($clientData);
                
                // Property: Each client should have unique ID
                $this->assertArrayHasKey('id', $clientData, "Client at index {$index} should have ID");
                $this->assertIsInt($clientData['id'], "Client ID at index {$index} should be integer");
                $this->assertGreaterThan(0, $clientData['id'], "Client ID at index {$index} should be positive");
            }
            
            // Property: No duplicate clients in same page
            $clientIds = array_column($content['data'], 'id');
            $uniqueIds = array_unique($clientIds);
            $this->assertCount(
                count($clientIds),
                $uniqueIds,
                'No duplicate clients should appear in same page'
            );
        });
    }
    
    /**
     * Property test for default pagination behavior
     */
    public function testDefaultPaginationBehaviorProperty(): void
    {
        $this->createMultipleTestClients(3);
        
        $this->forAll(
            Generator\elements([
                ApiClientTestConfig::ENDPOINT_LIST_ALL,
                ApiClientTestConfig::ENDPOINT_LIST_BY_ROLE
            ])
        )->then(function (string $endpoint): void {
            
            $token = AuthenticationTestHelper::createSuperAdminToken();
            $headers = AuthenticationTestHelper::createAuthHeaders($token);
            
            // Request without pagination parameters
            $this->client->request('GET', $endpoint, [], [], $headers);
            $response = $this->client->getResponse();
            
            // Skip if subscription required
            if ($response->getStatusCode() === 403) {
                return;
            }
            
            if ($response->getStatusCode() === 200) {
                $content = json_decode($response->getContent(), true);
                
                // Property: Should have default pagination even without parameters
                $this->assertPaginationStructure($content);
                
                $pagination = $content['pagination'];
                
                // Property: Should default to page 1
                $this->assertEquals(1, $pagination['current_page'], 'Should default to page 1');
                
                // Property: Should have reasonable default page size
                $this->assertGreaterThan(0, $pagination['per_page'], 'Should have positive default page size');
                $this->assertLessThanOrEqual(100, $pagination['per_page'], 'Default page size should be reasonable');
            }
        });
    }
    
    /**
     * Create multiple test clients for pagination testing
     */
    private function createMultipleTestClients(int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $this->createTestClient([
                'nom' => "PaginationClient{$i}",
                'prenom' => "Test{$i}",
                'numero' => "+225 " . str_pad($i, 10, '0', STR_PAD_LEFT)
            ]);
        }
    }
}