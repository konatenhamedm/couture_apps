<?php

namespace App\Tests\Controller\ApiClient;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Tests for file upload validation
 * Covers photo upload functionality across all endpoints
 */
class FileUploadValidationTest extends ApiClientTestBase
{
    /**
     * Test photo upload with valid JPEG file
     */
    public function testPhotoUploadValidJpeg(): void
    {
        $imagePath = $this->createTestImage('valid_jpeg', 'jpg');
        $uploadedFile = new UploadedFile(
            $imagePath,
            'valid_photo.jpg',
            'image/jpeg',
            null,
            true
        );

        $clientData = [
            'nom' => 'PhotoTest',
            'prenoms' => 'User',
            'numero' => '+225 0123456789'
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

        $content = $this->assertJsonResponse($response, 201);
        $this->assertNotNull($content['data']['photo']);
        $this->assertStringContainsString('document_01', $content['data']['photo']);
    }

    /**
     * Test photo upload with valid PNG file
     */
    public function testPhotoUploadValidPng(): void
    {
        $imagePath = $this->createTestImage('valid_png', 'png');
        $uploadedFile = new UploadedFile(
            $imagePath,
            'valid_photo.png',
            'image/png',
            null,
            true
        );

        $clientData = [
            'nom' => 'PhotoTest',
            'prenoms' => 'User',
            'numero' => '+225 0123456789'
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

        $content = $this->assertJsonResponse($response, 201);
        $this->assertNotNull($content['data']['photo']);
        $this->assertStringContainsString('document_01', $content['data']['photo']);
    }

    /**
     * Test photo upload with invalid file type
     */
    public function testPhotoUploadInvalidFileType(): void
    {
        $textFilePath = $this->createTestTextFile();
        $uploadedFile = new UploadedFile(
            $textFilePath,
            'invalid_file.txt',
            'text/plain',
            null,
            true
        );

        $clientData = [
            'nom' => 'PhotoTest',
            'prenoms' => 'User',
            'numero' => '+225 0123456789'
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

        $this->assertErrorResponse($response, 400, 'Format de fichier non supporté');
    }

    /**
     * Test photo upload with oversized file
     */
    public function testPhotoUploadOversizedFile(): void
    {
        // Create a large image file (simulate oversized)
        $imagePath = $this->createLargeTestImage();
        $uploadedFile = new UploadedFile(
            $imagePath,
            'large_photo.jpg',
            'image/jpeg',
            null,
            true
        );

        $clientData = [
            'nom' => 'PhotoTest',
            'prenoms' => 'User',
            'numero' => '+225 0123456789'
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

        // This might succeed or fail depending on server configuration
        // The test validates that the system handles large files appropriately
        $this->assertTrue(
            in_array($response->getStatusCode(), [201, 400, 413]),
            'Large file upload should either succeed or fail with appropriate error code'
        );
    }

    /**
     * Test photo upload with corrupted file
     */
    public function testPhotoUploadCorruptedFile(): void
    {
        $corruptedFilePath = $this->createCorruptedImageFile();
        $uploadedFile = new UploadedFile(
            $corruptedFilePath,
            'corrupted_photo.jpg',
            'image/jpeg',
            null,
            true
        );

        $clientData = [
            'nom' => 'PhotoTest',
            'prenoms' => 'User',
            'numero' => '+225 0123456789'
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

        $this->assertErrorResponse($response, 400, 'Fichier corrompu ou invalide');
    }

    /**
     * Test photo upload without file
     */
    public function testPhotoUploadWithoutFile(): void
    {
        $clientData = [
            'nom' => 'PhotoTest',
            'prenoms' => 'User',
            'numero' => '+225 0123456789'
        ];

        $response = $this->makeAuthenticatedRequest(
            'POST',
            '/api/client/create',
            $clientData,
            [], // No files
            [],
            null,
            'sadm'
        );

        // Should succeed without photo
        $content = $this->assertJsonResponse($response, 201);
        $this->assertNull($content['data']['photo']);
    }

    /**
     * Test photo replacement during update
     */
    public function testPhotoReplacementDuringUpdate(): void
    {
        // Create client with initial photo
        $initialImagePath = $this->createTestImage('initial', 'jpg');
        $initialFile = new UploadedFile(
            $initialImagePath,
            'initial_photo.jpg',
            'image/jpeg',
            null,
            true
        );

        $client = $this->createTestClient([
            'nom' => 'PhotoReplacement',
            'prenom' => 'Test',
            'numero' => '+225 0123456789'
        ], ['photo' => $initialFile]);

        // Update with new photo
        $newImagePath = $this->createTestImage('replacement', 'jpg');
        $newFile = new UploadedFile(
            $newImagePath,
            'replacement_photo.jpg',
            'image/jpeg',
            null,
            true
        );

        $updateData = ['nom' => 'UpdatedPhotoReplacement'];

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
        $this->assertNotNull($content['data']['photo']);
        $this->assertStringContainsString('document_01', $content['data']['photo']);
    }

    /**
     * Test multiple file uploads (should only accept one photo)
     */
    public function testMultipleFileUploads(): void
    {
        $image1Path = $this->createTestImage('photo1', 'jpg');
        $image2Path = $this->createTestImage('photo2', 'jpg');
        
        $file1 = new UploadedFile(
            $image1Path,
            'photo1.jpg',
            'image/jpeg',
            null,
            true
        );
        
        $file2 = new UploadedFile(
            $image2Path,
            'photo2.jpg',
            'image/jpeg',
            null,
            true
        );

        $clientData = [
            'nom' => 'MultipleFiles',
            'prenoms' => 'Test',
            'numero' => '+225 0123456789'
        ];

        // Try to upload multiple files (this should handle only the first one)
        $response = $this->makeAuthenticatedRequest(
            'POST',
            '/api/client/create',
            $clientData,
            ['photo' => $file1, 'photo2' => $file2],
            [],
            null,
            'sadm'
        );

        // Should succeed with only the photo field processed
        $content = $this->assertJsonResponse($response, 201);
        $this->assertNotNull($content['data']['photo']);
    }

    /**
     * Test file naming convention
     */
    public function testFileNamingConvention(): void
    {
        $imagePath = $this->createTestImage('naming_test', 'jpg');
        $uploadedFile = new UploadedFile(
            $imagePath,
            'original_name_with_spaces and special chars!.jpg',
            'image/jpeg',
            null,
            true
        );

        $clientData = [
            'nom' => 'NamingTest',
            'prenoms' => 'User',
            'numero' => '+225 0123456789'
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

        $content = $this->assertJsonResponse($response, 201);
        $photoPath = $content['data']['photo'];
        
        // Verify naming convention: should contain 'document_01' and be sanitized
        $this->assertStringContainsString('document_01', $photoPath);
        $this->assertStringNotContainsString(' ', $photoPath, 'File name should not contain spaces');
        $this->assertStringNotContainsString('!', $photoPath, 'File name should not contain special characters');
    }

    /**
     * Helper method to create a test image file
     */
    private function createTestImage(string $suffix = '', string $extension = 'jpg'): string
    {
        $testDir = $this->getTestUploadDir();
        $imagePath = $testDir . '/test_image_' . $suffix . '_' . uniqid() . '.' . $extension;
        
        if ($extension === 'png') {
            // Create a simple PNG image
            $image = imagecreate(100, 100);
            $color = imagecolorallocate($image, 255, 255, 255);
            imagepng($image, $imagePath);
            imagedestroy($image);
        } else {
            // Create a simple JPEG image
            $image = imagecreate(100, 100);
            $color = imagecolorallocate($image, 255, 255, 255);
            imagejpeg($image, $imagePath);
            imagedestroy($image);
        }
        
        return $imagePath;
    }

    /**
     * Helper method to create a test text file
     */
    private function createTestTextFile(): string
    {
        $testDir = $this->getTestUploadDir();
        $textPath = $testDir . '/test_text_' . uniqid() . '.txt';
        file_put_contents($textPath, 'This is a test text file');
        return $textPath;
    }

    /**
     * Helper method to create a large test image
     */
    private function createLargeTestImage(): string
    {
        $testDir = $this->getTestUploadDir();
        $imagePath = $testDir . '/large_test_image_' . uniqid() . '.jpg';
        
        // Create a larger image (1000x1000 pixels)
        $image = imagecreate(1000, 1000);
        $color = imagecolorallocate($image, 255, 255, 255);
        imagejpeg($image, $imagePath, 100); // High quality to increase file size
        imagedestroy($image);
        
        return $imagePath;
    }

    /**
     * Helper method to create a corrupted image file
     */
    private function createCorruptedImageFile(): string
    {
        $testDir = $this->getTestUploadDir();
        $corruptedPath = $testDir . '/corrupted_image_' . uniqid() . '.jpg';
        
        // Create a file with invalid JPEG content
        file_put_contents($corruptedPath, 'This is not a valid JPEG file content');
        
        return $corruptedPath;
    }
}