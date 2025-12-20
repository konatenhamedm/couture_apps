<?php

namespace App\Tests\Property;

use App\Entity\Client;
use App\Entity\Boutique;
use App\Entity\Entreprise;
use App\Entity\Surccursale;
use App\Service\Validation\EntityValidationServiceInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * **Feature: fix-persistence-environment-system, Property 7: Pre-persistence validation completeness**
 * **Validates: Requirements 3.4, 5.1, 5.3**
 * 
 * Property-based tests pour le service de validation d'entités
 */
class EntityValidationPropertyTest extends KernelTestCase
{
    private EntityValidationServiceInterface $validationService;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validationService = static::getContainer()->get(EntityValidationServiceInterface::class);
    }

    /**
     * Property: For any entity with required 'libelle' field, validation should fail if libelle is empty
     * Runs 100 iterations with different entity types and libelle values
     */
    public function testLibelleValidationProperty(): void
    {
        $entitiesWithLibelle = [
            Boutique::class,
            Entreprise::class,
            Surccursale::class
        ];

        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Generate random entity type
            $entityClass = $entitiesWithLibelle[array_rand($entitiesWithLibelle)];
            $entity = new $entityClass();

            // Generate random libelle value (sometimes empty, sometimes valid)
            $shouldBeValid = rand(0, 1) === 1;
            
            if ($shouldBeValid) {
                $libelle = $this->generateRandomString(5, 50);
                $entity->setLibelle($libelle);
            } else {
                // Set empty or whitespace-only libelle (avoid null for strict types)
                $emptyValues = ['', '   ', "\t", "\n", ' '];
                $entity->setLibelle($emptyValues[array_rand($emptyValues)]);
            }

            $result = $this->validationService->validateLibelleFields($entity);

            if ($shouldBeValid) {
                $this->assertTrue(
                    $result->isValid(),
                    "Entity {$entityClass} with valid libelle should be valid. Errors: " . $result->getFormattedErrors()
                );
            } else {
                $this->assertFalse(
                    $result->isValid(),
                    "Entity {$entityClass} with empty libelle should be invalid"
                );
                $this->assertStringContainsString('libelle', $result->getFormattedErrors());
            }
        }
    }

    /**
     * Property: For any Client entity (which doesn't have libelle), libelle validation should always pass
     * Runs 50 iterations with different Client configurations
     */
    public function testClientLibelleValidationProperty(): void
    {
        // Run 50 iterations
        for ($i = 0; $i < 50; $i++) {
            $client = new Client();
            
            // Set random valid client data
            $client->setNom($this->generateRandomString(3, 20));
            $client->setPrenom($this->generateRandomString(3, 20));
            $client->setNumero($this->generateRandomPhoneNumber());

            $result = $this->validationService->validateLibelleFields($client);

            $this->assertTrue(
                $result->isValid(),
                "Client entity should always be valid for libelle validation (no libelle field). Errors: " . $result->getFormattedErrors()
            );
        }
    }

    /**
     * Property: For any entity with valid libelle, validation should be idempotent
     * (running validation multiple times should give same result)
     */
    public function testValidationIdempotencyProperty(): void
    {
        $entitiesWithLibelle = [
            Boutique::class,
            Entreprise::class,
            Surccursale::class
        ];

        // Run 30 iterations
        for ($i = 0; $i < 30; $i++) {
            $entityClass = $entitiesWithLibelle[array_rand($entitiesWithLibelle)];
            $entity = new $entityClass();
            $entity->setLibelle($this->generateRandomString(5, 30));

            // Run validation multiple times
            $result1 = $this->validationService->validateLibelleFields($entity);
            $result2 = $this->validationService->validateLibelleFields($entity);
            $result3 = $this->validationService->validateLibelleFields($entity);

            $this->assertEquals(
                $result1->isValid(),
                $result2->isValid(),
                "Validation should be idempotent - same result on multiple calls"
            );
            
            $this->assertEquals(
                $result2->isValid(),
                $result3->isValid(),
                "Validation should be idempotent - same result on multiple calls"
            );

            $this->assertEquals(
                $result1->getErrors(),
                $result2->getErrors(),
                "Validation errors should be identical on multiple calls"
            );
        }
    }

    /**
     * Property: For any entity, if validation passes, the entity should remain unchanged
     */
    public function testValidationDoesNotMutateEntityProperty(): void
    {
        // Run 50 iterations
        for ($i = 0; $i < 50; $i++) {
            $boutique = new Boutique();
            $originalLibelle = $this->generateRandomString(5, 30);
            $boutique->setLibelle($originalLibelle);
            
            // Store original state
            $originalState = [
                'libelle' => $boutique->getLibelle(),
                'id' => $boutique->getId()
            ];

            // Run validation
            $this->validationService->validateLibelleFields($boutique);

            // Verify entity is unchanged
            $this->assertEquals(
                $originalState['libelle'],
                $boutique->getLibelle(),
                "Validation should not modify entity libelle"
            );
            
            $this->assertEquals(
                $originalState['id'],
                $boutique->getId(),
                "Validation should not modify entity id"
            );
        }
    }

    /**
     * Property: Validation result should always have consistent structure
     */
    public function testValidationResultStructureProperty(): void
    {
        // Run 50 iterations with different entities and states
        for ($i = 0; $i < 50; $i++) {
            $entities = [
                new Client(),
                new Boutique(),
                new Entreprise()
            ];

            $entity = $entities[array_rand($entities)];
            
            // Randomly set libelle for entities that have it
            if (method_exists($entity, 'setLibelle')) {
                if (rand(0, 1)) {
                    $entity->setLibelle($this->generateRandomString(1, 30));
                }
            }

            $result = $this->validationService->validateLibelleFields($entity);

            // Verify result structure
            $this->assertIsBool($result->isValid(), "isValid() should return boolean");
            $this->assertIsArray($result->getErrors(), "getErrors() should return array");
            $this->assertIsArray($result->getWarnings(), "getWarnings() should return array");
            $this->assertIsString($result->getFormattedErrors(), "getFormattedErrors() should return string");
            $this->assertIsString($result->getFormattedWarnings(), "getFormattedWarnings() should return string");
            
            // If invalid, should have errors
            if (!$result->isValid()) {
                $this->assertNotEmpty($result->getErrors(), "Invalid result should have errors");
            }
        }
    }

    /**
     * Generate random string of specified length range
     */
    private function generateRandomString(int $minLength, int $maxLength): string
    {
        $length = rand($minLength, $maxLength);
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789 ';
        $result = '';
        
        for ($i = 0; $i < $length; $i++) {
            $result .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return trim($result);
    }

    /**
     * Generate random phone number
     */
    private function generateRandomPhoneNumber(): string
    {
        $prefixes = ['+225', '+33', '+1', '+44'];
        $prefix = $prefixes[array_rand($prefixes)];
        $number = '';
        
        for ($i = 0; $i < 8; $i++) {
            $number .= rand(0, 9);
        }
        
        return $prefix . ' ' . $number;
    }
}