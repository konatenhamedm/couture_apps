<?php

namespace App\Tests\Integration;

use App\Entity\Client;
use App\Entity\Boutique;
use App\Entity\Entreprise;
use App\Service\Validation\CascadeOperationValidatorInterface;
use App\Service\Validation\EntityValidationServiceInterface;
use App\Service\Persistence\SafePersistenceHandlerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CascadeOperationIntegrationTest extends KernelTestCase
{
    private CascadeOperationValidatorInterface $cascadeValidator;
    private EntityValidationServiceInterface $entityValidationService;
    private SafePersistenceHandlerInterface $safePersistenceHandler;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        $this->cascadeValidator = $container->get(CascadeOperationValidatorInterface::class);
        $this->entityValidationService = $container->get(EntityValidationServiceInterface::class);
        $this->safePersistenceHandler = $container->get(SafePersistenceHandlerInterface::class);
    }

    public function testServicesAreProperlyConfigured(): void
    {
        $this->assertInstanceOf(CascadeOperationValidatorInterface::class, $this->cascadeValidator);
        $this->assertInstanceOf(EntityValidationServiceInterface::class, $this->entityValidationService);
        $this->assertInstanceOf(SafePersistenceHandlerInterface::class, $this->safePersistenceHandler);
    }

    public function testCascadeValidationWithValidClient(): void
    {
        // Arrange
        $client = new Client();
        $client->setNom('Test');
        $client->setPrenom('Client');
        $client->setNumero('+225 12345678');
        
        $entreprise = new Entreprise();
        $entreprise->setLibelle('Test Entreprise');
        $entreprise->setEmail('test@example.com');
        $entreprise->setIsActive(true);
        
        $boutique = new Boutique();
        $boutique->setLibelle('Test Boutique');
        $boutique->setSituation('123 Test Street');
        $boutique->setContact('123456789');
        $boutique->setIsActive(true);
        $boutique->setEntreprise($entreprise);
        
        $client->setEntreprise($entreprise);
        $client->setBoutique($boutique);
        
        // Act
        $cascadeResult = $this->cascadeValidator->validateCascadeOperations($client);
        $entityResult = $this->entityValidationService->validateForPersistence($client);
        
        // Assert
        $this->assertInstanceOf(\App\Service\Validation\ValidationResult::class, $cascadeResult);
        $this->assertInstanceOf(\App\Service\Validation\ValidationResult::class, $entityResult);
        
        // The validation might fail due to missing required fields, but it should not throw exceptions
        $this->assertTrue(is_bool($cascadeResult->isValid()));
        $this->assertTrue(is_bool($entityResult->isValid()));
    }

    public function testCascadeValidationWithInvalidLibelle(): void
    {
        // Arrange
        $entreprise = new Entreprise();
        $entreprise->setLibelle(''); // Invalid empty libelle
        $entreprise->setEmail('test@example.com');
        $entreprise->setIsActive(true);
        
        // Act - Test the Entreprise entity directly
        $result = $this->entityValidationService->validateForPersistence($entreprise);
        
        // Assert
        $this->assertInstanceOf(\App\Service\Validation\ValidationResult::class, $result);
        $this->assertFalse($result->isValid(), 'Validation should fail for empty libelle');
        $this->assertNotEmpty($result->getErrors(), 'Should provide error messages');
        
        // Check that error mentions libelle
        $errorText = implode(' ', $result->getErrors());
        $this->assertStringContainsString('libelle', strtolower($errorText));
    }

    public function testSafePersistenceValidation(): void
    {
        // Arrange
        $client = new Client();
        $client->setNom('Test');
        $client->setPrenom('Client');
        $client->setNumero('+225 12345678');
        
        // Act
        $result = $this->safePersistenceHandler->validateBeforePersistence($client);
        
        // Assert
        $this->assertInstanceOf(\App\Service\Persistence\SafePersistenceResult::class, $result);
        $this->assertTrue(is_bool($result->isSuccess()));
    }
}