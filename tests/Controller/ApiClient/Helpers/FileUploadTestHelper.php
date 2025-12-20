<?php

namespace App\Tests\Controller\ApiClient\Helpers;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Helper class for managing file uploads in API Client tests
 */
class FileUploadTestHelper
{
    private static array $createdFiles = [];
    
    /**
     * Create a valid image file for testing
     */
    public static function createValidImageFile(string $filename = 'test_image.jpg', int $width = 100, int $height = 100): UploadedFile
    {
        $tempFile = self::createTempImageFile($filename, $width, $height, 'jpeg');
        
        return new UploadedFile(
            $tempFile,
            $filename,
            'image/jpeg',
            null,
            true // Mark as test file
        );
    }
    
    /**
     * Create a valid PNG image file for testing
     */
    public static function createValidPngFile(string $filename = 'test_image.png', int $width = 100, int $height = 100): UploadedFile
    {
        $tempFile = self::createTempImageFile($filename, $width, $height, 'png');
        
        return new UploadedFile(
            $tempFile,
            $filename,
            'image/png',
            null,
            true
        );
    }
    
    /**
     * Create an invalid image file (wrong format)
     */
    public static function createInvalidImageFile(string $filename = 'test_file.txt'): UploadedFile
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_invalid_');
        file_put_contents($tempFile, 'This is not an image file');
        
        self::$createdFiles[] = $tempFile;
        
        return new UploadedFile(
            $tempFile,
            $filename,
            'text/plain',
            null,
            true
        );
    }
    
    /**
     * Create a large image file for testing size limits
     */
    public static function createLargeImageFile(string $filename = 'large_image.jpg', int $width = 2000, int $height = 2000): UploadedFile
    {
        $tempFile = self::createTempImageFile($filename, $width, $height, 'jpeg');
        
        return new UploadedFile(
            $tempFile,
            $filename,
            'image/jpeg',
            null,
            true
        );
    }
    
    /**
     * Create an empty file
     */
    public static function createEmptyFile(string $filename = 'empty.jpg'): UploadedFile
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_empty_');
        // Create empty file
        touch($tempFile);
        
        self::$createdFiles[] = $tempFile;
        
        return new UploadedFile(
            $tempFile,
            $filename,
            'image/jpeg',
            null,
            true
        );
    }
    
    /**
     * Create a file with invalid extension but valid image content
     */
    public static function createMislabeledImageFile(string $filename = 'image.exe'): UploadedFile
    {
        $tempFile = self::createTempImageFile($filename, 100, 100, 'jpeg');
        
        return new UploadedFile(
            $tempFile,
            $filename,
            'application/octet-stream',
            null,
            true
        );
    }
    
    /**
     * Create multiple valid image files
     */
    public static function createMultipleValidImages(int $count = 3): array
    {
        $files = [];
        for ($i = 1; $i <= $count; $i++) {
            $files[] = self::createValidImageFile("test_image_{$i}.jpg");
        }
        return $files;
    }
    
    /**
     * Create a corrupted image file
     */
    public static function createCorruptedImageFile(string $filename = 'corrupted.jpg'): UploadedFile
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_corrupted_');
        
        // Write invalid JPEG header followed by random data
        $corruptedData = "\xFF\xD8\xFF\xE0" . str_repeat('X', 1000);
        file_put_contents($tempFile, $corruptedData);
        
        self::$createdFiles[] = $tempFile;
        
        return new UploadedFile(
            $tempFile,
            $filename,
            'image/jpeg',
            null,
            true
        );
    }
    
    /**
     * Create a temporary image file
     */
    private static function createTempImageFile(string $filename, int $width, int $height, string $format): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_image_');
        
        // Create a simple colored image
        $image = imagecreate($width, $height);
        
        // Allocate colors
        $backgroundColor = imagecolorallocate($image, 255, 255, 255); // White
        $textColor = imagecolorallocate($image, 0, 0, 0); // Black
        
        // Add some text to make it a valid image
        imagestring($image, 5, 10, 10, 'TEST', $textColor);
        
        // Save the image
        switch ($format) {
            case 'jpeg':
                imagejpeg($image, $tempFile, 90);
                break;
            case 'png':
                imagepng($image, $tempFile);
                break;
            case 'gif':
                imagegif($image, $tempFile);
                break;
            default:
                throw new \InvalidArgumentException("Unsupported format: {$format}");
        }
        
        imagedestroy($image);
        
        self::$createdFiles[] = $tempFile;
        
        return $tempFile;
    }
    
    /**
     * Get file size in bytes
     */
    public static function getFileSize(UploadedFile $file): int
    {
        return $file->getSize();
    }
    
    /**
     * Verify file is a valid image
     */
    public static function isValidImage(UploadedFile $file): bool
    {
        $imageInfo = getimagesize($file->getPathname());
        return $imageInfo !== false;
    }
    
    /**
     * Get image dimensions
     */
    public static function getImageDimensions(UploadedFile $file): array
    {
        $imageInfo = getimagesize($file->getPathname());
        if ($imageInfo === false) {
            return ['width' => 0, 'height' => 0];
        }
        
        return [
            'width' => $imageInfo[0],
            'height' => $imageInfo[1]
        ];
    }
    
    /**
     * Create test upload directory
     */
    public static function createTestUploadDirectory(): string
    {
        $dir = sys_get_temp_dir() . '/test_uploads_' . uniqid();
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        return $dir;
    }
    
    /**
     * Clean up all created test files
     */
    public static function cleanupTestFiles(): void
    {
        foreach (self::$createdFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        self::$createdFiles = [];
    }
    
    /**
     * Generate random file content for testing
     */
    public static function generateRandomFileContent(int $sizeInBytes): string
    {
        return random_bytes($sizeInBytes);
    }
    
    /**
     * Create file with specific size
     */
    public static function createFileWithSize(int $sizeInBytes, string $filename = 'sized_file.jpg'): UploadedFile
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_sized_');
        
        // Create JPEG header
        $jpegHeader = "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x01\x00H\x00H\x00\x00";
        $content = $jpegHeader . str_repeat('X', max(0, $sizeInBytes - strlen($jpegHeader)));
        
        file_put_contents($tempFile, $content);
        
        self::$createdFiles[] = $tempFile;
        
        return new UploadedFile(
            $tempFile,
            $filename,
            'image/jpeg',
            null,
            true
        );
    }
    
    /**
     * Validate file naming convention
     */
    public static function validateFileNamingConvention(string $filename, string $expectedPrefix = 'document_01'): bool
    {
        // Check if filename starts with expected prefix
        return strpos($filename, $expectedPrefix) === 0;
    }
}