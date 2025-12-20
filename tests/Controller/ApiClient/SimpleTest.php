<?php

namespace App\Tests\Controller\ApiClient;

use PHPUnit\Framework\TestCase;
use App\Tests\Controller\ApiClient\Helpers\AuthenticationTestHelper;

/**
 * Simple test to verify our test infrastructure works
 */
class SimpleTest extends TestCase
{
    /**
     * Test that our authentication helper works
     */
    public function testAuthenticationHelperWorks(): void
    {
        $token = AuthenticationTestHelper::createSuperAdminToken();
        
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        
        // Verify token structure
        $this->assertTrue(AuthenticationTestHelper::verifyTokenStructure($token));
        
        // Decode and verify payload
        $payload = AuthenticationTestHelper::decodeToken($token);
        
        $this->assertEquals('SADM', $payload['user_type']);
        $this->assertTrue($payload['has_subscription']);
    }
    
    /**
     * Test that our file upload helper works
     */
    public function testFileUploadHelperWorks(): void
    {
        $file = \App\Tests\Controller\ApiClient\Helpers\FileUploadTestHelper::createValidImageFile();
        
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\File\UploadedFile::class, $file);
        $this->assertEquals('image/jpeg', $file->getMimeType());
        $this->assertGreaterThan(0, $file->getSize());
        
        // Cleanup
        \App\Tests\Controller\ApiClient\Helpers\FileUploadTestHelper::cleanupTestFiles();
    }
    
    /**
     * Test that our client data factory works
     */
    public function testClientDataFactoryWorks(): void
    {
        $clientData = \App\Tests\Controller\ApiClient\Factories\ClientTestDataFactory::createValidClientData();
        
        $this->assertIsArray($clientData);
        $this->assertArrayHasKey('nom', $clientData);
        $this->assertArrayHasKey('prenoms', $clientData);
        $this->assertArrayHasKey('numero', $clientData);
        
        $this->assertNotEmpty($clientData['nom']);
        $this->assertNotEmpty($clientData['prenoms']);
        $this->assertNotEmpty($clientData['numero']);
    }
}