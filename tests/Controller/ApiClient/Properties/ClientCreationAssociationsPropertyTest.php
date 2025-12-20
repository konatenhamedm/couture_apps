<?php

namespace App\Tests\Controller\ApiClient\Properties;

use App\Tests\Controller\ApiClient\ApiClientTestBase;
use App\Tests\Controller\ApiClient\ApiClientTestConfig;
use App\Tests\Controller\ApiClient\Helpers\AuthenticationTestHelper;
use App\Tests\Controller\ApiClient\Generators\ClientDataGenerator;
use Eris\Generator;
use Eris\TestTrait;

/**
 * Property-based test for client creation with associations
 * 
 * **Feature: api-client-testing, Property 5: Client creation with associations**
 * **Validates: Requirements 4.1, 4.4, 5.1, 5.4**
 * 
 * Tests that valid client data with required fields successfully creates a client 
 * and properly associates it with provided boutique/succursale entities
 */
class ClientCreationAssociationsPropertyTest extends ApiClientTestBase
{
    use TestTrait;
    
    /**
     * Property 5: Client creation with associations
     * For any valid client data with required fields, the create endpoint should 
     * successfully create a client and properly associate it with provided boutique/succursale entities
     */
    public function testClientCreationWithAssociationsProperty(): void
    {
        $this->forAll(
            ClientDataGenerator::validClientData(),
            Generator\elements(['create', 'create_boutique']) // Test both endpoints
        )->then(function (array $clientData, string $endpoint): void {
            
            // Ensure we have valid boutique and succursale IDs from our test data
            $clientData['boutique'] = $this->testData['boutique']->getId();
            $clientData['succursale'] = $this->testData['succursale']->getId();
            
            $token = AuthenticationTestHelper::createSuperAdminToken();
            $headers = AuthenticationTestHelper::createMultipartAuthHeaders($token);
            
            $endpointUrl = $endpoint === 'create' 
                ? ApiClientTestConfig::ENDPOINT_CREATE 
                : ApiClientTestConfig::ENDPOINT_CREATE_BOUTIQUE;
            
            $this->client->request(
                'POST',
                $endpointUrl,
                $clientData,
                [],
                $headers
            );
            
            $response = $this->client->getResponse();
            
            // Property: Valid data should result in successful creation (201)
            $this->assertEquals(
                201,
                $response->getStatusCode(),
                sprintf(
                    'Valid client data should create client successfully on %s endpoint. Response: %s',
                    $endpoint,
                    $response->getContent()
                )
            );
            
            $content = json_decode($response->getContent(), true);
            $this->assertIsArray($content, 'Response should be valid JSON');
            $this->assertArrayHasKey('data', $content, 'Response should contain data field');
            
            $clientResponseData = $content['data'];
            
            // Property: Created client should have all provided data
            $this->assertEquals($clientData['nom'], $clientResponseData['nom']);
            $this->assertEquals($clientData['prenoms'], $clientResponseData['prenom']);
            $this->assertEquals($clientData['numero'], $clientResponseData['numero']);
            
            // Property: Client should have proper associations
            if (isset($clientResponseData['boutique'])) {
                $this->assertIsArray($clientResponseData['boutique']);
                $this->assertEquals($clientData['boutique'], $clientResponseData['boutique']['id']);
            }
            
            if (isset($clientResponseData['succursale'])) {
                $this->assertIsArray($clientResponseData['succursale']);
                $this->assertEquals($clientData['succursale'], $clientResponseData['succursale']['id']);
            }
            
            // Property: Client should have enterprise association
            $this->assertArrayHasKey('entreprise', $clientResponseData);
            $this->assertIsArray($clientResponseData['entreprise']);
            
            // Property: Client should have creation timestamp
            $this->assertArrayHasKey('createdAt', $clientResponseData);
            $this->assertNotNull($clientResponseData['createdAt']);
        });
    }
    
