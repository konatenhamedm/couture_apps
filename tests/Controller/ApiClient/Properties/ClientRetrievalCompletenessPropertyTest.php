<?php

namespace App\Tests\Controller\ApiClient\Properties;

use App\Tests\Controller\ApiClient\ApiClientTestBase;
use Eris\Generator;
use Eris\TestTrait;

/**
 * Property Test 4: Client retrieval completeness
 * Validates: Requirements 3.1, 3.5
 * 
 * Tests that client retrieval always returns complete and consistent data
 * regardless of the client's data variations.
 */
class ClientRetrievalCompletenessPropertyTest extends ApiClientTestBase
{
    use TestTrait;

    /**
     * Property: Retrieved client data is always complete and consistent
     * 
     * For any valid client in the system, retrieving it should:
     * - Return all required fields (id, nom, prenom, numero)
     * - Include all association fields (boutique, succursale, entreprise)
     * - Include metadata fields (createdAt, updatedAt if exists)
     * - Maintain data consistency with stored values
     */
    public function testClientRetrievalCompletenessProperty(): void
    {
        $this->forAll(
            Generator\choose(1, 100), // Client variations
            Generator\elements(['sadm', 'adb', 'regular']), // User roles
            Generator\string()->withSize(Generator\choose(3, 20)), // Client names
            Generator\string()->withSize(Generator\choose(3, 20)), // Client prenoms
            Generator\regex('/^\+225 [0-9]{10}$/') // Valid phone numbers
        )
        ->then(function ($iteration, $userRole, $nom, $prenom, $numero) {
            // Create a client with the generated data
            $client = $this->createTestClient([
                'nom' => $nom,
                'prenom' => $prenom,
                'numero' => $numero
            ]);

            // Retrieve the client
            $response = $this->makeAuthenticatedRequest(
                'GET',
                '/api/client/get/one/' . $client->getId(),
                [],
                [],
                [],
                null,
                $userRole
            );

            // Property assertions
            $content = $this->assertJsonResponse($response, 200);

            // 1. All required fields must be present
            $requiredFields = ['id', 'nom', 'prenom', 'numero'];
            foreach ($requiredFields as $field) {
                $this->assertArrayHasKey(
                    $field, 
                    $content, 
                    "Required field '$field' must be present in client retrieval response"
                );
                $this->assertNotNull(
                    $content[$field], 
                    "Required field '$field' must not be null"
                );
            }

            // 2. Association fields must be present (can be null but key must exist)
            $associationFields = ['boutique', 'succursale', 'entreprise'];
            foreach ($associationFields as $field) {
                $this->assertArrayHasKey(
                    $field, 
                    $content, 
                    "Association field '$field' must be present in response"
                );
            }

            // 3. Metadata fields must be present
            $metadataFields = ['createdAt'];
            foreach ($metadataFields as $field) {
                $this->assertArrayHasKey(
                    $field, 
                    $content, 
                    "Metadata field '$field' must be present in response"
                );
            }

            // 4. Data consistency - retrieved data matches stored data
            $this->assertEquals(
                $client->getId(), 
                $content['id'], 
                'Retrieved ID must match stored ID'
            );
            $this->assertEquals(
                $nom, 
                $content['nom'], 
                'Retrieved nom must match stored nom'
            );
            $this->assertEquals(
                $prenom, 
                $content['prenom'], 
                'Retrieved prenom must match stored prenom'
            );
            $this->assertEquals(
                $numero, 
                $content['numero'], 
                'Retrieved numero must match stored numero'
            );

            // 5. ID must be positive integer
            $this->assertIsInt(
                $content['id'], 
                'Client ID must be an integer'
            );
            $this->assertGreaterThan(
                0, 
                $content['id'], 
                'Client ID must be positive'
            );

            // 6. String fields must be strings and not empty
            $this->assertIsString($content['nom'], 'nom must be a string');
            $this->assertIsString($content['prenom'], 'prenom must be a string');
            $this->assertIsString($content['numero'], 'numero must be a string');
            
            $this->assertNotEmpty($content['nom'], 'nom must not be empty');
            $this->assertNotEmpty($content['prenom'], 'prenom must not be empty');
            $this->assertNotEmpty($content['numero'], 'numero must not be empty');

            // 7. Entreprise association must always be present and not null
            $this->assertNotNull(
                $content['entreprise'], 
                'Entreprise association must not be null'
            );

            // 8. If boutique is present, it should have an ID
            if ($content['boutique'] !== null) {
                $this->assertArrayHasKey('id', $content['boutique']);
                $this->assertIsInt($content['boutique']['id']);
                $this->assertGreaterThan(0, $content['boutique']['id']);
            }

            // 9. If succursale is present, it should have an ID
            if ($content['succursale'] !== null) {
                $this->assertArrayHasKey('id', $content['succursale']);
                $this->assertIsInt($content['succursale']['id']);
                $this->assertGreaterThan(0, $content['succursale']['id']);
            }

            // 10. CreatedAt must be a valid datetime string
            $this->assertIsString($content['createdAt'], 'createdAt must be a string');
            $this->assertNotEmpty($content['createdAt'], 'createdAt must not be empty');
            
            // Validate datetime format
            $createdAt = \DateTime::createFromFormat(\DateTime::ATOM, $content['createdAt']);
            $this->assertNotFalse(
                $createdAt, 
                'createdAt must be a valid ISO 8601 datetime string'
            );
        });
    }

