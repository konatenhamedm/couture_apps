<?php

namespace App\Tests\Unit;

use App\Entity\Client;
use App\Entity\Boutique;
use App\Entity\Entreprise;
use App\Service\Validation\CascadeOperationValidator;
use App\Service\Validation\EntityValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class CascadeOperationValidatorTest extends TestCase
{
    private EntityManagerInterface|MockObject $entityManager;
    private LoggerInterface|MockObject $logger;
    private EntityValidationService|MockObject $entityValidationService;
    private CascadeOperationValidator $cascadeValidator;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->entityValidationService = $this->createMock(EntityValidationService::class);
        
        $this->cascadeValidator = new CascadeOperationValidator(
            $this->entityManager,
            $this->logger,
            $this->entityValidationService
        );
    }

    public function testValidateCascadeOperationsWithNullEntity(): void
    {
        // Arrange
        $client = new Client();
        $client->setNom('Test');
        $client->setPrenom('Client');
        $client->setNumero('+225 12345678');
        
        // Mock metadata
        $metadata = $this->createMock(\Doctrine\ORM\Mapping\ClassMetadata::class);
        $metadata->method('getAssociationNames')->willReturn([]);
        
        $this->entityManager
            ->method('getClassMetadata')
            ->willReturn($metadata);
        
        // Act
        $result = $this->cascadeValidator->validateCascadeOperations($client);
        
        // Assert
        $this->assertInstanceOf(\App\Service\Validation\ValidationResult::class, $result);
        $this->assertTrue($result->isValid());
    }

    public function testValidateRelatedEntityStatesWithValidEntities(): void
    {
        // Arrange
        $client = new Client();
        $client->setNom('Test');
        $client->setPrenom('Client');
        $client->setNumero('+225 12345678');
        
        $entreprise = new Entreprise();
        $entreprise->setLibelle('Test Entreprise');
        $entreprise->setEmail('test@example.com');
        
        $client->setEntreprise($entreprise);
        
        // Mock metadata
        $metadata = $this->createMock(\Doctrine\ORM\Mapping\ClassMetadata::class);
        $metadata->method('getAssociationNames')->willReturn(['entreprise']);
        
        $this->entityManager
            ->method('getClassMetadata')
            ->willReturn($metadata);
        
        $this->entityManager
            ->method('contains')
            ->willReturn(false);
        
        // Mock validation result
        $validationResult = $this->createMock(\App\Service\Validation\ValidationResult::class);
        $validationResult->method('isValid')->willReturn(true);
        $validationResult->method('getErrors')->willReturn([]);
        
        $this->entityValidationService
            ->method('validateForPersistence')
            ->willReturn($validationResult);
        
        // Act
        $result = $this->cascadeValidator->validateRelatedEntityStates($client);
        
        // Assert
        $this->assertInstanceOf(\App\Service\Validation\ValidationResult::class, $result);
    }

    public function testEnsureEntitiesInSamePersistenceContextWithManagedEntity(): void
    {
        // Arrange
        $client = new Client();
        $client->setNom('Test');
        $client->setPrenom('Client');
        $client->setNumero('+225 12345678');
        
        // Mock metadata
        $metadata = $this->createMock(\Doctrine\ORM\Mapping\ClassMetadata::class);
        $metadata->method('getAssociationNames')->willReturn([]);
        
        $this->entityManager
            ->method('getClassMetadata')
            ->willReturn($metadata);
        
        $this->entityManager
            ->method('contains')
            ->willReturn(true);
        
        // Act
        $result = $this->cascadeValidator->ensureEntitiesInSamePersistenceContext($client);
        
        // Assert
        $this->assertInstanceOf(\App\Service\Validation\ValidationResult::class, $result);
        $this->assertTrue($result->isValid());
    }
}