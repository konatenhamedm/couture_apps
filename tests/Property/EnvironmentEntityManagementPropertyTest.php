<?php

namespace App\Tests\Property;

use App\Entity\Client;
use App\Entity\Boutique;
use App\Entity\Entreprise;
use App\Service\Environment\EnvironmentEntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * **Feature: fix-persistence-environment-system, Property 3: Environment-specific entity management**
 * **Validates: Requirements 2.1, 2.4, 4.1, 4.2**
 * 
 * Property-based tests pour la gestion des entités dans l'environnement
 */
class EnvironmentEntityManagementPropertyTest extends KernelTestCase
{
    private EnvironmentEntityManagerInterface $environmentEntityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->environmentEntityManager = static::getContainer()->get(EnvironmentEntityManagerInterface::class);
    }

    /**
     * Property: For any entity, ensuring it's managed should be idempotent
     * (calling ensureEntityIsManaged multiple times should give same result)
     */
    public function testEnsureEntityIsManagedIdempotency(): void
    {
        $entities = [
            new Client(),
            new Boutique(),
            new Entreprise()
        ];

        // Run 50 iterations
        for ($i = 0; $i < 50; $i++) {
            $entity = $entities[array_rand($entities)];
            
            // Set some random data
            if ($entity instanceof Client) {
                $entity->setNom($this->generateRandomString(5, 20));
                $entity->setPrenom($this->generateRandomString(5, 20));
            } elseif ($entity instanceof Boutique) {
                $entity->setLibelle($this->generateRandomString(5, 30));
                $entity->setContact($this->generateRandomString(5, 20));
                $entity->setSituation($this->generateRandomString(5, 30));
            } elseif ($entity instanceof Entreprise) {
                $entity->setLibelle($this->generateRandomString(5, 30));
            }

            // Call ensureEntityIsManaged multiple times
            $result1 = $this->environmentEntityManager->ensureEntityIsManaged($entity);
            $result2 = $this->environmentEntityManager->ensureEntityIsManaged($result1);
            $result3 = $this->environmentEntityManager->ensureEntityIsManaged($result2);

            // Results should be consistent
            $this->assertSame(
                get_class($result1),
                get_class($result2),
                "Entity class should remain consistent across multiple ensureEntityIsManaged calls"
            );
            
            $this->assertSame(
                get_class($result2),
                get_class($result3),
                "Entity class should remain consistent across multiple ensureEntityIsManaged calls"
            );

            // If entity has an ID, it should remain the same
            if (method_exists($result1, 'getId') && $result1->getId()) {
                $this->assertEquals(
                    $result1->getId(),
                    $result2->getId(),
                    "Entity ID should remain consistent"
                );
                $this->assertEquals(
                    $result2->getId(),
                    $result3->getId(),
                    "Entity ID should remain consistent"
                );
            }
        }
    }

    /**
     * Property: For any entity, validateEntityContext should be consistent with entity state
     */
    public function testValidateEntityContextConsistency(): void
    {
        // Run 30 iterations
        for ($i = 0; $i < 30; $i++) {
            $entities = [
                new Client(),
                new Boutique(),
                new Entreprise()
            ];

            $entity = $entities[array_rand($entities)];
            
            // Set random data
            if ($entity instanceof Client) {
                $entity->setNom($this->generateRandomString(5, 20));
            } elseif ($entity instanceof Boutique) {
                $entity->setLibelle($this->generateRandomString(5, 30));
                $entity->setContact($this->generateRandomString(5, 20));
                $entity->setSituation($this->generateRandomString(5, 30));
            } elseif ($entity instanceof Entreprise) {
                $entity->setLibelle($this->generateRandomString(5, 30));
            }

            // Ensure entity is managed
            $managedEntity = $this->environmentEntityManager->ensureEntityIsManaged($entity);
            
            // Validate context multiple times - should be consistent
            $isValid1 = $this->environmentEntityManager->validateEntityContext($managedEntity);
            $isValid2 = $this->environmentEntityManager->validateEntityContext($managedEntity);
            $isValid3 = $this->environmentEntityManager->validateEntityContext($managedEntity);

            $this->assertEquals(
                $isValid1,
                $isValid2,
                "validateEntityContext should be consistent across multiple calls"
            );
            
            $this->assertEquals(
                $isValid2,
                $isValid3,
                "validateEntityContext should be consistent across multiple calls"
            );
        }
    }

    /**
     * Property: For any entity, isEntityDetached should be consistent with entity state
     */
    public function testIsEntityDetachedConsistency(): void
    {
        // Run 40 iterations
        for ($i = 0; $i < 40; $i++) {
            $entities = [
                new Client(),
                new Boutique(),
                new Entreprise()
            ];

            $entity = $entities[array_rand($entities)];
            
            // Set random data
            if ($entity instanceof Client) {
                $entity->setNom($this->generateRandomString(5, 20));
            } elseif ($entity instanceof Boutique) {
                $entity->setLibelle($this->generateRandomString(5, 30));
                $entity->setContact($this->generateRandomString(5, 20));
                $entity->setSituation($this->generateRandomString(5, 30));
            } elseif ($entity instanceof Entreprise) {
                $entity->setLibelle($this->generateRandomString(5, 30));
            }

            // Check detached status multiple times - should be consistent
            $isDetached1 = $this->environmentEntityManager->isEntityDetached($entity);
            $isDetached2 = $this->environmentEntityManager->isEntityDetached($entity);
            $isDetached3 = $this->environmentEntityManager->isEntityDetached($entity);

            $this->assertEquals(
                $isDetached1,
                $isDetached2,
                "isEntityDetached should be consistent across multiple calls"
            );
            
            $this->assertEquals(
                $isDetached2,
                $isDetached3,
                "isEntityDetached should be consistent across multiple calls"
            );

            // New entities (without ID) should not be considered detached
            if (!method_exists($entity, 'getId') || !$entity->getId()) {
                $this->assertFalse(
                    $isDetached1,
                    "New entities without ID should not be considered detached"
                );
            }
        }
    }

    /**
     * Property: For any entity, operations should not mutate the original entity unexpectedly
     */
    public function testEntityOperationsDoNotMutateUnexpectedly(): void
    {
        // Run 30 iterations
        for ($i = 0; $i < 30; $i++) {
            $client = new Client();
            $originalNom = $this->generateRandomString(5, 20);
            $originalPrenom = $this->generateRandomString(5, 20);
            
            $client->setNom($originalNom);
            $client->setPrenom($originalPrenom);

            // Store original state
            $originalState = [
                'nom' => $client->getNom(),
                'prenom' => $client->getPrenom(),
                'id' => $client->getId()
            ];

            // Perform various operations
            $this->environmentEntityManager->validateEntityContext($client);
            $this->environmentEntityManager->isEntityDetached($client);
            $managedEntity = $this->environmentEntityManager->ensureEntityIsManaged($client);

            // Original entity should not be mutated (unless it's the same reference)
            if ($managedEntity === $client) {
                // Same reference, so changes are expected
                $this->assertEquals(
                    $originalState['nom'],
                    $client->getNom(),
                    "Entity nom should remain consistent when same reference"
                );
            } else {
                // Different reference, original should be unchanged
                $this->assertEquals(
                    $originalState['nom'],
                    $client->getNom(),
                    "Original entity should not be mutated when different reference returned"
                );
                
                $this->assertEquals(
                    $originalState['prenom'],
                    $client->getPrenom(),
                    "Original entity should not be mutated when different reference returned"
                );
            }
        }
    }

    /**
     * Property: For any null input, all methods should handle gracefully
     */
    public function testNullInputHandling(): void
    {
        // Run 20 iterations to ensure consistent behavior
        for ($i = 0; $i < 20; $i++) {
            // All these should not throw exceptions and return expected values
            $this->assertNull(
                $this->environmentEntityManager->ensureEntityIsManaged(null),
                "ensureEntityIsManaged should return null for null input"
            );
            
            $this->assertNull(
                $this->environmentEntityManager->refreshEntityInCurrentContext(null),
                "refreshEntityInCurrentContext should return null for null input"
            );
            
            $this->assertFalse(
                $this->environmentEntityManager->validateEntityContext(null),
                "validateEntityContext should return false for null input"
            );
            
            $this->assertNull(
                $this->environmentEntityManager->resolveProxyEntity(null),
                "resolveProxyEntity should return null for null input"
            );
            
            $this->assertFalse(
                $this->environmentEntityManager->isEntityDetached(null),
                "isEntityDetached should return false for null input"
            );
            
            $this->assertNull(
                $this->environmentEntityManager->mergeDetachedEntity(null),
                "mergeDetachedEntity should return null for null input"
            );
            
            // detachEntity should not throw exception
            $this->environmentEntityManager->detachEntity(null);
        }
    }

    /**
     * Property: clearEntityCache should be safe to call multiple times
     */
    public function testClearEntityCacheIdempotency(): void
    {
        // Run 10 iterations
        for ($i = 0; $i < 10; $i++) {
            // Should not throw exceptions when called multiple times
            $this->environmentEntityManager->clearEntityCache();
            $this->environmentEntityManager->clearEntityCache();
            $this->environmentEntityManager->clearEntityCache();
            
            // No assertion needed - just ensuring no exceptions are thrown
            $this->assertTrue(true, "clearEntityCache should be safe to call multiple times");
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
}