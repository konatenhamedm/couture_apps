<?php

namespace App\Tests\Controller\ApiClient\Properties;

use App\Tests\Controller\ApiClient\ApiClientTestBase;
use Eris\Generator;
use Eris\TestTrait;

/**
 * Property Test 10: Bulk deletion operations
 * Validates: Requirements 7.1, 7.2, 7.3
 * 
 * Tests that bulk deletion operations maintain consistency and handle
 * edge cases properly across different scenarios.
 */
class BulkDeletionPropertyTest extends ApiClientTestBase
{
    use TestTrait;

    /**
     * Property: Bulk deletion is atomic - either all valid deletions succeed or operation fails gracefully
     */
    public function testBulkDeletionAtomicityProperty(): void
    {
        $this->forAll(
            Generator\choose(2, 10), // Number of clients to create
            Generator\elements(['sadm', 'adb', 'regular']), // User roles
            Generator\choose(0, 3) // Number of invalid IDs to add
        )
        ->then(function ($clientCount, $userRole, $invalidIdCount) {
            // Create valid clients
            $validClients = [];
            $validIds = [];
            
            for ($i = 1; $i <= $clientCount; $i++) {
                $client = $this->createTestClient([
                    'nom' => 'BulkTest' . $i,
                    'prenom' => 'Client' . $i,
                    'numero' => '+225 012345' . str_pad($i, 4, '0', STR_PAD_LEFT)
                ]);
                $validClients[] = $client;
                $validIds[] = $client->getId();
            }

            // Add some invalid IDs
            $invalidIds = [];
            for ($i = 1; $i <= $invalidIdCount; $i++) {
                $invalidIds[] = 100000 + $i;
            }

            $allIds = array_merge($validIds, $invalidIds);
            shuffle($allIds); // Randomize order

            // Perform bulk deletion
            $response = $this->makeAuthenticatedRequest(
                'DELETE',
                '/api/client/delete/all/items',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['ids' => $allIds]),
                $userRole
            );

            // Property: Bulk deletion should succeed (delete valid, ignore invalid)
            $this->assertEquals(
                200, 
                $response->getStatusCode(), 
                'Bulk deletion should succeed even with mixed valid/invalid IDs'
            );

            $content = json_decode($response->getContent(), true);
            $this->assertIsArray($content, 'Response must be valid JSON');
            $this->assertArrayHasKey('message', $content);
            $this->assertEquals('Operation effectuées avec succès', $content['message']);

            // Property: All valid clients should be deleted
            foreach ($validIds as $validId) {
                $getResponse = $this->makeAuthenticatedRequest(
                    'GET',
                    '/api/client/get/one/' . $validId,
                    [],
                    [],
                    [],
                    null,
                    $userRole
                );
                
                $this->assertEquals(
                    404, 
                    $getResponse->getStatusCode(), 
                    "Valid client ID $validId should be deleted after bulk operation"
                );
            }
        });
    }

    /**
     * Property: Single deletion is idempotent
     */
    public function testSingleDeletionIdempotencyProperty(): void
    {
        $this->forAll(
            Generator\choose(1, 30), // Multiple iterations
            Generator\elements(['sadm', 'adb', 'regular']) // User roles
        )
        ->then(function ($iteration, $userRole) {
            // Create a client
            $client = $this->createTestClient([
                'nom' => 'IdempotentDelete' . $iteration,
                'prenom' => 'Test' . $iteration,
                'numero' => '+225 012345' . str_pad($iteration, 4, '0', STR_PAD_LEFT)
            ]);

            $clientId = $client->getId();

            // First deletion should succeed
            $firstResponse = $this->makeAuthenticatedRequest(
                'DELETE',
                '/api/client/delete/' . $clientId,
                [],
                [],
                [],
                null,
                $userRole
            );

            $this->assertEquals(
                200, 
                $firstResponse->getStatusCode(), 
                'First deletion attempt must succeed'
            );

            $firstContent = json_decode($firstResponse->getContent(), true);
            $this->assertEquals('Operation effectuées avec succès', $firstContent['message']);

            // Second deletion should return 404 (resource not found)
            $secondResponse = $this->makeAuthenticatedRequest(
                'DELETE',
                '/api/client/delete/' . $clientId,
                [],
                [],
                [],
                null,
                $userRole
            );

            // Property: Subsequent deletions return consistent error
            $this->assertEquals(
                404, 
                $secondResponse->getStatusCode(), 
                'Subsequent deletion attempts must return 404 (already deleted)'
            );

            $secondContent = json_decode($secondResponse->getContent(), true);
            $this->assertArrayHasKey('message', $secondContent);
            $this->assertEquals('Cette ressource est inexistante', $secondContent['message']);
        });
    }

    /**
     * Property: Bulk deletion handles edge cases gracefully
     */
    public function testBulkDeletionEdgeCasesProperty(): void
    {
        $this->forAll(
            Generator\elements(['sadm', 'adb', 'regular']), // User roles
            Generator\oneof(
                Generator\constant([]), // Empty array
                Generator\seq(Generator\choose(100000, 999999))->withSize(Generator\choose(1, 5)), // All invalid IDs
                Generator\seq(Generator\choose(-1000, -1))->withSize(Generator\choose(1, 3)) // Negative IDs
            )
        )
        ->then(function ($userRole, $edgeCaseIds) {
            $response = $this->makeAuthenticatedRequest(
                'DELETE',
                '/api/client/delete/all/items',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['ids' => $edgeCaseIds]),
                $userRole
            );

            // Property: Edge cases should be handled gracefully
            $this->assertContains(
                $response->getStatusCode(),
                [200, 400],
                'Edge cases in bulk deletion should return 200 (success) or 400 (bad request)'
            );

            $content = json_decode($response->getContent(), true);
            $this->assertIsArray($content, 'Response must be valid JSON');
            $this->assertArrayHasKey('message', $content, 'Response must have message field');
            $this->assertArrayHasKey('statusCode', $content, 'Response must have statusCode field');
        });
    }

    /**
     * Property: Deletion operations maintain referential integrity
     */
    public function testDeletionReferentialIntegrityProperty(): void
    {
        $this->forAll(
            Generator\choose(2, 5), // Number of clients to create
            Generator\elements(['sadm', 'adb', 'regular']) // User roles
        )
        ->then(function ($clientCount, $userRole) {
            // Create multiple clients with same boutique/succursale
            $clients = [];
            $clientIds = [];
            
            for ($i = 1; $i <= $clientCount; $i++) {
                $client = $this->createTestClient([
                    'nom' => 'IntegrityTest' . $i,
                    'prenom' => 'Client' . $i,
                    'numero' => '+225 012345' . str_pad($i, 4, '0', STR_PAD_LEFT)
                ]);
                $clients[] = $client;
                $clientIds[] = $client->getId();
            }

            // Delete the first client
            $deletedClientId = $clientIds[0];
            $remainingClientIds = array_slice($clientIds, 1);

            $deleteResponse = $this->makeAuthenticatedRequest(
                'DELETE',
                '/api/client/delete/' . $deletedClientId,
                [],
                [],
                [],
                null,
                $userRole
            );

            $this->assertEquals(200, $deleteResponse->getStatusCode());

            // Property: Remaining clients should still be accessible
            foreach ($remainingClientIds as $remainingId) {
                $getResponse = $this->makeAuthenticatedRequest(
                    'GET',
                    '/api/client/get/one/' . $remainingId,
                    [],
                    [],
                    [],
                    null,
                    $userRole
                );

                $this->assertEquals(
                    200, 
                    $getResponse->getStatusCode(), 
                    "Remaining client ID $remainingId must still be accessible after deletion of another client"
                );

                $content = json_decode($getResponse->getContent(), true);
                $this->assertArrayHasKey('data', $content);
                $this->assertEquals($remainingId, $content['data']['id']);
            }

            // Property: Deleted client should be inaccessible
            $getDeletedResponse = $this->makeAuthenticatedRequest(
                'GET',
                '/api/client/get/one/' . $deletedClientId,
                [],
                [],
                [],
                null,
                $userRole
            );

            $this->assertEquals(
                404, 
                $getDeletedResponse->getStatusCode(), 
                'Deleted client must be inaccessible'
            );
        });
    }
}