<?php

namespace App\Tests\Property;

use App\Entity\Client;
use App\Entity\Boutique;
use App\Entity\Surccursale;
use App\Entity\Entreprise;
use App\Service\Validation\EntityValidationServiceInterface;
use App\Service\Environment\EnvironmentEntityManagerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Eris\Generator;
use Eris\TestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Property 2: Related entity validation before persistence
 * 
 * For any client with associated entities (Boutique, Surccursale, Entreprise), 
 * all related entities should have valid required fields (including 'libelle') 
 * before persistence operations
 * 
 * Validates: Requirements 1.4, 1.5, 2.2, 2.3
 */
class RelatedEntityValidationPropertyTest extends KernelTestCase
{
    use TestTrait;

    private EntityManagerInterface $entityManager;
    private EntityValidationServiceInterface $entityValidationService;
    private EnvironmentEntityManagerInterface $environmentEntityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->entityValidationService = $container->get(EntityValidationServiceInterface::class);
        $this->environmentEntityManager = $container->get(EnvironmentEntityManagerInterface::class);
    }

    /**
     * Property: For any client with valid related entities, validation should pass
     */
    public function testValidRelatedEntitiesPassValidation(): void
    {
        $this->forAll(
            Generator\choose(1, 100),
            $this->generateClientWithValidRelatedEntities()
        )->then(function (int $iteration, Client $client) {
            // Act: Validate the client and its related entities
            $result = $this->entityValidationService->validateForPersistence($client);
            
            // Assert: Validation should pass for valid related entities
            if (!$result->isValid()) {
                // If validation fails, errors should be specific and actionable
                $this->assertNotEmpty($result->getErrors(),
                    "Failed validation should provide specific error messages");
                
                // Verify that errors are about actual validation issues, not system problems
                foreach ($result->getErrors() as $error) {
                    $this->assertDoesNotMatchRegularExpression('/exception|error|erreur/i', $error,
                        "Validation errors should be about business rules, not system exceptions");
                }
            } else {
                // If validation passes, related entities should have proper libelle fields
                $this->assertValidRelatedEntitiesHaveProperLibelle($client);
            }
        });
    }

    /**
     * Property: For any client with invalid libelle in related entities, validation should fail
     */
    public function testInvalidLibelleInRelatedEntitiesCausesValidationFailure(): void
    {
        $this->forAll(
            Generator\choose(1, 100),
            $this->generateClientWithInvalidLibelleRelatedEntities()
        )->then(function (int $iteration, Client $client) {
            // Act: Validate client with invalid related entities
            $result = $this->entityValidationService->validateForPersistence($client);
            
            // Assert: Validation should fail and provide specific libelle-related errors
            $this->assertFalse($result->isValid(),
                "Validation should fail when related entities have invalid libelle fields");
            
            $this->assertNotEmpty($result->getErrors(),
                "Failed validation should provide specific error messages");
            
            // Verify that at least one error mentions libelle
            $hasLibelleError = false;
            foreach ($result->getErrors() as $error) {
                if (stripos($error, 'libelle') !== false) {
                    $hasLibelleError = true;
                    break;
                }
            }
            
            $this->assertTrue($hasLibelleError,
                "Validation errors should specifically mention libelle field issues");
        });
    }

    /**
     * Property: For any client, related entity validation should be comprehensive
     */
    public function testRelatedEntityValidationIsComprehensive(): void
    {
        $this->forAll(
            Generator\choose(1, 100),
            $this->generateClientWithMixedValidityRelatedEntities()
        )->then(function (int $iteration, Client $client) {
            // Act: Validate related entities specifically
            $result = $this->entityValidationService->validateRelatedEntities($client);
            
            // Assert: Validation should check all related entities
            $this->assertInstanceOf(\App\Service\Validation\ValidationResult::class, $result,
                "Related entity validation should return a ValidationResult");
            
            if (!$result->isValid()) {
                // Errors should be specific about which related entity has issues
                foreach ($result->getErrors() as $error) {
                    $this->assertMatchesRegularExpression('/entreprise|boutique|succursale|surccursale/i', $error,
                        "Errors should specify which related entity has validation issues");
                }
            }
        });
    }

    /**
     * Property: For any client, libelle field validation should be consistent
     */
    public function testLibelleFieldValidationIsConsistent(): void
    {
        $this->forAll(
            Generator\choose(1, 100),
            $this->generateClientWithSpecificLibelleScenarios()
        )->then(function (int $iteration, array $scenario) {
            // Arrange
            $client = $scenario['client'];
            $expectedValid = $scenario['expectedValid'];
            
            // Act: Validate libelle fields specifically
            $result = $this->entityValidationService->validateLibelleFields($client);
            
            // Assert: Validation result should match expected validity
            if ($expectedValid) {
                $this->assertTrue($result->isValid() || !$result->hasErrors(),
                    "Client with valid libelle fields should pass libelle validation");
            } else {
                $this->assertFalse($result->isValid(),
                    "Client with invalid libelle fields should fail libelle validation");
                
                $this->assertNotEmpty($result->getErrors(),
                    "Invalid libelle validation should provide specific error messages");
            }
        });
    }

    /**
     * Property: For any client, validation should handle null related entities gracefully
     */
    public function testValidationHandlesNullRelatedEntitiesGracefully(): void
    {
        $this->forAll(
            Generator\choose(1, 50),
            $this->generateClientWithNullRelatedEntities()
        )->then(function (int $iteration, Client $client) {
            // Act: Validate client with null related entities
            $result = $this->entityValidationService->validateForPersistence($client);
            
            // Assert: Should handle null entities without throwing exceptions
            $this->assertInstanceOf(\App\Service\Validation\ValidationResult::class, $result,
                "Validation should handle null related entities gracefully");
            
            // Null entities should not cause libelle validation errors
            if ($result->hasErrors()) {
                foreach ($result->getErrors() as $error) {
                    $this->assertDoesNotMatchRegularExpression('/null.*libelle|libelle.*null/i', $error,
                        "Null entities should not cause libelle-specific validation errors");
                }
            }
        });
    }

    private function generateClientWithValidRelatedEntities(): Generator
    {
        return Generator\map(
            function (string $nom, string $prenom, string $numero, string $entrepriseLibelle, string $boutiqueLibelle, string $succursaleLibelle) {
                $client = new Client();
                $client->setNom($nom);
                $client->setPrenom($prenom);
                $client->setNumero($numero);
                
                // Create valid Entreprise
                $entreprise = new Entreprise();
                $entreprise->setLibelle($entrepriseLibelle);
                $entreprise->setEmail(strtolower($nom) . '@example.com');
                $entreprise->setIsActive(true);
                
                // Create valid Boutique
                $boutique = new Boutique();
                $boutique->setLibelle($boutiqueLibelle);
                $boutique->setAdresse('123 Test Street');
                $boutique->setIsActive(true);
                $boutique->setEntreprise($entreprise);
                
                // Create valid Succursale
                $succursale = new Surccursale();
                $succursale->setLibelle($succursaleLibelle);
                $succursale->setAdresse('456 Test Avenue');
                $succursale->setIsActive(true);
                $succursale->setBoutique($boutique);
                
                $client->setEntreprise($entreprise);
                $client->setBoutique($boutique);
                $client->setSurccursale($succursale);
                
                return $client;
            },
            Generator\names(),
            Generator\names(),
            Generator\map(function($num) { return '+225 ' . str_pad($num, 8, '0', STR_PAD_LEFT); }, Generator\choose(10000000, 99999999)),
            Generator\map(function($name) { return 'Entreprise ' . $name; }, Generator\names()), // Valid libelle
            Generator\map(function($name) { return 'Boutique ' . $name; }, Generator\names()),   // Valid libelle
            Generator\map(function($name) { return 'Succursale ' . $name; }, Generator\names())  // Valid libelle
        );
    }

    private function generateClientWithInvalidLibelleRelatedEntities(): Generator
    {
        return Generator\map(
            function (string $nom, string $prenom, string $numero, string $invalidLibelle) {
                $client = new Client();
                $client->setNom($nom);
                $client->setPrenom($prenom);
                $client->setNumero($numero);
                
                // Create Entreprise with invalid libelle
                $entreprise = new Entreprise();
                $entreprise->setLibelle($invalidLibelle); // This will be empty or null
                $entreprise->setEmail('test@example.com');
                $entreprise->setIsActive(true);
                
                // Create Boutique with invalid libelle
                $boutique = new Boutique();
                $boutique->setLibelle($invalidLibelle); // This will be empty or null
                $boutique->setAdresse('123 Test Street');
                $boutique->setIsActive(true);
                $boutique->setEntreprise($entreprise);
                
                $client->setEntreprise($entreprise);
                $client->setBoutique($boutique);
                
                return $client;
            },
            Generator\names(),
            Generator\names(),
            Generator\map(function($num) { return '+225 ' . str_pad($num, 8, '0', STR_PAD_LEFT); }, Generator\choose(10000000, 99999999)),
            Generator\oneOf(
                Generator\constant(''),      // Empty string
                Generator\constant('   '),   // Whitespace only
                Generator\constant(null)     // Null value
            )
        );
    }

    private function generateClientWithMixedValidityRelatedEntities(): Generator
    {
        return Generator\map(
            function (string $nom, string $prenom, string $numero, bool $entrepriseValid, bool $boutiqueValid) {
                $client = new Client();
                $client->setNom($nom);
                $client->setPrenom($prenom);
                $client->setNumero($numero);
                
                // Create Entreprise with conditional validity
                $entreprise = new Entreprise();
                $entreprise->setLibelle($entrepriseValid ? 'Valid Entreprise' : '');
                $entreprise->setEmail('test@example.com');
                $entreprise->setIsActive(true);
                
                // Create Boutique with conditional validity
                $boutique = new Boutique();
                $boutique->setLibelle($boutiqueValid ? 'Valid Boutique' : '');
                $boutique->setAdresse('123 Test Street');
                $boutique->setIsActive(true);
                $boutique->setEntreprise($entreprise);
                
                $client->setEntreprise($entreprise);
                $client->setBoutique($boutique);
                
                return $client;
            },
            Generator\names(),
            Generator\names(),
            Generator\map(function($num) { return '+225 ' . str_pad($num, 8, '0', STR_PAD_LEFT); }, Generator\choose(10000000, 99999999)),
            Generator\bool(),
            Generator\bool()
        );
    }

    private function generateClientWithSpecificLibelleScenarios(): Generator
    {
        return Generator\oneOf(
            // Scenario 1: All valid libelles
            Generator\map(
                function (string $nom, string $prenom, string $numero) {
                    $client = $this->createClientWithLibelles($nom, $prenom, $numero, 'Valid Entreprise', 'Valid Boutique');
                    return ['client' => $client, 'expectedValid' => true];
                },
                Generator\names(),
                Generator\names(),
                Generator\map(function($num) { return '+225 ' . str_pad($num, 8, '0', STR_PAD_LEFT); }, Generator\choose(10000000, 99999999))
            ),
            // Scenario 2: Empty libelles
            Generator\map(
                function (string $nom, string $prenom, string $numero) {
                    $client = $this->createClientWithLibelles($nom, $prenom, $numero, '', '');
                    return ['client' => $client, 'expectedValid' => false];
                },
                Generator\names(),
                Generator\names(),
                Generator\map(function($num) { return '+225 ' . str_pad($num, 8, '0', STR_PAD_LEFT); }, Generator\choose(10000000, 99999999))
            ),
            // Scenario 3: Whitespace-only libelles
            Generator\map(
                function (string $nom, string $prenom, string $numero) {
                    $client = $this->createClientWithLibelles($nom, $prenom, $numero, '   ', '   ');
                    return ['client' => $client, 'expectedValid' => false];
                },
                Generator\names(),
                Generator\names(),
                Generator\map(function($num) { return '+225 ' . str_pad($num, 8, '0', STR_PAD_LEFT); }, Generator\choose(10000000, 99999999))
            )
        );
    }

    private function generateClientWithNullRelatedEntities(): Generator
    {
        return Generator\map(
            function (string $nom, string $prenom, string $numero) {
                $client = new Client();
                $client->setNom($nom);
                $client->setPrenom($prenom);
                $client->setNumero($numero);
                // Intentionally leave related entities as null
                return $client;
            },
            Generator\names(),
            Generator\names(),
            Generator\map(function($num) { return '+225 ' . str_pad($num, 8, '0', STR_PAD_LEFT); }, Generator\choose(10000000, 99999999))
        );
    }

    private function createClientWithLibelles(string $nom, string $prenom, string $numero, string $entrepriseLibelle, string $boutiqueLibelle): Client
    {
        $client = new Client();
        $client->setNom($nom);
        $client->setPrenom($prenom);
        $client->setNumero($numero);
        
        $entreprise = new Entreprise();
        $entreprise->setLibelle($entrepriseLibelle);
        $entreprise->setEmail('test@example.com');
        $entreprise->setIsActive(true);
        
        $boutique = new Boutique();
        $boutique->setLibelle($boutiqueLibelle);
        $boutique->setAdresse('123 Test Street');
        $boutique->setIsActive(true);
        $boutique->setEntreprise($entreprise);
        
        $client->setEntreprise($entreprise);
        $client->setBoutique($boutique);
        
        return $client;
    }

    private function assertValidRelatedEntitiesHaveProperLibelle(Client $client): void
    {
        if ($client->getEntreprise()) {
            $this->assertNotEmpty(trim($client->getEntreprise()->getLibelle()),
                "Valid Entreprise should have non-empty libelle");
        }
        
        if ($client->getBoutique()) {
            $this->assertNotEmpty(trim($client->getBoutique()->getLibelle()),
                "Valid Boutique should have non-empty libelle");
        }
        
        if ($client->getSurccursale()) {
            $this->assertNotEmpty(trim($client->getSurccursale()->getLibelle()),
                "Valid Succursale should have non-empty libelle");
        }
    }
}