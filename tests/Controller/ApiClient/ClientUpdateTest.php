<?php

namespace App\Tests\Controller\ApiClient;

use App\Entity\Client;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Tests for client update endpoint
 * Covers PUT /api/client/update/{id}
 */
class ClientUpdateTest extends ApiClientTestBase
{
    /**
     * Test successful client update with basic fields
     */
    public function testUpdateClientSuccess(): void
    {
        // Create a test client first
        $client = $this->createTestClient([
            'nom' => 'Original',
            'prenom' => 'Name',
            'numero' => '+225 0123456789'
        ]);

        // Update the client
        $updateData = [
            'nom' => 'Updated',
            'prenoms' => 'Updated Name',
            'numero' => '+225 0987654321'
        ];

        $response = $this->makeAuthenticatedRequest(
            'PUT',
            '/api/client/update/' . $client->getId(),
            $updateData,
            [],
            [],
            null,
            'sadm'
        );

        $content = $this->assertJsonResponse($response, 200);
        
        // Verify the update was successful
        $this->assertEquals('Updated', $content['data']['nom']);
        $this->assertEquals('Updated Name', $content['data']['prenom']);
        $this->assertEquals('+225 0987654321', $content['data']['numero']);
        $this->assertEquals($client->getId(), $content['data']['id']);
    }

    /**
     * Test client update with photo replacement
     */
    public function testUpdateClientWithPhotoReplacement(): void
    {
        // Create a client with initial photo
        $initialImagePath = $this->createTestImage('initial');
        $initialFile = new UploadedFile(
            $initialImagePath,
            'initial_photo.jpg',
            'image/jpeg',
            null,
            true
        );

        $client = $this->createTestClient([
            'nom' => 'PhotoTest',
            'prenom' => 'User',
            'numero' => '+225 0123456789'
        ], ['photo' => $initialFile]);

        // Update with new photo
        $newImagePath = $this->createTestImage('updated');
        $newFile = new UploadedFile(
            $newImagePath,
            'updated_photo.jpg',
            'image/jpeg',
            null,
            true
        );

        $updateData = ['nom' => 'UpdatedPhotoTest'];

        $response = $this->makeAuthenticatedRequest(
            'PUT',
            '/api/client/update/' . $client->getId(),
            $updateData,
            ['photo' => $newFile],
            [],
            null,
            'sadm'
        );

        $content = $this->assertJsonResponse($response, 200);
        
        // Verify the update was successful
        $this->assertEquals('UpdatedPhotoTest', $content['data']['nom']);
        $this->assertNotNull($content['data']['photo']);
        $this->assertStringContainsString('document_01', $content['data']['photo']);
    }

    /**
     * Test client update with non-existent ID
     */
    public function testUpdateClientNotFound(): void
    {
        $nonExistentId = 99999;
        $updateData = [
            'nom' => 'Test',
            'prenoms' => 'User',
            'numero' => '+225 0123456789'
        ];

        $response = $this->makeAuthenticatedRequest(
            'PUT',
            '/api/client/update/' . $nonExistentId,
            $updateData,
            [],
            [],
            null,
            'sadm'
        );

        $this->assertErrorResponse($response, 404, 'Cette ressource est inexistante');
    }

    /**
     * Test client update with empty required fields
     */
    public function testUpdateClientEmptyRequiredFields(): void
    {
        $client = $this->createTestClient();

        // Try to update with empty nom
        $updateData = [
            'nom' => '   ', // Empty after trim
            'prenoms' => 'Valid Name'
        ];

        $response = $this->makeAuthenticatedRequest(
            'PUT',
            '/api/client/update/' . $client->getId(),
            $updateData,
            [],
            [],
            null,
            'sadm'
        );

        $this->assertErrorResponse($response, 400, 'Les champs nom, prenoms et numero sont obligatoires');
    }

    /**
     * Test partial client update (only some fields)
     */
    public function testUpdateClientPartialUpdate(): void
    {
        $client = $this->createTestClient([
            'nom' => 'Original',
            'prenom' => 'Name',
            'numero' => '+225 0123456789'
        ]);

        // Update only the nom field
        $updateData = ['nom' => 'PartiallyUpdated'];

        $response = $this->makeAuthenticatedRequest(
            'PUT',
            '/api/client/update/' . $client->getId(),
            $updateData,
            [],
            [],
            null,
            'sadm'
        );

        $content = $this->assertJsonResponse($response, 200);
        
        // Verify only nom was updated, other fields remain the same
        $this->assertEquals('PartiallyUpdated', $content['data']['nom']);
        $this->assertEquals('Name', $content['data']['prenom']);
        $this->assertEquals('+225 0123456789', $content['data']['numero']);
    }

    /**
     * Test client update with different user roles
     */
    public function testUpdateClientWithDifferentRoles(): void
    {
        $client = $this->createTestClient();
        $updateData = ['nom' => 'RoleTest'];

        // Test with SADM role
        $response = $this->makeAuthenticatedRequest(
            'PUT',
            '/api/client/update/' . $client->getId(),
            $updateData,
            [],
            [],
            null,
            'sadm'
        );
        $this->assertEquals(200, $response->getStatusCode());

        // Test with ADB role
        $updateData['nom'] = 'ADBRoleTest';
        $response = $this->makeAuthenticatedRequest(
            'PUT',
            '/api/client/update/' . $client->getId(),
            $updateData,
            [],
            [],
            null,
            'adb'
        );
        $this->assertEquals(200, $response->getStatusCode());

        // Test with regular user role
        $updateData['nom'] = 'RegularRoleTest';
        $response = $this->makeAuthenticatedRequest(
            'PUT',
            '/api/client/update/' . $client->getId(),
            $updateData,
            [],
            [],
            null,
            'regular'
        );
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Helper method to create a test image file
     */
    private function createTestImage(string $suffix = ''): string
    {
        $testDir = $this->getTestUploadDir();
        $imagePath = $testDir . '/test_image_' . $suffix . '_' . uniqid() . '.jpg';
        
        // Create a simple 1x1 pixel JPEG image
        $image = imagecreate(1, 1);
        $color = imagecolorallocate($image, 255, 255, 255);
        imagejpeg($image, $imagePath);
        imagedestroy($image);
        
        return $imagePath;
    }
}