    /**
     * Property: Non-existent client retrieval always returns 404
     */
    public function testNonExistentClientRetrievalProperty(): void
    {
        $this->forAll(
            Generator\choose(100000, 999999), // Non-existent IDs
            Generator\elements(['sadm', 'adb', 'regular']) // User roles
        )
        ->then(function ($nonExistentId, $userRole) {
            $response = $this->makeAuthenticatedRequest(
                'GET',
                '/api/client/get/one/' . $nonExistentId,
                [],
                [],
                [],
                null,
                $userRole
            );

            // Property: Non-existent resources always return 404
            $this->assertEquals(
                404, 
                $response->getStatusCode(), 
                'Non-existent client retrieval must return 404'
            );

            $content = json_decode($response->getContent(), true);
            $this->assertArrayHasKey('message', $content);
            $this->assertArrayHasKey('statusCode', $content);
            $this->assertEquals(404, $content['statusCode']);
        });
    }

    /**
     * Property: Client retrieval response structure is consistent
     */
    public function testClientRetrievalResponseStructureProperty(): void
    {
        $this->forAll(
            Generator\choose(1, 50), // Multiple iterations
            Generator\elements(['sadm', 'adb', 'regular']) // User roles
        )
        ->then(function ($iteration, $userRole) {
            // Create a client
            $client = $this->createTestClient([
                'nom' => 'TestClient' . $iteration,
                'prenom' => 'TestPrenom' . $iteration,
                'numero' => '+225 012345' . str_pad($iteration, 4, '0', STR_PAD_LEFT)
            ]);

            $response = $this->makeAuthenticatedRequest(
                'GET',
                '/api/client/get/one/' . $client->getId(),
                [],
                [],
                [],
                null,
                $userRole
            );

            $content = $this->assertJsonResponse($response, 200);

            // Property: Response structure is always consistent
            $expectedStructure = [
                'id', 'nom', 'prenom', 'numero', 'photo',
                'boutique', 'succursale', 'entreprise', 'createdAt'
            ];

            foreach ($expectedStructure as $field) {
                $this->assertArrayHasKey(
                    $field, 
                    $content, 
                    "Response structure must always include '$field' field"
                );
            }

            // Property: Response is valid JSON
            $this->assertIsArray($content, 'Response must be valid JSON array/object');
            
            // Property: No unexpected extra fields at root level
            $actualFields = array_keys($content);
            $allowedFields = array_merge($expectedStructure, ['updatedAt', 'isActive']);
            
            foreach ($actualFields as $field) {
                $this->assertContains(
                    $field, 
                    $allowedFields, 
                    "Unexpected field '$field' in response structure"
                );
            }
        });
    }
}