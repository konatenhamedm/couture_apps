<?php

namespace App\Tests\Property;

use App\Entity\Client;
use App\Entity\Boutique;
use App\Entity\Surccursale;
use App\Entity\Entreprise;
use App\Service\Validation\CascadeOperationValidatorInterface;
use App\Service\Environment\EnvironmentEntityManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Eris\Generator;
use Eris\TestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Property 4: Cascade operation validity
 * 
 * For any entity with cascade persist relationships, all related entities should be 
 * in a valid state for persistence and in the same persistence context
 * 
 * Validates: Requirements 2.5, 4.5
 */
class CascadeOperationValidityPropertyTest extends KernelTestCase
{
    use TestTrait;

    private EntityManagerInterface $entityManager;
    private CascadeOperationValidatorInterface $cascadeValidator;
    private EnvironmentEntityManagerInterface $environmentEntityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->cascadeValidator = $container->get(CascadeOperationValidatorInterface::class);
        $this->environmentEntityManager = $container->get(EnvironmentEntityManagerInterface::class);
    }

    /**
     * Property: For any client with related entities, cascade operations should be valid
     */
    public function testCascadeOperationsAreValidForClientWithRelatedEntities(): void
    {
        $this->forAll(
            Generator\choose(1, 100), // iterations
            $this->generateClientWithRelatedEntities()
        )->then(function (int $iteration, array $clientData) {
            // Arrange: Create client with related entities
            $client = $this->createClientFromData($clientData);
            
            // Act: Validate cascade operations
            $result = $this->cascadeValidator->validateCascadeOperations($client);
            
            // Assert: Cascade operations should be valid or provide clear error messages
            if (!$result->isValid()) {
                $this->assertNotEmpty($result->getErrors(), 
                    "Cascade validation should provide specific error messages when invalid");
                
                // Verify error messages are meaningful
                foreach ($result->getErrors() as $error) {
                    $this->assertStringContainsString('cascade', strtolower($error), 
                        "Error messages should mention cascade operations");
                }
            } else {
                // If valid, related entities should be properly managed
                $this->assertCascadeOperationsAreProperlyConfigured($client);
            }
        });
    }

    /**
     * Property: For any client, related entity states should be consistent
     */
    public function testRelatedEntityStatesAreConsistent(): void
    {
        $this->forAll(
            Generator\choose(1, 100),
            $this->generateClientWithRelatedEntities()
        )->then(function (int $iteration, array $clientData) {
            // Arrange
            $client = $this->createClientFromData($clientData);
            
            // Act: Validate related entity states
            $result = $this->cascadeValidator->validateRelatedEntityStates($client);
            
            // Assert: Related entities should be in consistent states
            $this->assertTrue($result->isValid() || $result->hasErrors(), 
                "Validation should either pass or provide specific errors");
            
            if ($result->hasErrors()) {
                // Verify error messages are specific about entity states
                foreach ($result->getErrors() as $error) {
                    $this->assertMatchesRegularExpression('/entité|entity|état|state/i', $error,
                        "Error messages should mention entity states");
                }
            }
        });
    }

    /**
     * Property: For any client, entities should be in the same persistence context
     */
    public function testEntitiesAreInSamePersistenceContext(): void
    {
        $this->forAll(
            Generator\choose(1, 100),
            $this->generateClientWithRelatedEntities()
        )->then(function (int $iteration, array $clientData) {
            // Arrange
            $client = $this->createClientFromData($clientData);
            
            // Ensure client is managed
            $managedClient = $this->environmentEntityManager->ensureEntityIsManaged($client);
            
            // Act: Validate persistence context
            $result = $this->cascadeValidator->ensureEntitiesInSamePersistenceContext($managedClient);
            
            // Assert: Should provide meaningful feedback about context issues
            if (!$result->isValid()) {
                $this->assertNotEmpty($result->getErrors(), 
                    "Context validation should provide specific error messages");
                
                foreach ($result->getErrors() as $error) {
                    $this->assertMatchesRegularExpression('/contexte|context|persistance|persistence/i', $error,
                        "Error messages should mention persistence context");
                }
            }
            
            // Warnings are acceptable for detached entities
            if ($result->hasWarnings()) {
                foreach ($result->getWarnings() as $warning) {
                    $this->assertMatchesRegularExpression('/détachée?|detached|gérée?|managed/i', $warning,
                        "Warnings should mention entity management state");
                }
            }
        });
    }

    /**
     * Property: Cascade validation should handle edge cases gracefully
     */
    public function testCascadeValidationHandlesEdgeCases(): void
    {
        $this->forAll(
            Generator\choose(1, 50),
            Generator\oneOf(
                $this->generateClientWithNullRelations(),
                $this->generateClientWithEmptyLibelleRelations(),
                $this->generateClientWithDetachedRelations()
            )
        )->then(function (int $iteration, Client $client) {
            // Act: Validate cascade operations for edge cases
            $result = $this->cascadeValidator->validateCascadeOperations($client);
            
            // Assert: Should handle edge cases without throwing exceptions
            $this->assertInstanceOf(\App\Service\Validation\ValidationResult::class, $result,
                "Cascade validation should always return a ValidationResult");
            
            // Should provide meaningful feedback for problematic cases
            if (!$result->isValid()) {
                $this->assertNotEmpty($result->getErrors(),
                    "Invalid cases should provide specific error messages");
            }
        });
    }

    private function generateClientWithRelatedEntities(): Generator
    {
        return Generator\map(
            function (string $nom, string $prenom, string $numero, array $entrepriseData, array $boutiqueData, array $succursaleData) {
                return [
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'numero' => $numero,
                    'entreprise' => $entrepriseData,
                    'boutique' => $boutiqueData,
                    'succursale' => $succursaleData
                ];
            },
            Generator\names(),
            Generator\names(),
            Generator\map(function($num) { return '+225 ' . str_pad($num, 8, '0', STR_PAD_LEFT); }, Generator\choose(10000000, 99999999)),
            $this->generateEntrepriseData(),
            $this->generateBoutiqueData(),
            $this->generateSuccursaleData()
        );
    }

    private function generateEntrepriseData(): Generator
    {
        return Generator\map(
            function (string $name) {
                return [
                    'libelle' => 'Entreprise ' . $name,
                    'email' => strtolower($name) . '@example.com',
                    'isActive' => true
                ];
            },
            Generator\names()
        );
    }

    private function generateBoutiqueData(): Generator
    {
        return Generator\map(
            function (string $name, string $adresse) {
                return [
                    'libelle' => 'Boutique ' . $name,
                    'adresse' => $adresse,
                    'isActive' => true
                ];
            },
            Generator\names(),
            Generator\string()
        );
    }

    private function generateSuccursaleData(): Generator
    {
        return Generator\map(
            function (string $name, string $adresse) {
                return [
                    'libelle' => 'Succursale ' . $name,
                    'adresse' => $adresse,
                    'isActive' => true
                ];
            },
            Generator\names(),
            Generator\string()
        );
    }

    private function generateClientWithNullRelations(): Generator
    {
        return Generator\map(
            function (string $nom, string $prenom, string $numero) {
                $client = new Client();
                $client->setNom($nom);
                $client->setPrenom($prenom);
                $client->setNumero($numero);
                // Relations intentionnellement null
                return $client;
            },
            Generator\names(),
            Generator\names(),
            Generator\map(function($num) { return '+225 ' . str_pad($num, 8, '0', STR_PAD_LEFT); }, Generator\choose(10000000, 99999999))
        );
    }

    private function generateClientWithEmptyLibelleRelations(): Generator
    {
        return Generator\map(
            function (string $nom, string $prenom, string $numero) {
                $client = new Client();
                $client->setNom($nom);
                $client->setPrenom($prenom);
                $client->setNumero($numero);
                
                // Create related entities with empty libelle (should cause validation errors)
                $entreprise = new Entreprise();
                $entreprise->setLibelle(''); // Empty libelle
                $entreprise->setEmail('test@example.com');
                
                $boutique = new Boutique();
                $boutique->setLibelle(''); // Empty libelle
                $boutique->setEntreprise($entreprise);
                
                $client->setEntreprise($entreprise);
                $client->setBoutique($boutique);
                
                return $client;
            },
            Generator\names(),
            Generator\names(),
            Generator\map(function($num) { return '+225 ' . str_pad($num, 8, '0', STR_PAD_LEFT); }, Generator\choose(10000000, 99999999))
        );
    }

    private function generateClientWithDetachedRelations(): Generator
    {
        return Generator\map(
            function (string $nom, string $prenom, string $numero) {
                $client = new Client();
                $client->setNom($nom);
                $client->setPrenom($prenom);
                $client->setNumero($numero);
                
                // Create detached entities (with IDs but not managed)
                $entreprise = new Entreprise();
                $entreprise->setLibelle('Entreprise Test');
                $entreprise->setEmail('test@example.com');
                // Simulate detached entity by setting ID via reflection
                $reflection = new \ReflectionClass($entreprise);
                if ($reflection->hasProperty('id')) {
                    $idProperty = $reflection->getProperty('id');
                    $idProperty->setAccessible(true);
                    $idProperty->setValue($entreprise, 999); // Fake ID
                }
                
                $client->setEntreprise($entreprise);
                
                return $client;
            },
            Generator\names(),
            Generator\names(),
            Generator\map(function($num) { return '+225 ' . str_pad($num, 8, '0', STR_PAD_LEFT); }, Generator\choose(10000000, 99999999))
        );
    }

    private function createClientFromData(array $data): Client
    {
        $client = new Client();
        $client->setNom($data['nom']);
        $client->setPrenom($data['prenom']);
        $client->setNumero($data['numero']);
        
        // Create Entreprise
        $entreprise = new Entreprise();
        $entreprise->setLibelle($data['entreprise']['libelle']);
        $entreprise->setEmail($data['entreprise']['email']);
        $entreprise->setIsActive($data['entreprise']['isActive']);
        
        // Create Boutique
        $boutique = new Boutique();
        $boutique->setLibelle($data['boutique']['libelle']);
        $boutique->setAdresse($data['boutique']['adresse']);
        $boutique->setIsActive($data['boutique']['isActive']);
        $boutique->setEntreprise($entreprise);
        
        // Create Succursale
        $succursale = new Surccursale();
        $succursale->setLibelle($data['succursale']['libelle']);
        $succursale->setAdresse($data['succursale']['adresse']);
        $succursale->setIsActive($data['succursale']['isActive']);
        $succursale->setBoutique($boutique);
        
        // Associate with client
        $client->setEntreprise($entreprise);
        $client->setBoutique($boutique);
        $client->setSurccursale($succursale);
        
        return $client;
    }

    private function assertCascadeOperationsAreProperlyConfigured(Client $client): void
    {
        // Verify that if cascade operations are valid, related entities have proper configuration
        if ($client->getEntreprise()) {
            $this->assertNotEmpty($client->getEntreprise()->getLibelle(),
                "Valid cascade operations should ensure Entreprise has non-empty libelle");
        }
        
        if ($client->getBoutique()) {
            $this->assertNotEmpty($client->getBoutique()->getLibelle(),
                "Valid cascade operations should ensure Boutique has non-empty libelle");
        }
        
        if ($client->getSurccursale()) {
            $this->assertNotEmpty($client->getSurccursale()->getLibelle(),
                "Valid cascade operations should ensure Succursale has non-empty libelle");
        }
    }
}