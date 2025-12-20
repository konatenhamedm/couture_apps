<?php

namespace App\Tests\Integration;

use App\Entity\Client;
use App\Entity\Boutique;
use App\Entity\Entreprise;
use App\Service\Validation\EntityValidationServiceInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ClientPersistenceTest extends KernelTestCase
{
    private EntityValidationServiceInterface $validationService;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validationService = static::getContainer()->get(EntityValidationServiceInterface::class);
    }

    public function testValidateClientWithValidBoutique(): void
    {
        $boutique = new Boutique();
        $boutique->setLibelle('Test Boutique'); // Champ requis
        $boutique->setContact('Test Contact');
        $boutique->setSituation('Test Situation');

        $client = new Client();
        $client->setNom('Test');
        $client->setPrenom('Client');
        $client->setNumero('1234567890');
        $client->setBoutique($boutique);

        $result = $this->validationService->validateLibelleFields($boutique);

        $this->assertTrue($result->isValid(), 'Boutique avec libelle devrait être valide: ' . $result->getFormattedErrors());
    }

    public function testValidateClientWithInvalidBoutique(): void
    {
        $boutique = new Boutique();
        // Ne pas définir de libelle - devrait causer une erreur
        $boutique->setContact('Test Contact');
        $boutique->setSituation('Test Situation');

        $result = $this->validationService->validateLibelleFields($boutique);

        $this->assertFalse($result->isValid(), 'Boutique sans libelle devrait être invalide');
        $this->assertStringContainsString('libelle', $result->getFormattedErrors());
    }

    public function testValidateSimpleClient(): void
    {
        $client = new Client();
        $client->setNom('Test');
        $client->setPrenom('Client');
        $client->setNumero('1234567890');

        // Test seulement la validation des champs libelle (Client n'en a pas)
        $result = $this->validationService->validateLibelleFields($client);

        $this->assertTrue($result->isValid(), 'Client simple devrait être valide pour les champs libelle: ' . $result->getFormattedErrors());
    }

    public function testValidateEntrepriseWithLibelle(): void
    {
        $entreprise = new Entreprise();
        $entreprise->setLibelle('Test Entreprise');

        $result = $this->validationService->validateLibelleFields($entreprise);

        $this->assertTrue($result->isValid(), 'Entreprise avec libelle devrait être valide: ' . $result->getFormattedErrors());
    }

    public function testValidateEntrepriseWithoutLibelle(): void
    {
        $entreprise = new Entreprise();
        // Ne pas définir de libelle

        $result = $this->validationService->validateLibelleFields($entreprise);

        $this->assertFalse($result->isValid(), 'Entreprise sans libelle devrait être invalide');
        $this->assertStringContainsString('libelle', $result->getFormattedErrors());
    }
}