<?php

namespace App\Tests\Controller\ApiClient\Debug;

use App\Tests\Controller\ApiClient\ApiClientTestBase;
use App\Tests\Controller\ApiClient\ApiClientTestConfig;
use App\Tests\Controller\ApiClient\Helpers\AuthenticationTestHelper;
use App\Tests\Controller\ApiClient\Helpers\FileUploadTestHelper;

/**
 * Debug test for client creation issues
 */
class ClientCreationDebugTest extends ApiClientTestBase
{
    /**
     * Test client creation with minimal data to debug the 500 error
     */
    public function testClientCreationDebug(): void
    {
        // Create test data similar to the failing request
        $clientData = [
            'nom' => 'ateliya',
            'prenoms' => 'Hamed',
            'numero' => '2250704314164',
            'succursale' => $this->testData['succursale']->getId()
            // Note: boutique is empty in the original request
        ];
        
        $token = AuthenticationTestHelper::createSuperAdminToken();
        $headers = AuthenticationTestHelper::createMultipartAuthHeaders($token);
        
        $this->client->request(
            'POST',
            ApiClientTestConfig::ENDPOINT_CREATE,
            $clientData,
            [],
            $headers
        );
        
        $response = $this->client->getResponse();
        
        // Debug output
        echo "\n=== DEBUG CLIENT CREATION ===\n";
        echo "Status Code: " . $response->getStatusCode() . "\n";
        echo "Response Content: " . $response->getContent() . "\n";
        echo "Headers: " . print_r($response->headers->all(), true) . "\n";
        
        // If it's a 500 error, let's see what the actual error is
        if ($response->getStatusCode() === 500) {
            $content = json_decode($response->getContent(), true);
            if ($content && isset($content['message'])) {
                echo "Error Message: " . $content['message'] . "\n";
            }
        }
        
        // For now, just assert that we get a response (not necessarily successful)
        $this->assertNotNull($response);
    }
    
    /**
     * Test client creation with boutique specified
     */
    public function testClientCreationWithBoutique(): void
    {
        $clientData = [
            'nom' => 'ateliya',
            'prenoms' => 'Hamed',
            'numero' => '2250704314164',
            'boutique' => $this->testData['boutique']->getId(),
            'succursale' => $this->testData['succursale']->getId()
        ];
        
        $token = AuthenticationTestHelper::createSuperAdminToken();
        $headers = AuthenticationTestHelper::createMultipartAuthHeaders($token);
        
        $this->client->request(
            'POST',
            ApiClientTestConfig::ENDPOINT_CREATE,
            $clientData,
            [],
            $headers
        );
        
        $response = $this->client->getResponse();
        
        echo "\n=== DEBUG CLIENT CREATION WITH BOUTIQUE ===\n";
        echo "Status Code: " . $response->getStatusCode() . "\n";
        echo "Response Content: " . $response->getContent() . "\n";
        
        if ($response->getStatusCode() === 500) {
            $content = json_decode($response->getContent(), true);
            if ($content && isset($content['message'])) {
                echo "Error Message: " . $content['message'] . "\n";
            }
        }
        
        $this->assertNotNull($response);
    }
    
    /**
     * Test client creation with photo
     */
    public function testClientCreationWithPhoto(): void
    {
        $clientData = [
            'nom' => 'ateliya',
            'prenoms' => 'Hamed',
            'numero' => '2250704314164',
            'boutique' => $this->testData['boutique']->getId(),
            'succursale' => $this->testData['succursale']->getId()
        ];
        
        $photo = FileUploadTestHelper::createValidImageFile('test_client.jpg');
        
        $token = AuthenticationTestHelper::createSuperAdminToken();
        $headers = AuthenticationTestHelper::createMultipartAuthHeaders($token);
        
        $this->client->request(
            'POST',
            ApiClientTestConfig::ENDPOINT_CREATE,
            $clientData,
            ['photo' => $photo],
            $headers
        );
        
        $response = $this->client->getResponse();
        
        echo "\n=== DEBUG CLIENT CREATION WITH PHOTO ===\n";
        echo "Status Code: " . $response->getStatusCode() . "\n";
        echo "Response Content: " . $response->getContent() . "\n";
        
        if ($response->getStatusCode() === 500) {
            $content = json_decode($response->getContent(), true);
            if ($content && isset($content['message'])) {
                echo "Error Message: " . $content['message'] . "\n";
            }
        }
        
        $this->assertNotNull($response);
    }
    
    /**
     * Test the entities setup
     */
    public function testEntitiesSetup(): void
    {
        echo "\n=== DEBUG ENTITIES SETUP ===\n";
        echo "Entreprise ID: " . $this->testData['entreprise']->getId() . "\n";
        echo "Boutique ID: " . $this->testData['boutique']->getId() . "\n";
        echo "Succursale ID: " . $this->testData['succursale']->getId() . "\n";
        echo "SADM User ID: " . $this->testData['users']['sadm']->getId() . "\n";
        
        $this->assertNotNull($this->testData['entreprise']);
        $this->assertNotNull($this->testData['boutique']);
        $this->assertNotNull($this->testData['succursale']);
        $this->assertNotNull($this->testData['users']['sadm']);
    }
}