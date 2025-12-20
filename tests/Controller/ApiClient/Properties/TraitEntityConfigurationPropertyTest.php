<?php

namespace App\Tests\Controller\ApiClient\Properties;

use App\Tests\Controller\ApiClient\ApiClientTestBase;
use Eris\Generator;
use Eris\TestTrait;

/**
 * Property Test 8: Trait entity configuration
 * Validates: Requirements 5.5
 * 
 * Tests that all created entities are properly configured with TraitEntity
 * fields like createdAt, updatedAt, createdBy, updatedBy, and isActive.
 */
class TraitEntityConfigurationPropertyTest extends ApiClientTestBase
{
    use TestTrait;

    /**
     * Property: All created clients have proper TraitEntity configuration
     */
    public function testTraitEntityConfigurationProperty(): void
    {
        $this->forAll(
            Generator\choose(1, 50), // Multiple iterations
            Generator\elements(['sadm', 'adb', 'regular']), // User roles
            Generator\string()->withSize(Generator\choose(3, 20)), // Client names
            Generator\string()->withSize(Generator\choose(3, 20)), // Client prenoms
            Generator\regex('/^\+225 [0-9]{10}$/') // Valid phone numbers
        )
        ->then(function ($iteration, $userRole, $nom, $prenom, $numero) {
            $clientData = [
                'nom' => $nom,
                'prenoms' => $prenom,
                'numero' => $numero,
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
                $userRole
            );

            if ($response->getStatusCode() === 200) {
                $content = json_decode($response->getContent(), true);
                
                // Property: Response contains TraitEntity metadata
                $this->assertArrayHasKey(
                    'data', 
                    $content, 
                    'Response must have data field'
                );
                
                $clientData = $content['data'];
                
                // Property: CreatedAt must be present and valid
                $this->assertArrayHasKey(
                    'createdAt', 
                    $clientData, 
                    'Client must have createdAt field from TraitEntity'
                );
                
                $this->assertNotNull(
                    $clientData['createdAt'], 
                    'CreatedAt must not be null'
                );
                
                // Property: IsActive must be present and true for new entities
                $this->assertArrayHasKey(
                    'active', 
                    $clientData, 
                    'Client must have active field from TraitEntity'
                );
                
                $this->assertTrue(
                    $clientData['active'], 
                    'New clients must be active by default'
                );
                
                // Property: ID must be present and positive
                $this->assertArrayHasKey(
                    'id', 
                    $clientData, 
                    'Client must have ID field'
                );
                
                $this->assertIsInt(
                    $clientData['id'], 
                    'Client ID must be an integer'
                );
                
                $this->assertGreaterThan(
                    0, 
                    $clientData['id'], 
                    'Client ID must be positive'
                );
                
                // Property: CreatedBy should be present (may be null in some configurations)
                $this->assertArrayHasKey(
                    'createdBy', 
                    $clientData, 
                    'Client should have createdBy field from TraitEntity'
                );
                
                // Property: UpdatedBy should be present (may be null for new entities)
                $this->assertArrayHasKey(
                    'updatedBy', 
                    $clientData, 
                    'Client should have updatedBy field from TraitEntity'
                );
                
                // Property: UpdatedAt should be present (may be null for new entities)
                $this->assertArrayHasKey(
                    'updatedAt', 
                    $clientData, 
                    'Client should have updatedAt field from TraitEntity'
                );
            }
        });
    }

