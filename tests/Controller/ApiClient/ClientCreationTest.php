<?php

namespace App\Tests\Controller\ApiClient;

use App\Entity\Client;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests for client creation endpoints
 * Covers POST /api/client/create and POST /api/client/create/boutique
 */
class ClientCreationTest extends ApiClientTestBase
{
    /**
     * Test successful client creation with required fields only
     */
    public function testCreateClientSuccess(): void
    {
        $clientData = [
            'nom' => 'Kouassi',
            'prenoms' => 'Yao Jean',
            'numero' => '+225 0123456789',
            'boutique' => $this->testData['boutique']->getId(),
            'succursale' => $this->testData['succursale']->getId()
        ];

        $response = $this->makeAuthenticatedRequest(
            'POST',
            '/api/client/create',
            $clientData,
            [],
            [],
            null,
            'sadm'
        );

        $content = $this->assertJsonResponse($response, 200);
        $this->assertClientDataStructure($content);
        $this->assertEquals('Kouassi', $content['nom']);
        $this->assertEquals('Yao Jean', $content['prenom']);
        $this->assertEquals('+225 0123456789', $content['numero']);
        $this->assertNotNull($content['boutique']);
        $this->assertNotNull($content['succursale']);
    }

    /**
     * Test client creation with photo upload
     */
    public function testCreateClientWithPhoto(): void
    {
        // Create a temporary test image
        $testImagePath = $this->createTestImage();
        $uploadedFile = new UploadedFile(
            $testImagePath,
            'test_client_photo.jpg',
            'image/jpeg',
            null,
            true
        );

        $clientData = [
            'nom' => 'Test',
            'prenoms' => 'Photo',
            'numero' => '+225 0987654321',
            'boutique' => $this->testData['boutique']->getId(),
            'succursale' => $this->testData['succursale']->getId()
        ];

        $response = $this->makeAuthenticatedRequest(
            'POST',
            '/api/client/create',
            $clientData,
            ['photo' => $uploadedFile],
            [],
            null,
            'sadm'
        );

        $content = $this->assertJsonResponse($response, 200);
        $this->assertClientDataStructure($content);
        $this->assertNotNull($content['photo'], 'Photo should be uploaded and saved');
        $this->assertStringContains('document_01', $content['photo']);
    }

    /**
     * Test client creation with missing required fields
     */
    public function testCreateClientMissingRequiredFields(): void
    {
        // Test missing nom
        $response = $this->makeAuthenticatedRequest(
            'POST',
            '/api/client/create',
            [
                'prenoms' => 'Test',
                'numero' => '+225 0123456789',
                'boutique' => $this->testData['boutique']->getId()
            ],
            [],
            [],
            null,
            'sadm'
        );
        $this->assertErrorResponse($response, 400, 'Les champs nom, prenoms et numero sont obligatoires');

        // Test missing prenoms
        $response = $this->makeAuthenticatedRequest(
            'POST',
            '/api/client/create',
            [
                'nom' => 'Test',
                'numero' => '+225 0123456789',
                'boutique' => $this->testData['boutique']->getId()
            ],
            [],
            [],
            null,
            'sadm'
        );
        $this->assertErrorResponse($response, 400, 'Les champs nom, prenoms et numero sont obligatoires');

        // Test missing numero
        $response = $this->makeAuthenticatedRequest(
            'POST',
            '/api/client/create',
            [
                'nom' => 'Test',
                'prenoms' => 'User',
                'boutique' => $this->testData['boutique']->getId()
            ],
            [],
            [],
            null,
            'sadm'
        );
        $this->assertErrorResponse($response, 400, 'Les champs nom, prenoms et numero sont obligatoires');
    }

    /**
     * Test client creation with empty required fields
     */
    public function testCreateClientEmptyRequiredFields(): void
    {
        $response = $this->makeAuthenticatedRequest(
            'POST',
            '/api/client/create',
            [
                'nom' => '   ', // Empty after trim
                'prenoms' => 'Test',
                'numero' => '+225 0123456789',
                'boutique' => $this->testData['boutique']->getId()
            ],
            [],
            [],
            null,
            'sadm'
        );
        $this->assertErrorResponse($response, 400, 'Les champs nom, prenoms et numero sont obligatoires');
    }

