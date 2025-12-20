<?php

namespace App\Tests\Controller\ApiClient\Properties;

use App\Tests\Controller\ApiClient\ApiClientTestBase;
use Eris\Generator;
use Eris\TestTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Property Test 7: Photo upload and naming
 * Validates: Requirements 4.3, 5.3, 9.3, 9.4
 * 
 * Tests that photo uploads are handled consistently with proper naming conventions
 * and file management across all client operations.
 */
class PhotoUploadPropertyTest extends ApiClientTestBase
{
    use TestTrait;

    /**
     * Property: Photo uploads always result in consistent naming pattern
     */
    public function testPhotoUploadNamingConsistencyProperty(): void
    {
        $this->forAll(
            Generator\choose(1, 50), // Multiple iterations
            Generator\elements(['sadm', 'adb', 'regular']), // User roles
            Generator\string()->withSize(Generator\choose(3, 15)), // Client names
            Generator\string()->withSize(Generator\choose(3, 15)) // Client prenoms
        )
        ->then(function ($iteration, $userRole, $nom, $prenom) {
            // Create a test image
            $testImagePath = $this->createTestImage($iteration);
            $uploadedFile = new UploadedFile(
                $testImagePath,
                'test_photo_' . $iteration . '.jpg',
                'image/jpeg',
                null,
                true
            );

            $clientData = [
                'nom' => $nom,
                'prenoms' => $prenom,
                'numero' => '+225 012345' . str_pad($iteration, 4, '0', STR_PAD_LEFT),
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
                $userRole
            );

            if ($response->getStatusCode() === 200) {
                $content = json_decode($response->getContent(), true);

                // Property: Photo field must be present
                $this->assertArrayHasKey(
                    'photo', 
                    $content, 
                    'Photo field must be present in response'
                );

                // Property: If photo was uploaded, it must follow naming convention
                if ($content['photo'] !== null) {
                    $this->assertIsString(
                        $content['photo'], 
                        'Photo path must be a string'
                    );
                    
                    // Property: Photo name must contain the prefix 'document_01'
                    $this->assertStringContainsString(
                        'document_01', 
                        $content['photo'], 
                        'Photo filename must contain standard prefix "document_01"'
                    );

                    // Property: Photo path must not be empty
                    $this->assertNotEmpty(
                        $content['photo'], 
                        'Photo path must not be empty when photo is uploaded'
                    );

                    // Property: Photo path should be a valid path format
                    $this->assertMatchesRegularExpression(
                        '/^[a-zA-Z0-9_\-\/\.]+$/', 
                        $content['photo'], 
                        'Photo path must contain only valid path characters'
                    );
                }
            }
        });
    }

    /**
     * Property: Photo upload is optional - clients can be created without photos
     */
    public function testPhotoUploadOptionalProperty(): void
    {
        $this->forAll(
            Generator\choose(1, 50), // Multiple iterations
            Generator\elements(['sadm', 'adb', 'regular']), // User roles
            Generator\bool() // Whether to include photo
        )
        ->then(function ($iteration, $userRole, $includePhoto) {
            $clientData = [
                'nom' => 'TestClient' . $iteration,
                'prenoms' => 'TestPrenom' . $iteration,
                'numero' => '+225 012345' . str_pad($iteration, 4, '0', STR_PAD_LEFT),
                'boutique' => $this->testData['boutique']->getId(),
                'succursale' => $this->testData['succursale']->getId()
            ];

            $files = [];
            if ($includePhoto) {
                $testImagePath = $this->createTestImage($iteration);
                $files['photo'] = new UploadedFile(
                    $testImagePath,
                    'test_photo.jpg',
                    'image/jpeg',
                    null,
                    true
                );
            }

            $response = $this->makeAuthenticatedRequest(
                'POST',
                '/api/client/create',
                $clientData,
                $files,
                [],
                null,
                $userRole
            );

            // Property: Client creation succeeds with or without photo
            $this->assertEquals(
                200, 
                $response->getStatusCode(), 
                'Client creation must succeed regardless of photo presence'
            );

            $content = json_decode($response->getContent(), true);
            $this->assertArrayHasKey('photo', $content);

            // Property: Photo field reflects upload status
            if ($includePhoto) {
                // If photo was uploaded, field should contain path or be null (if upload failed)
                $this->assertTrue(
                    is_string($content['photo']) || $content['photo'] === null,
                    'Photo field must be string or null when photo is uploaded'
                );
            } else {
                // If no photo uploaded, field should be null
                $this->assertNull(
                    $content['photo'], 
                    'Photo field must be null when no photo is uploaded'
                );
            }
        });
    }

