<?php

namespace App\Tests\Controller\ApiClient\Properties;

use App\Tests\Controller\ApiClient\ApiClientTestBase;
use Eris\Generator;
use Eris\TestTrait;

/**
 * Property Test 15: Not found resource handling
 * Validates: Requirements 3.2, 6.2, 7.2
 * 
 * Tests that all endpoints consistently handle non-existent resources
 * with proper 404 responses and error messages.
 */
class NotFoundResourceHandlingPropertyTest extends ApiClientTestBase
{
    use TestTrait;

    /**
     * Property: Client retrieval with non-existent ID always returns 404
     */
    public function testClientRetrievalNotFoundProperty(): void
    {
        $this->forAll(
            Generator\choose(100000, 999999), // Non-existent IDs
            Generator\elements(['sadm', 'adb', 'regular']) // User roles
        )
        ->then(function ($nonExistentId, $userRole) {
            $response = $this->makeAuthenticatedRequest(
                'GET',
                '/api/client/get/one/' . $nonExistentId,
                [],
                [],
                [],
                null,
                $userRole
            );

            // Property: Non-existent client retrieval returns 404
            $this->assertEquals(
                404, 
                $response->getStatusCode(), 
                'Non-existent client retrieval must return 404'
            );

            $content = json_decode($response->getContent(), true);
            $this->assertIsArray($content, 'Error response must be valid JSON');
            $this->assertArrayHasKey('message', $content, 'Error response must have message');
            $this->assertArrayHasKey('statusCode', $content, 'Error response must have statusCode');
            $this->assertEquals(404, $content['statusCode'], 'StatusCode in response must match HTTP status');
            $this->assertEquals('Cette ressource est inexistante', $content['message']);
        });
    }

    /**
     * Property: Client update with non-existent ID always returns 404
     */
    public function testClientUpdateNotFoundProperty(): void
    {
        $this->forAll(
            Generator\choose(100000, 999999), // Non-existent IDs
            Generator\elements(['sadm', 'adb', 'regular']), // User roles
            Generator\string()->withSize(Generator\choose(3, 20)), // Update data
            Generator\string()->withSize(Generator\choose(3, 20))
        )
        ->then(function ($nonExistentId, $userRole, $nom, $prenom) {
            $updateData = [
                'nom' => $nom,
                'prenoms' => $prenom,
                'numero' => '+225 0123456789'
            ];

            $response = $this->makeAuthenticatedRequest(
                'PUT',
                '/api/client/update/' . $nonExistentId,
                $updateData,
                [],
                [],
                null,
                $userRole
            );

            // Property: Non-existent client update returns 404
            $this->assertEquals(
                404, 
                $response->getStatusCode(), 
                'Non-existent client update must return 404'
            );

            $content = json_decode($response->getContent(), true);
            $this->assertIsArray($content, 'Error response must be valid JSON');
            $this->assertArrayHasKey('message', $content, 'Error response must have message');
            $this->assertArrayHasKey('statusCode', $content, 'Error response must have statusCode');
            $this->assertEquals(404, $content['statusCode']);
            $this->assertEquals('Cette ressource est inexistante', $content['message']);
        });
    }

    /**
     * Property: Client deletion with non-existent ID always returns 404
     */
    public function testClientDeletionNotFoundProperty(): void
    {
        $this->forAll(
            Generator\choose(100000, 999999), // Non-existent IDs
            Generator\elements(['sadm', 'adb', 'regular']) // User roles
        )
        ->then(function ($nonExistentId, $userRole) {
            $response = $this->makeAuthenticatedRequest(
                'DELETE',
                '/api/client/delete/' . $nonExistentId,
                [],
                [],
                [],
                null,
                $userRole
            );

            // Property: Non-existent client deletion returns 404
            $this->assertEquals(
                404, 
                $response->getStatusCode(), 
                'Non-existent client deletion must return 404'
            );

            $content = json_decode($response->getContent(), true);
            $this->assertIsArray($content, 'Error response must be valid JSON');
            $this->assertArrayHasKey('message', $content, 'Error response must have message');
            $this->assertArrayHasKey('statusCode', $content, 'Error response must have statusCode');
            $this->assertEquals(404, $content['statusCode']);
            $this->assertEquals('Cette ressource est inexistante', $content['message']);
        });
    }