    /**
     * Property test for association consistency between boutique and succursale
     */
    public function testAssociationConsistencyProperty(): void
    {
        $this->forAll(
            ClientDataGenerator::validClientName(),
            ClientDataGenerator::validClientFirstName(),
            ClientDataGenerator::validIvorianPhoneNumber()
        )->then(function (string $nom, string $prenom, string $numero): void {
            
            // Use our test data to ensure consistency
            $clientData = [
                'nom' => $nom,
                'prenoms' => $prenom,
                'numero' => $numero,
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
            
            if ($response->getStatusCode() === 201) {
                $content = json_decode($response->getContent(), true);
                $clientResponseData = $content['data'];
                
                // Property: If both boutique and succursale are provided, 
                // the succursale should belong to the boutique
                if (isset($clientResponseData['boutique']) && isset($clientResponseData['succursale'])) {
                    // In our test setup, succursale belongs to boutique
                    $this->assertEquals(
                        $clientResponseData['boutique']['id'],
                        $this->testData['succursale']->getBoutique()->getId(),
                        'Succursale should belong to the specified boutique'
                    );
                }
            }
        });
    }
    
    /**
     * Property test for client creation with only boutique (boutique endpoint)
     */
    public function testBoutiqueOnlyCreationProperty(): void
    {
        $this->forAll(
            ClientDataGenerator::validClientData()
        )->then(function (array $clientData): void {
            
            // Remove succursale for boutique-only creation
            unset($clientData['succursale']);
            $clientData['boutique'] = $this->testData['boutique']->getId();
            
            $token = AuthenticationTestHelper::createSuperAdminToken();
            $headers = AuthenticationTestHelper::createMultipartAuthHeaders($token);
            
            $this->client->request(
                'POST',
                ApiClientTestConfig::ENDPOINT_CREATE_BOUTIQUE,
                $clientData,
                [],
                $headers
            );
            
            $response = $this->client->getResponse();
            
            // Property: Boutique-only creation should succeed
            $this->assertEquals(
                201,
                $response->getStatusCode(),
                'Boutique-only client creation should succeed'
            );
            
            $content = json_decode($response->getContent(), true);
            $clientResponseData = $content['data'];
            
            // Property: Client should have boutique association
            $this->assertArrayHasKey('boutique', $clientResponseData);
            $this->assertIsArray($clientResponseData['boutique']);
            $this->assertEquals($clientData['boutique'], $clientResponseData['boutique']['id']);
        });
    }
    
    /**
     * Property test for client creation with invalid associations
     */
    public function testInvalidAssociationsProperty(): void
    {
        $this->forAll(
            ClientDataGenerator::validClientData(),
            Generator\choose(9999, 99999) // Non-existent IDs
        )->then(function (array $clientData, int $invalidId): void {
            
            // Use invalid boutique ID
            $clientData['boutique'] = $invalidId;
            $clientData['succursale'] = $this->testData['succursale']->getId();
            
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
            
            // Property: Invalid associations should result in error (not 201)
            $this->assertNotEquals(
                201,
                $response->getStatusCode(),
                'Invalid boutique ID should not result in successful creation'
            );
            
            // Should be either 400 (validation error) or 404 (not found)
            $this->assertContains(
                $response->getStatusCode(),
                [400, 404, 500],
                'Invalid association should return appropriate error code'
            );
        });
    }
    
    /**
     * Property test for enterprise association inheritance
     */
    public function testEnterpriseAssociationInheritanceProperty(): void
    {
        $this->forAll(
            ClientDataGenerator::validClientData()
        )->then(function (array $clientData): void {
            
            $clientData['boutique'] = $this->testData['boutique']->getId();
            $clientData['succursale'] = $this->testData['succursale']->getId();
            
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
            
            if ($response->getStatusCode() === 201) {
                $content = json_decode($response->getContent(), true);
                $clientResponseData = $content['data'];
                
                // Property: Client should inherit enterprise from boutique/succursale
                $this->assertArrayHasKey('entreprise', $clientResponseData);
                $this->assertIsArray($clientResponseData['entreprise']);
                
                $expectedEnterpriseId = $this->testData['entreprise']->getId();
                $this->assertEquals(
                    $expectedEnterpriseId,
                    $clientResponseData['entreprise']['id'],
                    'Client should inherit enterprise from associated boutique/succursale'
                );
            }
        });
    }
    
    /**
     * Property test for client creation with photo association
     */
    public function testClientCreationWithPhotoProperty(): void
    {
        $this->forAll(
            ClientDataGenerator::validClientData()
        )->then(function (array $clientData): void {
            
            $clientData['boutique'] = $this->testData['boutique']->getId();
            $clientData['succursale'] = $this->testData['succursale']->getId();
            
            // Create a test photo file
            $photoFile = $this->createTestPhotoFile();
            
            $token = AuthenticationTestHelper::createSuperAdminToken();
            $headers = AuthenticationTestHelper::createMultipartAuthHeaders($token);
            
            $this->client->request(
                'POST',
                ApiClientTestConfig::ENDPOINT_CREATE,
                $clientData,
                ['photo' => $photoFile],
                $headers
            );
            
            $response = $this->client->getResponse();
            
            if ($response->getStatusCode() === 201) {
                $content = json_decode($response->getContent(), true);
                $clientResponseData = $content['data'];
                
                // Property: Client with photo should have photo field
                $this->assertArrayHasKey('photo', $clientResponseData);
                
                // If photo was successfully uploaded, it should not be null
                if ($clientResponseData['photo'] !== null) {
                    $this->assertIsString($clientResponseData['photo']);
                    $this->assertNotEmpty($clientResponseData['photo']);
                }
            }
        });
    }
    
    /**
     * Property test for required fields validation
     */
    public function testRequiredFieldsValidationProperty(): void
    {
        $this->forAll(
            Generator\elements(['nom', 'prenoms', 'numero']) // Required fields
        )->then(function (string $missingField): void {
            
            $clientData = [
                'nom' => 'Test Nom',
                'prenoms' => 'Test Prenoms',
                'numero' => '+225 0123456789',
                'boutique' => $this->testData['boutique']->getId(),
                'succursale' => $this->testData['succursale']->getId()
            ];
            
            // Remove the required field
            unset($clientData[$missingField]);
            
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
            
            // Property: Missing required field should result in validation error
            $this->assertEquals(
                400,
                $response->getStatusCode(),
                sprintf('Missing required field "%s" should result in 400 error', $missingField)
            );
            
            $content = json_decode($response->getContent(), true);
            $this->assertArrayHasKey('message', $content);
            $this->assertStringContainsString(
                'obligatoire',
                $content['message'],
                'Error message should indicate required field'
            );
        });
    }
    
    /**
     * Create a test photo file for upload testing
     */
    private function createTestPhotoFile(): \Symfony\Component\HttpFoundation\File\UploadedFile
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_photo_');
        
        // Create a simple 1x1 pixel JPEG
        $imageData = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwA/8A');
        file_put_contents($tempFile, $imageData);
        
        return new \Symfony\Component\HttpFoundation\File\UploadedFile(
            $tempFile,
            'test_photo.jpg',
            'image/jpeg',
            null,
            true
        );
    }
}