<?php

namespace App\Tests\Property;

use App\Entity\Client;
use App\Entity\Boutique;
use App\Entity\Entreprise;
use App\Service\Environment\EnvironmentEntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * **Feature: fix-persistence-environment-system, Property 8: Detached entity resolution**
 * **Validates: Requirements 4.3, 4.4**
 * 
 * Property-based tests pour la résolution des entités détachées
 */
class DetachedEntityResolutionPropertyTest extends KernelTestCase
{
    private EnvironmentEntityManagerInterface $environmentEntityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->environmentEntityManager = static::getContainer()->get(EnvironmentEntityManagerInterface::class);
    }

    /**
     * Property: For any entity, detaching and then reattaching should preserve entity data
     */
    public function testDetachReattachPreservesData(): void
    {
        // Run 30 iterations
        for ($i = 0; $i < 30; $i++) {
            $entities = [
                $this->createClientWithData(),
                $this->createBoutiqueWithData(),
                $this->createEntrepriseWithData()
            ];

            $entity = $entities[array_rand($entities)];
            
            // Store original data
            $originalData = $this->extractEntityData($entity);

            // Ensure entity is managed first
            $managedEntity = $this->environmentEntityManager->ensureEntityIsManaged($entity);
            
            // Detach the entity
            $this->environmentEntityManager->detachEntity($managedEntity);
            
            // Verify it's detached (for entities with ID)
            if (method_exists($managedEntity, 'getId') && $managedEntity->getId()) {
                $this->assertTrue(
                    $this->environmentEntityManager->isEntityDetached($managedEntity),
                    "Entity should be detached after detachEntity call"
                );
            }
            
            // Reattach the entity
            $reattachedEntity = $this->environmentEntityManager->mergeDetachedEntity($managedEntity);
            
            // Verify data is preserved
            $reattachedData = $this->extractEntityData($reattachedEntity);
            
            $this->assertEquals(
                $originalData,
                $reattachedData,
                "Entity data should be preserved after detach/reattach cycle"
            );
        }
    }

    /**
     * Property: For any entity, resolveProxyEntity should be idempotent
     */
    public function testResolveProxyEntityIdempotency(): void
    {
        // Run 40 iterations
        for ($i = 0; $i < 40; $i++) {
            $entities = [
                $this->createClientWithData(),
                $this->createBoutiqueWithData(),
                $this->createEntrepriseWithData()
            ];

            $entity = $entities[array_rand($entities)];
            
            // Resolve proxy multiple times
            $resolved1 = $this->environmentEntityManager->resolveProxyEntity($entity);
            $resolved2 = $this->environmentEntityManager->resolveProxyEntity($resolved1);
            $resolved3 = $this->environmentEntityManager->resolveProxyEntity($resolved2);

            // Results should be consistent
            $this->assertEquals(
                get_class($resolved1),
                get_class($resolved2),
                "Proxy resolution should be idempotent - class should remain same"
            );
            
            $this->assertEquals(
                get_class($resolved2),
                get_class($resolved3),
                "Proxy resolution should be idempotent - class should remain same"
            );

            // Data should be preserved
            $data1 = $this->extractEntityData($resolved1);
            $data2 = $this->extractEntityData($resolved2);
            $data3 = $this->extractEntityData($resolved3);

            $this->assertEquals(
                $data1,
                $data2,
                "Proxy resolution should preserve entity data"
            );
            
            $this->assertEquals(
                $data2,
                $data3,
                "Proxy resolution should preserve entity data"
            );
        }
    }

    /**
     * Property: For any entity, mergeDetachedEntity should handle both managed and detached entities correctly
     */
    public function testMergeDetachedEntityHandlesBothStates(): void
    {
        // Run 30 iterations
        for ($i = 0; $i < 30; $i++) {
            $entities = [
                $this->createClientWithData(),
                $this->createBoutiqueWithData(),
                $this->createEntrepriseWithData()
            ];

            $entity = $entities[array_rand($entities)];
            $originalData = $this->extractEntityData($entity);

            // Test with managed entity
            $managedEntity = $this->environmentEntityManager->ensureEntityIsManaged($entity);
            $mergedManaged = $this->environmentEntityManager->mergeDetachedEntity($managedEntity);
            
            // Should return the same entity or preserve data
            $managedData = $this->extractEntityData($mergedManaged);
            $this->assertEquals(
                $originalData,
                $managedData,
                "Merging already managed entity should preserve data"
            );

            // Test with potentially detached entity
            $this->environmentEntityManager->detachEntity($managedEntity);
            $mergedDetached = $this->environmentEntityManager->mergeDetachedEntity($managedEntity);
            
            // Should preserve data
            $detachedData = $this->extractEntityData($mergedDetached);
            $this->assertEquals(
                $originalData,
                $detachedData,
                "Merging detached entity should preserve data"
            );
        }
    }

    /**
     * Property: For any entity, state transitions should be consistent
     */
    public function testEntityStateTransitionsConsistency(): void
    {
        // Run 25 iterations
        for ($i = 0; $i < 25; $i++) {
            $entities = [
                $this->createClientWithData(),
                $this->createBoutiqueWithData(),
                $this->createEntrepriseWithData()
            ];

            $entity = $entities[array_rand($entities)];
            
            // Initial state - new entity should not be detached
            $initialDetached = $this->environmentEntityManager->isEntityDetached($entity);
            if (!method_exists($entity, 'getId') || !$entity->getId()) {
                $this->assertFalse(
                    $initialDetached,
                    "New entity without ID should not be considered detached"
                );
            }

            // Ensure managed
            $managedEntity = $this->environmentEntityManager->ensureEntityIsManaged($entity);
            
            // Should be in valid context after ensuring managed
            $isValidAfterManaged = $this->environmentEntityManager->validateEntityContext($managedEntity);
            
            // For new entities, this might be false (no ID), but should be consistent
            $isValidAfterManaged2 = $this->environmentEntityManager->validateEntityContext($managedEntity);
            $this->assertEquals(
                $isValidAfterManaged,
                $isValidAfterManaged2,
                "Entity context validation should be consistent"
            );

            // Detach and check state
            $this->environmentEntityManager->detachEntity($managedEntity);
            
            if (method_exists($managedEntity, 'getId') && $managedEntity->getId()) {
                $isDetachedAfterDetach = $this->environmentEntityManager->isEntityDetached($managedEntity);
                $this->assertTrue(
                    $isDetachedAfterDetach,
                    "Entity with ID should be detached after detachEntity call"
                );
            }
        }
    }

    /**
     * Property: For any entity, refreshEntityInCurrentContext should be safe
     */
    public function testRefreshEntitySafety(): void
    {
        // Run 30 iterations
        for ($i = 0; $i < 30; $i++) {
            $entities = [
                $this->createClientWithData(),
                $this->createBoutiqueWithData(),
                $this->createEntrepriseWithData()
            ];

            $entity = $entities[array_rand($entities)];
            $originalData = $this->extractEntityData($entity);

            // Refresh should not throw exceptions
            $refreshed1 = $this->environmentEntityManager->refreshEntityInCurrentContext($entity);
            $refreshed2 = $this->environmentEntityManager->refreshEntityInCurrentContext($refreshed1);

            // Should return valid entities
            $this->assertNotNull($refreshed1, "Refresh should return non-null entity");
            $this->assertNotNull($refreshed2, "Refresh should return non-null entity");

            // Class should remain the same
            $this->assertEquals(
                get_class($entity),
                get_class($refreshed1),
                "Refresh should preserve entity class"
            );
            
            $this->assertEquals(
                get_class($refreshed1),
                get_class($refreshed2),
                "Refresh should preserve entity class"
            );
        }
    }

    /**
     * Create a Client with random data
     */
    private function createClientWithData(): Client
    {
        $client = new Client();
        $client->setNom($this->generateRandomString(5, 20));
        $client->setPrenom($this->generateRandomString(5, 20));
        $client->setNumero($this->generateRandomPhoneNumber());
        return $client;
    }

    /**
     * Create a Boutique with random data
     */
    private function createBoutiqueWithData(): Boutique
    {
        $boutique = new Boutique();
        $boutique->setLibelle($this->generateRandomString(5, 30));
        $boutique->setContact($this->generateRandomString(5, 20));
        $boutique->setSituation($this->generateRandomString(5, 30));
        return $boutique;
    }

    /**
     * Create an Entreprise with random data
     */
    private function createEntrepriseWithData(): Entreprise
    {
        $entreprise = new Entreprise();
        $entreprise->setLibelle($this->generateRandomString(5, 30));
        $entreprise->setNumero($this->generateRandomString(5, 15));
        $entreprise->setEmail($this->generateRandomEmail());
        return $entreprise;
    }

    /**
     * Extract data from entity for comparison
     */
    private function extractEntityData(object $entity): array
    {
        $data = ['class' => get_class($entity)];
        
        if ($entity instanceof Client) {
            $data['nom'] = $entity->getNom();
            $data['prenom'] = $entity->getPrenom();
            $data['numero'] = $entity->getNumero();
        } elseif ($entity instanceof Boutique) {
            $data['libelle'] = $entity->getLibelle();
            $data['contact'] = $entity->getContact();
            $data['situation'] = $entity->getSituation();
        } elseif ($entity instanceof Entreprise) {
            $data['libelle'] = $entity->getLibelle();
            $data['numero'] = $entity->getNumero();
            $data['email'] = $entity->getEmail();
        }
        
        if (method_exists($entity, 'getId')) {
            $data['id'] = $entity->getId();
        }
        
        return $data;
    }

    /**
     * Generate random string
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

    /**
     * Generate random email
     */
    private function generateRandomEmail(): string
    {
        $domains = ['example.com', 'test.org', 'demo.net'];
        $username = strtolower($this->generateRandomString(5, 10));
        $domain = $domains[array_rand($domains)];
        
        return $username . '@' . $domain;
    }
}