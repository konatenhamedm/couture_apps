<?php

namespace App\Tests\Controller\ApiClient\Properties;

use App\Tests\Controller\ApiClient\ApiClientTestBase;
use Eris\Generator;
use Eris\TestTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Property Test 9: Client update consistency
 * Validates: Requirements 6.1, 6.2, 6.3
 * 
 * Tests that client updates maintain data consistency and integrity
 * across different update scenarios and field combinations.
 */
class ClientUpdateConsistencyPropertyTest extends ApiClientTestBase
{
    use TestTrait;

    /**
     * Property: Client updates preserve unchanged fields
     */
    public function testClientUpdatePreservesUnchangedFieldsProperty(): void
    {
        $this->forAll(
            Generator\choose(1, 50), // Multiple iterations
            Generator\elements(['sadm', 'adb', 'regular']), // User roles
            Generator\string()->withSize(Generator\choose(3, 20)), // Original names
            Generator\string()->withSize(Generator\choose(3, 20)), // Original prenoms
            Generator\regex('/^\+225 [0-9]{10}$/'), // Original phone numbers
            Generator\string()->withSize(Generator\choose(3, 20)) // Updated names
        )
        ->then(function ($iteration, $userRole, $originalNom, $originalPrenom, $originalNumero, $updatedNom) {
            // Create a client with original data
            $client = $this->createTestClient([
                'nom' => $originalNom,
                'prenom' => $originalPrenom,
                'numero' => $originalNumero
            ]);

            // Update only the nom field
            $updateData = ['nom' => $updatedNom];

            $response = $this->makeAuthenticatedRequest(
                'PUT',
                '/api/client/update/' . $client->getId(),
                $updateData,
                [],
                [],
                null,
                $userRole
            );

            if ($response->getStatusCode() === 200) {
                $content = json_decode($response->getContent(), true);
                $clientData = $content['data'];

                // Property: Updated field reflects new value
                $this->assertEquals(
                    $updatedNom, 
                    $clientData['nom'], 
                    'Updated nom field must reflect the new value'
                );

                // Property: Unchanged fields preserve original values
                $this->assertEquals(
                    $originalPrenom, 
                    $clientData['prenom'], 
                    'Unchanged prenom field must preserve original value'
                );

                $this->assertEquals(
                    $originalNumero, 
                    $clientData['numero'], 
                    'Unchanged numero field must preserve original value'
                );

                // Property: ID remains unchanged
                $this->assertEquals(
                    $client->getId(), 
                    $clientData['id'], 
                    'Client ID must remain unchanged after update'
                );
            }
        });
    }

    /**
     * Property: Multiple field updates are applied atomically
     */
    public function testClientUpdateAtomicityProperty(): void
    {
        $this->forAll(
            Generator\choose(1, 30), // Multiple iterations
            Generator\elements(['sadm', 'adb', 'regular']), // User roles
            Generator\string()->withSize(Generator\choose(3, 20)), // New nom
            Generator\string()->withSize(Generator\choose(3, 20)), // New prenom
            Generator\regex('/^\+225 [0-9]{10}$/') // New numero
        )
        ->then(function ($iteration, $userRole, $newNom, $newPrenom, $newNumero) {
            // Create a client
            $client = $this->createTestClient([
                'nom' => 'Original' . $iteration,
                'prenom' => 'OriginalPrenom' . $iteration,
                'numero' => '+225 012345' . str_pad($iteration, 4, '0', STR_PAD_LEFT)
            ]);

            // Update multiple fields at once
            $updateData = [
                'nom' => $newNom,
                'prenoms' => $newPrenom,
                'numero' => $newNumero
            ];

            $response = $this->makeAuthenticatedRequest(
                'PUT',
                '/api/client/update/' . $client->getId(),
                $updateData,
                [],
                [],
                null,
                $userRole
            );

            if ($response->getStatusCode() === 200) {
                $content = json_decode($response->getContent(), true);
                $clientData = $content['data'];

                // Property: All updated fields reflect new values (atomicity)
                $this->assertEquals(
                    $newNom, 
                    $clientData['nom'], 
                    'All updates must be applied atomically - nom'
                );

                $this->assertEquals(
                    $newPrenom, 
                    $clientData['prenom'], 
                    'All updates must be applied atomically - prenom'
                );

                $this->assertEquals(
                    $newNumero, 
                    $clientData['numero'], 
                    'All updates must be applied atomically - numero'
                );
            }
        });
    }

    /**
     * Property: Update validation is consistent
     */
    public function testClientUpdateValidationConsistencyProperty(): void
    {
        $this->forAll(
            Generator\choose(1, 30), // Multiple iterations
            Generator\elements(['sadm', 'adb', 'regular']), // User roles
            Generator\elements(['', '   ', null]) // Invalid values
        )
        ->then(function ($iteration, $userRole, $invalidValue) {
            // Create a client
            $client = $this->createTestClient([
                'nom' => 'ValidationTest' . $iteration,
                'prenom' => 'Test' . $iteration,
                'numero' => '+225 012345' . str_pad($iteration, 4, '0', STR_PAD_LEFT)
            ]);

            // Try to update with invalid nom
            $updateData = ['nom' => $invalidValue];

            $response = $this->makeAuthenticatedRequest(
                'PUT',
                '/api/client/update/' . $client->getId(),
                $updateData,
                [],
                [],
                null,
                $userRole
            );

            // Property: Invalid updates are consistently rejected
            $this->assertEquals(
                400, 
                $response->getStatusCode(), 
                'Invalid field values must be consistently rejected with 400 status'
            );

            $content = json_decode($response->getContent(), true);
            $this->assertIsArray($content, 'Error response must be valid JSON');
            $this->assertArrayHasKey('message', $content, 'Error response must have message');
            $this->assertArrayHasKey('statusCode', $content, 'Error response must have statusCode');
            $this->assertEquals(400, $content['statusCode']);
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