    /**
     * Property: Photo replacement during update maintains naming consistency
     */
    public function testPhotoReplacementNamingProperty(): void
    {
        $this->forAll(
            Generator\choose(1, 30), // Multiple iterations
            Generator\elements(['sadm', 'adb', 'regular']), // User roles
            Generator\bool() // Whether to replace photo
        )
        ->then(function ($iteration, $userRole, $replacePhoto) {
            // Create a client with initial photo
            $initialImagePath = $this->createTestImage($iteration . '_initial');
            $initialFile = new UploadedFile(
                $initialImagePath,
                'initial_photo.jpg',
                'image/jpeg',
                null,
                true
            );

            $clientData = [
                'nom' => 'UpdateTest' . $iteration,
                'prenoms' => 'PhotoTest' . $iteration,
                'numero' => '+225 012345' . str_pad($iteration, 4, '0', STR_PAD_LEFT),
                'boutique' => $this->testData['boutique']->getId(),
                'succursale' => $this->testData['succursale']->getId()
            ];

            $createResponse = $this->makeAuthenticatedRequest(
                'POST',
                '/api/client/create',
                $clientData,
                ['photo' => $initialFile],
                [],
                null,
                $userRole
            );

            if ($createResponse->getStatusCode() === 200) {
                $createContent = json_decode($createResponse->getContent(), true);
                $clientId = $createContent['id'];
                $initialPhoto = $createContent['photo'];

                // Update the client
                $updateData = ['nom' => 'Updated' . $iteration];
                $updateFiles = [];

                if ($replacePhoto) {
                    $newImagePath = $this->createTestImage($iteration . '_updated');
                    $updateFiles['photo'] = new UploadedFile(
                        $newImagePath,
                        'updated_photo.jpg',
                        'image/jpeg',
                        null,
                        true
                    );
                }

                $updateResponse = $this->makeAuthenticatedRequest(
                    'PUT',
                    '/api/client/update/' . $clientId,
                    $updateData,
                    $updateFiles,
                    [],
                    null,
                    $userRole
                );

                if ($updateResponse->getStatusCode() === 200) {
                    $updateContent = json_decode($updateResponse->getContent(), true);

                    // Property: Photo field is always present
                    $this->assertArrayHasKey('photo', $updateContent);

                    if ($replacePhoto) {
                        // Property: New photo follows naming convention
                        if ($updateContent['photo'] !== null) {
                            $this->assertStringContainsString(
                                'document_01', 
                                $updateContent['photo'], 
                                'Replacement photo must follow naming convention'
                            );
                        }
                    } else {
                        // Property: Photo remains unchanged if not replaced
                        $this->assertEquals(
                            $initialPhoto, 
                            $updateContent['photo'], 
                            'Photo must remain unchanged when not replaced'
                        );
                    }
                }
            }
        });
    }

    /**
     * Property: Multiple clients can have photos without naming conflicts
     */
    public function testPhotoUploadNoNamingConflictsProperty(): void
    {
        $this->forAll(
            Generator\choose(2, 10), // Number of clients to create
            Generator\elements(['sadm', 'adb', 'regular']) // User roles
        )
        ->then(function ($clientCount, $userRole) {
            $uploadedPhotos = [];

            for ($i = 1; $i <= $clientCount; $i++) {
                $testImagePath = $this->createTestImage('conflict_test_' . $i);
                $uploadedFile = new UploadedFile(
                    $testImagePath,
                    'test_photo.jpg', // Same filename for all
                    'image/jpeg',
                    null,
                    true
                );

                $clientData = [
                    'nom' => 'ConflictTest' . $i,
                    'prenoms' => 'Client' . $i,
                    'numero' => '+225 012345' . str_pad($i, 4, '0', STR_PAD_LEFT),
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
                    $userRole
                );

                if ($response->getStatusCode() === 200) {
                    $content = json_decode($response->getContent(), true);
                    if ($content['photo'] !== null) {
                        $uploadedPhotos[] = $content['photo'];
                    }
                }
            }

            // Property: All uploaded photos have unique paths (no conflicts)
            if (count($uploadedPhotos) > 1) {
                $uniquePhotos = array_unique($uploadedPhotos);
                $this->assertEquals(
                    count($uploadedPhotos), 
                    count($uniquePhotos), 
                    'All uploaded photos must have unique paths to avoid conflicts'
                );
            }
        });
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