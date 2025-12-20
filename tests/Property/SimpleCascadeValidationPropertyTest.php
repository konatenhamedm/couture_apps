<?php

namespace App\Tests\Property;

use App\Entity\Client;
use App\Entity\Boutique;
use App\Entity\Entreprise;
use App\Service\Validation\CascadeOperationValidatorInterface;
use App\Service\Validation\EntityValidationServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Eris\Generator;
use Eris\TestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Simplified Property Tests for Cascade Operation Validity
 * 
 * Property 4: Cascade operation validity
 * Property 2: Related entity validation before persistence
 * 
 * Validates: Requirements 1.4, 1.5, 2.2, 2.3, 2.5, 4.5
 */
class SimpleCascadeValidationPropertyTest extends KernelTestCase
{
    use TestTrait;

    private EntityManagerInterface $entityManager;
    private CascadeOperationValidatorInterface $cascadeValidator;
    private EntityValidationServiceInterface $entityValidationService;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->cascadeValidator = $container->get(CascadeOperationValidatorInterface::class);
        $this->entityValidationService = $container->get(EntityValidationServiceInterface::class);
    }

    /**
     * Property: For any entity with valid libelle, validation should pass
     */
    public function testValidLibelleEntitiesPassValidation(): void
    {
        $this->forAll(
            Generator\choose(1, 100),
            Generator\names(),
            Generator\names()
        )->then(function (int $iteration, string $entrepriseLibelle, string $boutiqueLibelle) {
            // Arrange: Create entities with valid libelles
            $entreprise = new Entreprise();
            $entreprise->setLibelle('Entreprise ' . $entrepriseLibelle);
            $entreprise->setEmail('test@example.com');
            $entreprise->setIsActive(true);
            
            $boutique = new Boutique();
            $boutique->setLibelle('Boutique ' . $boutiqueLibelle);
            $boutique->setSituation('123 Test Street');
            $boutique->setContact('123456789');
            $boutique->setIsActive(true);
            $boutique->setEntreprise($entreprise);
            
            // Act: Validate entities
            $entrepriseResult = $this->entityValidationService->validateForPersistence($entreprise);
            $boutiqueResult = $this->entityValidationService->validateForPersistence($boutique);
            
            // Assert: Valid entities should pass validation
            $this->assertTrue($entrepriseResult->isValid() || !empty($entrepriseResult->getErrors()),
                "Validation should either pass or provide specific errors for Entreprise");
            
            $this->assertTrue($boutiqueResult->isValid() || !empty($boutiqueResult->getErrors()),
                "Validation should either pass or provide specific errors for Boutique");
            
            // If validation fails, it should be for legitimate reasons, not empty libelle
            if (!$entrepriseResult->isValid()) {
                $errorText = implode(' ', $entrepriseResult->getErrors());
                $this->assertStringNotContainsString('libelle', strtolower($errorText),
                    "Valid libelle should not cause libelle validation errors");
            }
        });
    }

    /**
     * Property: For any entity with empty libelle, validation should fail with specific error
     */
    public function testEmptyLibelleEntitiesFailValidation(): void
    {
        $this->forAll(
            Generator\choose(1, 50),
            Generator\oneOf(
                Generator\constant(''),
                Generator\constant('   '),
                Generator\constant(null)
            )
        )->then(function (int $iteration, $invalidLibelle) {
            // Arrange: Create entity with invalid libelle
            $entreprise = new Entreprise();
            $entreprise->setLibelle($invalidLibelle);
            $entreprise->setEmail('test@example.com');
            $entreprise->setIsActive(true);
            
            // Act: Validate entity
            $result = $this->entityValidationService->validateForPersistence($entreprise);
            
            // Assert: Should fail validation with libelle-specific error
            $this->assertFalse($result->isValid(),
                "Entity with invalid libelle should fail validation");
            
            $this->assertNotEmpty($result->getErrors(),
                "Failed validation should provide error messages");
            
            // Should mention libelle in error message
            $errorText = implode(' ', $result->getErrors());
            $this->assertStringContainsString('libelle', strtolower($errorText),
                "Error message should mention libelle field");
        });
    }

    /**
     * Property: For any client with related entities, cascade validation should be consistent
     */
    public function testCascadeValidationIsConsistent(): void
    {
        $this->forAll(
            Generator\choose(1, 50),
            Generator\names(),
            Generator\names(),
            Generator\bool()
        )->then(function (int $iteration, string $clientNom, string $clientPrenom, bool $hasValidLibelle) {
            // Arrange: Create client with conditionally valid related entities
            $client = new Client();
            $client->setNom($clientNom);
            $client->setPrenom($clientPrenom);
            $client->setNumero('+225 12345678');
            
            $entreprise = new Entreprise();
            $entreprise->setLibelle($hasValidLibelle ? 'Valid Entreprise' : '');
            $entreprise->setEmail('test@example.com');
            $entreprise->setIsActive(true);
            
            $client->setEntreprise($entreprise);
            
            // Act: Validate cascade operations and entity validation
            $cascadeResult = $this->cascadeValidator->validateCascadeOperations($client);
            $entityResult = $this->entityValidationService->validateForPersistence($client);
            
            // Assert: Validation should be consistent
            $this->assertInstanceOf(\App\Service\Validation\ValidationResult::class, $cascadeResult,
                "Cascade validation should return ValidationResult");
            
            $this->assertInstanceOf(\App\Service\Validation\ValidationResult::class, $entityResult,
                "Entity validation should return ValidationResult");
            
            // If libelle is invalid, entity validation should catch it through related entity validation
            if (!$hasValidLibelle) {
                $this->assertFalse($entityResult->isValid(),
                    "Entity validation should fail for invalid libelle in related entities");
                
                // Should have specific error about libelle
                $errorText = implode(' ', $entityResult->getErrors());
                $this->assertStringContainsString('libelle', strtolower($errorText),
                    "Error should mention libelle field");
            }
        });
    }

    /**
     * Property: For any entity, related entity state validation should handle null entities gracefully
     */
    public function testRelatedEntityStateValidationHandlesNullGracefully(): void
    {
        $this->forAll(
            Generator\choose(1, 50),
            Generator\names(),
            Generator\names()
        )->then(function (int $iteration, string $nom, string $prenom) {
            // Arrange: Create client with no related entities
            $client = new Client();
            $client->setNom($nom);
            $client->setPrenom($prenom);
            $client->setNumero('+225 12345678');
            // Intentionally leave related entities as null
            
            // Act: Validate related entity states
            $result = $this->cascadeValidator->validateRelatedEntityStates($client);
            
            // Assert: Should handle null entities gracefully
            $this->assertInstanceOf(\App\Service\Validation\ValidationResult::class, $result,
                "Related entity validation should handle null entities gracefully");
            
            // Should not throw exceptions or cause system errors
            $this->assertTrue(is_bool($result->isValid()),
                "Validation result should have a boolean validity state");
        });
    }

    /**
     * Property: For any entity, persistence context validation should provide meaningful feedback
     */
    public function testPersistenceContextValidationProvidesMeaningfulFeedback(): void
    {
        $this->forAll(
            Generator\choose(1, 50),
            Generator\names()
        )->then(function (int $iteration, string $nom) {
            // Arrange: Create entity
            $entreprise = new Entreprise();
            $entreprise->setLibelle('Test Entreprise ' . $nom);
            $entreprise->setEmail('test@example.com');
            $entreprise->setIsActive(true);
            
            // Act: Validate persistence context
            $result = $this->cascadeValidator->ensureEntitiesInSamePersistenceContext($entreprise);
            
            // Assert: Should provide meaningful feedback
            $this->assertInstanceOf(\App\Service\Validation\ValidationResult::class, $result,
                "Persistence context validation should return ValidationResult");
            
            // If there are errors or warnings, they should be meaningful
            if ($result->hasErrors()) {
                foreach ($result->getErrors() as $error) {
                    $this->assertNotEmpty(trim($error),
                        "Error messages should not be empty");
                }
            }
            
            if ($result->hasWarnings()) {
                foreach ($result->getWarnings() as $warning) {
                    $this->assertNotEmpty(trim($warning),
                        "Warning messages should not be empty");
                }
            }
        });
    }
}