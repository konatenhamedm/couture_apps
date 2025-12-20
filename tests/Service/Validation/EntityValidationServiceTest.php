<?php

namespace App\Tests\Service\Validation;

use App\Entity\Client;
use App\Entity\Boutique;
use App\Service\Validation\EntityValidationService;
use App\Service\Validation\ValidationResult;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class EntityValidationServiceTest extends TestCase
{
    private EntityValidationService $validationService;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->validationService = new EntityValidationService($this->entityManager, $this->logger);
    }

    public function testValidateLibelleFieldsWithBoutiqueWithoutLibelle(): void
    {
        $boutique = new Boutique();
        // Ne pas définir de libelle

        $result = $this->validationService->validateLibelleFields($boutique);

        $this->assertFalse($result->isValid());
        $this->assertNotEmpty($result->getErrors());
        $this->assertStringContainsString("libelle", $result->getFormattedErrors());
    }

    public function testValidateLibelleFieldsWithBoutiqueWithLibelle(): void
    {
        $boutique = new Boutique();
        $boutique->setLibelle('Test Boutique');

        $result = $this->validationService->validateLibelleFields($boutique);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    public function testValidateLibelleFieldsWithClientWithoutLibelle(): void
    {
        $client = new Client();
        // Client n'a pas de champ libelle, donc devrait être valide

        $result = $this->validationService->validateLibelleFields($client);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    public function testValidationResultMerge(): void
    {
        $result1 = new ValidationResult(true, [], ['Warning 1']);
        $result2 = new ValidationResult(false, ['Error 1'], ['Warning 2']);

        $result1->merge($result2);

        $this->assertFalse($result1->isValid());
        $this->assertEquals(['Error 1'], $result1->getErrors());
        $this->assertEquals(['Warning 1', 'Warning 2'], $result1->getWarnings());
    }

    public function testValidationResultFormattedMessages(): void
    {
        $result = new ValidationResult(false, ['Error 1', 'Error 2'], ['Warning 1', 'Warning 2']);

        $this->assertEquals('Error 1; Error 2', $result->getFormattedErrors());
        $this->assertEquals('Warning 1; Warning 2', $result->getFormattedWarnings());
    }

    public function testValidationResultAddError(): void
    {
        $result = new ValidationResult(true);
        
        $this->assertTrue($result->isValid());
        
        $result->addError('Test error');
        
        $this->assertFalse($result->isValid());
        $this->assertEquals(['Test error'], $result->getErrors());
    }

    public function testValidationResultAddWarning(): void
    {
        $result = new ValidationResult(true);
        
        $result->addWarning('Test warning');
        
        $this->assertTrue($result->isValid()); // Les warnings ne rendent pas invalide
        $this->assertEquals(['Test warning'], $result->getWarnings());
    }
}