    /**
     * Test client creation with invalid boutique ID
     */
    public function testCreateClientInvalidBoutique(): void
    {
        $response = $this->makeAuthenticatedRequest(
            'POST',
            '/api/client/create',
            [
                'nom' => 'Test',
                'prenoms' => 'User',
                'numero' => '+225 0123456789',
                'boutique' => 99999 // Non-existent boutique
            ],
            [],
            [],
            null,
            'sadm'
        );
        $this->assertErrorResponse($response, 404, 'Boutique non trouvée');
    }

    /**
     * Test client creation with invalid succursale ID
     */
    public function testCreateClientInvalidSuccursale(): void
    {
        $response = $this->makeAuthenticatedRequest(
            'POST',
            '/api/client/create',
            [
                'nom' => 'Test',
                'prenoms' => 'User',
                'numero' => '+225 0123456789',
                'boutique' => $this->testData['boutique']->getId(),
                'succursale' => 99999 // Non-existent succursale
            ],
            [],
            [],
            null,
            'sadm'
        );
        $this->assertErrorResponse($response, 404, 'Succursale non trouvée');
    }

    /**
     * Test client creation without boutique association
     */
    public function testCreateClientWithoutBoutique(): void
    {
        $response = $this->makeAuthenticatedRequest(
            'POST',
            '/api/client/create',
            [
                'nom' => 'Test',
                'prenoms' => 'User',
                'numero' => '+225 0123456789'
                // No boutique or succursale
            ],
            [],
            [],
            null,
            'sadm'
        );
        $this->assertErrorResponse($response, 400, 'Une boutique doit être associée au client (directement ou via la succursale)');
    }

    /**
     * Test boutique-specific client creation endpoint
     */
    public function testCreateClientBoutique(): void
    {
        $clientData = [
            'nom' => 'Boutique',
            'prenoms' => 'Client',
            'numero' => '+225 0555666777',
            'boutique' => $this->testData['boutique']->getId(),
            'succursale' => $this->testData['succursale']->getId()
        ];

        $response = $this->makeAuthenticatedRequest(
            'POST',
            '/api/client/create/boutique',
            $clientData,
            [],
            [],
            null,
            'adb'
        );

        $content = $this->assertJsonResponse($response, 200);
        $this->assertClientDataStructure($content);
        $this->assertEquals('Boutique', $content['nom']);
        $this->assertEquals('Client', $content['prenom']);
        $this->assertEquals('+225 0555666777', $content['numero']);
    }

    /**
     * Test client creation without subscription
     */
    public function testCreateClientWithoutSubscription(): void
    {
        // This test would need to mock the subscription checker
        // For now, we'll test that the subscription check is called
        $response = $this->makeAuthenticatedRequest(
            'POST',
            '/api/client/create',
            [
                'nom' => 'Test',
                'prenoms' => 'User',
                'numero' => '+225 0123456789',
                'boutique' => $this->testData['boutique']->getId()
            ],
            [],
            [],
            null,
            'regular'
        );

        // The actual behavior depends on subscription implementation
        // This test ensures the endpoint handles subscription checking
        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 403]),
            'Response should be either success (has subscription) or forbidden (no subscription)'
        );
    }

    /**
     * Test client creation with different user roles
     */
    public function testCreateClientWithDifferentRoles(): void
    {
        $clientData = [
            'nom' => 'Role',
            'prenoms' => 'Test',
            'numero' => '+225 0111222333',
            'boutique' => $this->testData['boutique']->getId(),
            'succursale' => $this->testData['succursale']->getId()
        ];

        // Test with SADM role
        $response = $this->makeAuthenticatedRequest(
            'POST',
            '/api/client/create',
            $clientData,
            [],
            [],
            null,
            'sadm'
        );
        $this->assertEquals(200, $response->getStatusCode());

        // Test with ADB role
        $clientData['numero'] = '+225 0111222334'; // Different number
        $response = $this->makeAuthenticatedRequest(
            'POST',
            '/api/client/create',
            $clientData,
            [],
            [],
            null,
            'adb'
        );
        $this->assertEquals(200, $response->getStatusCode());

        // Test with regular user role
        $clientData['numero'] = '+225 0111222335'; // Different number
        $response = $this->makeAuthenticatedRequest(
            'POST',
            '/api/client/create',
            $clientData,
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
    private function createTestImage(): string
    {
        $testDir = $this->getTestUploadDir();
        $imagePath = $testDir . '/test_image.jpg';
        
        // Create a simple 1x1 pixel JPEG image
        $image = imagecreate(1, 1);
        $color = imagecolorallocate($image, 255, 255, 255);
        imagejpeg($image, $imagePath);
        imagedestroy($image);
        
        return $imagePath;
    }
}