    /**
     * Property: Client updates properly set TraitEntity update fields
     */
    public function testTraitEntityUpdateConfigurationProperty(): void
    {
        $this->forAll(
            Generator\choose(1, 30), // Multiple iterations
            Generator\elements(['sadm', 'adb', 'regular']), // User roles
            Generator\string()->withSize(Generator\choose(3, 20)) // Updated names
        )
        ->then(function ($iteration, $userRole, $updatedNom) {
            // First create a client
            $originalData = [
                'nom' => 'Original' . $iteration,
                'prenoms' => 'Client' . $iteration,
                'numero' => '+225 012345' . str_pad($iteration, 4, '0', STR_PAD_LEFT),
                'boutique' => $this->testData['boutique']->getId(),
                'succursale' => $this->testData['succursale']->getId()
            ];

            $createResponse = $this->makeAuthenticatedRequest(
                'POST',
                '/api/client/create',
                $originalData,
                [],
                [],
                null,
                $userRole
            );

            if ($createResponse->getStatusCode() === 200) {
                $createContent = json_decode($createResponse->getContent(), true);
                $clientId = $createContent['data']['id'];
                $originalCreatedAt = $createContent['data']['createdAt'];

                // Now update the client
                $updateData = ['nom' => $updatedNom];
                
                $updateResponse = $this->makeAuthenticatedRequest(
                    'PUT',
                    '/api/client/update/' . $clientId,
                    $updateData,
                    [],
                    [],
                    null,
                    $userRole
                );

                if ($updateResponse->getStatusCode() === 200) {
                    $updateContent = json_decode($updateResponse->getContent(), true);
                    $updatedClientData = $updateContent['data'];

                    // Property: UpdatedAt must be set after update
                    $this->assertArrayHasKey(
                        'updatedAt', 
                        $updatedClientData, 
                        'Updated client must have updatedAt field'
                    );
                    
                    $this->assertNotNull(
                        $updatedClientData['updatedAt'], 
                        'UpdatedAt must be set after update operation'
                    );

                    // Property: CreatedAt should remain unchanged
                    $this->assertEquals(
                        $originalCreatedAt, 
                        $updatedClientData['createdAt'], 
                        'CreatedAt should not change during updates'
                    );

                    // Property: UpdatedBy should be set
                    $this->assertArrayHasKey(
                        'updatedBy', 
                        $updatedClientData, 
                        'Updated client must have updatedBy field'
                    );

                    // Property: Active status should remain true
                    $this->assertTrue(
                        $updatedClientData['active'], 
                        'Client should remain active after update'
                    );

                    // Property: Updated data should reflect changes
                    $this->assertEquals(
                        $updatedNom, 
                        $updatedClientData['nom'], 
                        'Updated nom should match the provided value'
                    );
                }
            }
        });
    }

    /**
     * Property: TraitEntity fields are consistent across all operations
     */
    public function testTraitEntityConsistencyProperty(): void
    {
        $this->forAll(
            Generator\choose(1, 20), // Multiple iterations
            Generator\elements(['sadm', 'adb', 'regular']) // User roles
        )
        ->then(function ($iteration, $userRole) {
            // Create a client
            $clientData = [
                'nom' => 'Consistency' . $iteration,
                'prenoms' => 'Test' . $iteration,
                'numero' => '+225 012345' . str_pad($iteration, 4, '0', STR_PAD_LEFT),
                'boutique' => $this->testData['boutique']->getId(),
                'succursale' => $this->testData['succursale']->getId()
            ];

            $createResponse = $this->makeAuthenticatedRequest(
                'POST',
                '/api/client/create',
                $clientData,
                [],
                [],
                null,
                $userRole
            );

            if ($createResponse->getStatusCode() === 200) {
                $createContent = json_decode($createResponse->getContent(), true);
                $clientId = $createContent['data']['id'];

                // Retrieve the same client
                $getResponse = $this->makeAuthenticatedRequest(
                    'GET',
                    '/api/client/get/one/' . $clientId,
                    [],
                    [],
                    [],
                    null,
                    $userRole
                );

                if ($getResponse->getStatusCode() === 200) {
                    $getContent = json_decode($getResponse->getContent(), true);
                    $retrievedClientData = $getContent['data'];

                    // Property: TraitEntity fields must be consistent between create and retrieve
                    $this->assertEquals(
                        $createContent['data']['id'], 
                        $retrievedClientData['id'], 
                        'ID must be consistent between create and retrieve'
                    );

                    $this->assertEquals(
                        $createContent['data']['createdAt'], 
                        $retrievedClientData['createdAt'], 
                        'CreatedAt must be consistent between create and retrieve'
                    );

                    $this->assertEquals(
                        $createContent['data']['active'], 
                        $retrievedClientData['active'], 
                        'Active status must be consistent between create and retrieve'
                    );

                    // Property: All TraitEntity fields must be present in both responses
                    $traitFields = ['id', 'createdAt', 'updatedAt', 'createdBy', 'updatedBy', 'active'];
                    
                    foreach ($traitFields as $field) {
                        $this->assertArrayHasKey(
                            $field, 
                            $createContent['data'], 
                            "Create response must contain TraitEntity field: $field"
                        );
                        
                        $this->assertArrayHasKey(
                            $field, 
                            $retrievedClientData, 
                            "Retrieve response must contain TraitEntity field: $field"
                        );
                    }
                }
            }
        });
    }
}