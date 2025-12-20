<?php

namespace App\Tests\Controller\ApiClient;

/**
 * Tests for client deletion endpoints
 * Covers DELETE /api/client/delete/{id} and DELETE /api/client/delete/all/items
 */
class ClientDeletionTest extends ApiClientTestBase
{
    /**
     * Test successful single client deletion
     */
    public function testDeleteClientSuccess(): void
    {
        // Create a test client
        $client = $this->createTestClient([
            'nom' => 'ToDelete',
            'prenom' => 'Client',
            'numero' => '+225 0123456789'
        ]);

        $clientId = $client->getId();

        // Delete the client
        $response = $this->makeAuthenticatedRequest(
            'DELETE',
            '/api/client/delete/' . $clientId,
            [],
            [],
            [],
            null,
            'sadm'
        );

        $content = $this->assertJsonResponse($response, 200);
        $this->assertEquals('Operation effectuées avec succès', $content['message']);

        // Verify the client is deleted (should return 404)
        $getResponse = $this->makeAuthenticatedRequest(
            'GET',
            '/api/client/get/one/' . $clientId,
            [],
            [],
            [],
            null,
            'sadm'
        );
        $this->assertEquals(404, $getResponse->getStatusCode());
    }

    /**
     * Test deletion of non-existent client
     */
    public function testDeleteClientNotFound(): void
    {
        $nonExistentId = 99999;

        $response = $this->makeAuthenticatedRequest(
            'DELETE',
            '/api/client/delete/' . $nonExistentId,
            [],
            [],
            [],
            null,
            'sadm'
        );

        $this->assertErrorResponse($response, 404, 'Cette ressource est inexistante');
    }

    /**
     * Test bulk client deletion with valid IDs
     */
    public function testBulkDeleteClientsSuccess(): void
    {
        // Create multiple test clients
        $clients = [];
        for ($i = 1; $i <= 3; $i++) {
            $clients[] = $this->createTestClient([
                'nom' => 'BulkDelete' . $i,
                'prenom' => 'Client' . $i,
                'numero' => '+225 012345678' . $i
            ]);
        }

        $clientIds = array_map(fn($client) => $client->getId(), $clients);

        // Bulk delete the clients
        $response = $this->makeAuthenticatedRequest(
            'DELETE',
            '/api/client/delete/all/items',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['ids' => $clientIds]),
            'sadm'
        );

        $content = $this->assertJsonResponse($response, 200);
        $this->assertEquals('Operation effectuées avec succès', $content['message']);

        // Verify all clients are deleted
        foreach ($clientIds as $clientId) {
            $getResponse = $this->makeAuthenticatedRequest(
                'GET',
                '/api/client/get/one/' . $clientId,
                [],
                [],
                [],
                null,
                'sadm'
            );
            $this->assertEquals(404, $getResponse->getStatusCode());
        }
    }

    /**
     * Test bulk deletion with mixed valid and invalid IDs
     */
    public function testBulkDeleteMixedIds(): void
    {
        // Create some valid clients
        $validClients = [];
        for ($i = 1; $i <= 2; $i++) {
            $validClients[] = $this->createTestClient([
                'nom' => 'MixedDelete' . $i,
                'prenom' => 'Client' . $i,
                'numero' => '+225 012345678' . $i
            ]);
        }

        $validIds = array_map(fn($client) => $client->getId(), $validClients);
        $invalidIds = [99998, 99999];
        $mixedIds = array_merge($validIds, $invalidIds);

        // Bulk delete with mixed IDs
        $response = $this->makeAuthenticatedRequest(
            'DELETE',
            '/api/client/delete/all/items',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['ids' => $mixedIds]),
            'sadm'
        );

        // Should succeed (delete valid ones, ignore invalid ones)
        $content = $this->assertJsonResponse($response, 200);
        $this->assertEquals('Operation effectuées avec succès', $content['message']);

        // Verify valid clients are deleted
        foreach ($validIds as $clientId) {
            $getResponse = $this->makeAuthenticatedRequest(
                'GET',
                '/api/client/get/one/' . $clientId,
                [],
                [],
                [],
                null,
                'sadm'
            );
            $this->assertEquals(404, $getResponse->getStatusCode());
        }
    }

    /**
     * Test bulk deletion with empty IDs array
     */
    public function testBulkDeleteEmptyIds(): void
    {
        $response = $this->makeAuthenticatedRequest(
            'DELETE',
            '/api/client/delete/all/items',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['ids' => []]),
            'sadm'
        );

        // Should handle empty array gracefully
        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 400]),
            'Empty IDs array should be handled gracefully'
        );
    }

    /**
     * Test client deletion with different user roles
     */
    public function testDeleteClientWithDifferentRoles(): void
    {
        // Test with SADM role
        $client1 = $this->createTestClient([
            'nom' => 'SADMDelete',
            'prenom' => 'Test',
            'numero' => '+225 0123456781'
        ]);

        $response = $this->makeAuthenticatedRequest(
            'DELETE',
            '/api/client/delete/' . $client1->getId(),
            [],
            [],
            [],
            null,
            'sadm'
        );
        $this->assertEquals(200, $response->getStatusCode());

        // Test with ADB role
        $client2 = $this->createTestClient([
            'nom' => 'ADBDelete',
            'prenom' => 'Test',
            'numero' => '+225 0123456782'
        ]);

        $response = $this->makeAuthenticatedRequest(
            'DELETE',
            '/api/client/delete/' . $client2->getId(),
            [],
            [],
            [],
            null,
            'adb'
        );
        $this->assertEquals(200, $response->getStatusCode());

        // Test with regular user role
        $client3 = $this->createTestClient([
            'nom' => 'RegularDelete',
            'prenom' => 'Test',
            'numero' => '+225 0123456783'
        ]);

        $response = $this->makeAuthenticatedRequest(
            'DELETE',
            '/api/client/delete/' . $client3->getId(),
            [],
            [],
            [],
            null,
            'regular'
        );
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test concurrent deletion attempts
     */
    public function testConcurrentDeletion(): void
    {
        $client = $this->createTestClient([
            'nom' => 'ConcurrentDelete',
            'prenom' => 'Test',
            'numero' => '+225 0123456789'
        ]);

        // First deletion should succeed
        $response1 = $this->makeAuthenticatedRequest(
            'DELETE',
            '/api/client/delete/' . $client->getId(),
            [],
            [],
            [],
            null,
            'sadm'
        );
        $this->assertEquals(200, $response1->getStatusCode());

        // Second deletion should return 404 (already deleted)
        $response2 = $this->makeAuthenticatedRequest(
            'DELETE',
            '/api/client/delete/' . $client->getId(),
            [],
            [],
            [],
            null,
            'sadm'
        );
        $this->assertEquals(404, $response2->getStatusCode());
    }
}