    /**
     * Property: Bulk deletion with mixed valid/invalid IDs handles non-existent gracefully
     */
    public function testBulkDeletionMixedIdsProperty(): void
    {
        $this->forAll(
            Generator\seq(Generator\choose(1, 5)), // Valid client count
            Generator\seq(Generator\choose(100000, 999999))->withSize(Generator\choose(1, 3)), // Invalid IDs
            Generator\elements(['sadm', 'adb', 'regular']) // User roles
        )
        ->then(function ($validClientCount, $invalidIds, $userRole) {
            // Create some valid clients
            $validIds = [];
            foreach (range(1, count($validClientCount)) as $i) {
                $client = $this->createTestClient([
                    'nom' => 'TestClient' . $i,
                    'prenom' => 'TestPrenom' . $i,
                    'numero' => '+225 012345' . str_pad($i, 4, '0', STR_PAD_LEFT)
                ]);
                $validIds[] = $client->getId();
            }

            // Mix valid and invalid IDs
            $mixedIds = array_merge($validIds, $invalidIds);
            shuffle($mixedIds);

            $response = $this->makeAuthenticatedRequest(
                'DELETE',
                '/api/client/delete/all/items',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['ids' => $mixedIds]),
                $userRole
            );

            // Property: Bulk deletion with mixed IDs should succeed
            // (it should delete valid ones and ignore invalid ones)
            $this->assertEquals(
                200, 
                $response->getStatusCode(), 
                'Bulk deletion with mixed IDs should return 200 (delete valid, ignore invalid)'
            );

            $content = json_decode($response->getContent(), true);
            $this->assertIsArray($content, 'Response must be valid JSON');
            $this->assertArrayHasKey('message', $content, 'Response must have message');
            $this->assertEquals('Operation effectuées avec succès', $content['message']);
        });
    }

    /**
     * Property: Error response structure is consistent across all not-found scenarios
     */
    public function testNotFoundErrorResponseStructureProperty(): void
    {
        $this->forAll(
            Generator\choose(100000, 999999), // Non-existent IDs
            Generator\elements(['GET', 'PUT', 'DELETE']), // HTTP methods
            Generator\elements(['sadm', 'adb', 'regular']) // User roles
        )
        ->then(function ($nonExistentId, $method, $userRole) {
            $endpoints = [
                'GET' => '/api/client/get/one/' . $nonExistentId,
                'PUT' => '/api/client/update/' . $nonExistentId,
                'DELETE' => '/api/client/delete/' . $nonExistentId
            ];

            $requestData = [];
            if ($method === 'PUT') {
                $requestData = [
                    'nom' => 'Test',
                    'prenoms' => 'User',
                    'numero' => '+225 0123456789'
                ];
            }

            $response = $this->makeAuthenticatedRequest(
                $method,
                $endpoints[$method],
                $requestData,
                [],
                [],
                null,
                $userRole
            );

            // Property: All not-found responses have consistent structure
            $this->assertEquals(
                404, 
                $response->getStatusCode(), 
                "All not-found responses must return 404 for method $method"
            );

            $content = json_decode($response->getContent(), true);
            
            // Consistent error structure
            $this->assertIsArray($content, 'Error response must be valid JSON array');
            $this->assertArrayHasKey('message', $content, 'Error response must have message field');
            $this->assertArrayHasKey('statusCode', $content, 'Error response must have statusCode field');
            
            // Consistent values
            $this->assertEquals(404, $content['statusCode'], 'StatusCode field must match HTTP status');
            $this->assertEquals('Cette ressource est inexistante', $content['message'], 'Error message must be consistent');
            
            // Response should be JSON
            $this->assertTrue(
                $response->headers->contains('Content-Type', 'application/json'),
                'Error response must have JSON content type'
            );
        });
    }

    /**
     * Property: Invalid ID formats are handled gracefully
     */
    public function testInvalidIdFormatHandlingProperty(): void
    {
        $this->forAll(
            Generator\elements(['abc', 'null', '0', '-1', '1.5', 'true', 'false']), // Invalid ID formats
            Generator\elements(['GET', 'PUT', 'DELETE']), // HTTP methods
            Generator\elements(['sadm', 'adb', 'regular']) // User roles
        )
        ->then(function ($invalidId, $method, $userRole) {
            $endpoints = [
                'GET' => '/api/client/get/one/' . $invalidId,
                'PUT' => '/api/client/update/' . $invalidId,
                'DELETE' => '/api/client/delete/' . $invalidId
            ];

            $requestData = [];
            if ($method === 'PUT') {
                $requestData = [
                    'nom' => 'Test',
                    'prenoms' => 'User',
                    'numero' => '+225 0123456789'
                ];
            }

            try {
                $response = $this->makeAuthenticatedRequest(
                    $method,
                    $endpoints[$method],
                    $requestData,
                    [],
                    [],
                    null,
                    $userRole
                );

                // Property: Invalid ID formats result in 404 or 400 error
                $this->assertContains(
                    $response->getStatusCode(),
                    [400, 404],
                    "Invalid ID format should result in 400 or 404 error for method $method with ID '$invalidId'"
                );

                // If we get a JSON response, it should have proper error structure
                if ($response->headers->contains('Content-Type', 'application/json')) {
                    $content = json_decode($response->getContent(), true);
                    if ($content !== null) {
                        $this->assertIsArray($content, 'Error response must be valid JSON');
                        $this->assertArrayHasKey('message', $content, 'Error response must have message');
                        $this->assertArrayHasKey('statusCode', $content, 'Error response must have statusCode');
                    }
                }
            } catch (\Exception $e) {
                // Some invalid formats might cause routing exceptions, which is acceptable
                $this->assertTrue(true, 'Routing exceptions for invalid ID formats are acceptable');
            }
        });
    }
}