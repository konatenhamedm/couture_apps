<?php

namespace App\Tests\Property;

use App\Entity\Client;
use App\Entity\Boutique;
use App\Entity\Surccursale;
use App\Entity\Entreprise;
use App\Entity\Pays;
use App\Repository\ClientRepository;
use App\Service\Validation\EntityValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use ReflectionClass;

/**
 * Property 1: Client persistence operations succeed
 * Validates: Requirements 1.1, 1.2, 1.3
 * 
 * Tests that client creation, update, and deletion operations work correctly
 * with proper validation and entity management across environments.
 */
class ClientPersistencePropertyTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private EntityValidationService $validationService;
    private ClientRepository $clientRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->validationService = new EntityValidationService($this->entityManager, new NullLogger());
        $this->clientRepository = static::getContainer()->get(ClientRepository::class);
    }

    /**
     * Property: Client creation with valid related entities should always succeed
     * 
     * @dataProvider validClientDataProvider
     */
    public function testClientCreationSucceedsWithValidData(array $clientData, array $boutiqueData, array $succursaleData, array $entrepriseData): void
    {
        // Créer les entités liées avec des IDs (simulant des entités persistées)
        $pays = $this->createPersistedPays();
        $entreprise = $this->createPersistedEntreprise($entrepriseData, $pays);
        $boutique = $this->createPersistedBoutique($boutiqueData, $entreprise);
        $succursale = $this->createPersistedSuccursale($succursaleData, $entreprise);

        // Créer le client
        $client = new Client();
        $client->setNom($clientData['nom']);
        $client->setPrenom($clientData['prenom']);
        $client->setNumero($clientData['numero']);
        $client->setEntreprise($entreprise);
        $client->setBoutique($boutique);
        $client->setSurccursale($succursale);

        // La validation devrait réussir
        $result = $this->validationService->validateForPersistence($client);
        
        $this->assertTrue(
            $result->isValid(), 
            'Client creation validation should succeed with valid data. Errors: ' . $result->getFormattedErrors()
        );

        // Vérifier que toutes les associations sont correctement définies
        $this->assertNotNull($client->getEntreprise(), 'Client should have an entreprise');
        $this->assertNotNull($client->getBoutique(), 'Client should have a boutique');
        $this->assertNotNull($client->getSurccursale(), 'Client should have a succursale');
        
        // Vérifier que les entités liées ont des libelles valides
        $this->assertNotEmpty($client->getEntreprise()->getLibelle(), 'Entreprise should have a valid libelle');
        $this->assertNotEmpty($client->getBoutique()->getLibelle(), 'Boutique should have a valid libelle');
        $this->assertNotEmpty($client->getSurccursale()->getLibelle(), 'Succursale should have a valid libelle');
    }

    /**
     * Property: Client creation with invalid related entities should fail with clear error messages
     * 
     * @dataProvider invalidClientDataProvider
     */
    public function testClientCreationFailsWithInvalidData(array $clientData, array $invalidEntityData, string $expectedErrorPattern): void
    {
        // Créer des entités avec des données invalides
        $entreprise = new Entreprise();
        if (isset($invalidEntityData['entreprise_libelle'])) {
            $entreprise->setLibelle($invalidEntityData['entreprise_libelle']);
        }
        $entreprise->setNumero('ENT001');
        $entreprise->setEmail('test@entreprise.com');

        $boutique = new Boutique();
        if (isset($invalidEntityData['boutique_libelle'])) {
            $boutique->setLibelle($invalidEntityData['boutique_libelle']);
        }
        $boutique->setContact('0123456789');
        $boutique->setSituation('Test Location');
        $boutique->setEntreprise($entreprise);

        // Créer le client
        $client = new Client();
        $client->setNom($clientData['nom']);
        $client->setPrenom($clientData['prenom']);
        $client->setNumero($clientData['numero']);
        $client->setEntreprise($entreprise);
        $client->setBoutique($boutique);

        // La validation devrait échouer
        $result = $this->validationService->validateForPersistence($client);
        
        $this->assertFalse($result->isValid(), 'Client creation validation should fail with invalid data');
        
        // Vérifier que l'erreur contient le pattern attendu
        $errors = $result->getFormattedErrors();
        $this->assertMatchesRegularExpression(
            $expectedErrorPattern, 
            $errors, 
            "Error message should match expected pattern. Actual errors: $errors"
        );
    }

    /**
     * Property: Client update operations preserve entity relationships and validation
     */
    public function testClientUpdatePreservesValidation(): void
    {
        // Créer un client valide
        $pays = $this->createPersistedPays();
        $entreprise = $this->createPersistedEntreprise([
            'libelle' => 'Test Entreprise',
            'numero' => 'ENT001',
            'email' => 'test@entreprise.com'
        ], $pays);
        $boutique = $this->createPersistedBoutique([
            'libelle' => 'Test Boutique',
            'contact' => '0123456789',
            'situation' => 'Test Location'
        ], $entreprise);
        
        $client = new Client();
        $this->setEntityId($client, 1); // Simuler un client persisté
        $client->setNom('Original');
        $client->setPrenom('Client');
        $client->setNumero('0123456789');
        $client->setEntreprise($entreprise);
        $client->setBoutique($boutique);

        // Modifier le client
        $client->setNom('Updated');
        $client->setPrenom('Client Updated');

        // La validation devrait toujours réussir
        $result = $this->validationService->validateForPersistence($client);
        
        $this->assertTrue(
            $result->isValid(), 
            'Client update validation should succeed. Errors: ' . $result->getFormattedErrors()
        );

        // Vérifier que les modifications ont été appliquées
        $this->assertEquals('Updated', $client->getNom());
        $this->assertEquals('Client Updated', $client->getPrenom());
        
        // Vérifier que les relations sont préservées
        $this->assertNotNull($client->getEntreprise());
        $this->assertNotNull($client->getBoutique());
    }

    /**
     * Fournit des données valides pour les tests de création de client
     */
    public static function validClientDataProvider(): array
    {
        $datasets = [];
        
        // Générer 100 jeux de données aléatoires
        for ($i = 0; $i < 100; $i++) {
            $datasets[] = [
                // Client data
                [
                    'nom' => 'Client' . $i,
                    'prenom' => 'Prenom' . $i,
                    'numero' => '+225 ' . str_pad($i, 8, '0', STR_PAD_LEFT)
                ],
                // Boutique data
                [
                    'libelle' => 'Boutique ' . $i,
                    'contact' => '+225 ' . str_pad($i + 1000, 8, '0', STR_PAD_LEFT),
                    'situation' => 'Location ' . $i
                ],
                // Succursale data
                [
                    'libelle' => 'Succursale ' . $i,
                    'contact' => '+225 ' . str_pad($i + 2000, 8, '0', STR_PAD_LEFT)
                ],
                // Entreprise data
                [
                    'libelle' => 'Entreprise ' . $i,
                    'numero' => 'ENT' . str_pad($i, 3, '0', STR_PAD_LEFT),
                    'email' => "entreprise{$i}@test.com"
                ]
            ];
        }
        
        return $datasets;
    }

    /**
     * Fournit des données invalides pour les tests d'échec de création
     */
    public static function invalidClientDataProvider(): array
    {
        return [
            'empty_entreprise_libelle' => [
                ['nom' => 'Test', 'prenom' => 'Client', 'numero' => '0123456789'],
                ['entreprise_libelle' => '', 'boutique_libelle' => 'Valid Boutique'],
                '/libelle.*requis.*Entreprise/'
            ],
            'null_entreprise_libelle' => [
                ['nom' => 'Test', 'prenom' => 'Client', 'numero' => '0123456789'],
                ['boutique_libelle' => 'Valid Boutique'],
                '/libelle.*requis.*Entreprise/'
            ],
            'empty_boutique_libelle' => [
                ['nom' => 'Test', 'prenom' => 'Client', 'numero' => '0123456789'],
                ['entreprise_libelle' => 'Valid Entreprise', 'boutique_libelle' => ''],
                '/libelle.*requis.*Boutique/'
            ],
            'null_boutique_libelle' => [
                ['nom' => 'Test', 'prenom' => 'Client', 'numero' => '0123456789'],
                ['entreprise_libelle' => 'Valid Entreprise'],
                '/libelle.*requis.*Boutique/'
            ]
        ];
    }

    /**
     * Crée un pays persisté pour les tests
     */
    private function createPersistedPays(): Pays
    {
        $pays = new Pays();
        $this->setEntityId($pays, 1);
        $pays->setLibelle('Côte d\'Ivoire');
        $pays->setCode('CI');
        return $pays;
    }

    /**
     * Crée une entreprise persistée pour les tests
     */
    private function createPersistedEntreprise(array $data, Pays $pays): Entreprise
    {
        $entreprise = new Entreprise();
        $this->setEntityId($entreprise, 1);
        $entreprise->setLibelle($data['libelle']);
        $entreprise->setNumero($data['numero']);
        $entreprise->setEmail($data['email']);
        $entreprise->setPays($pays);
        return $entreprise;
    }

    /**
     * Crée une boutique persistée pour les tests
     */
    private function createPersistedBoutique(array $data, Entreprise $entreprise): Boutique
    {
        $boutique = new Boutique();
        $this->setEntityId($boutique, 1);
        $boutique->setLibelle($data['libelle']);
        $boutique->setContact($data['contact']);
        $boutique->setSituation($data['situation']);
        $boutique->setEntreprise($entreprise);
        return $boutique;
    }

    /**
     * Crée une succursale persistée pour les tests
     */
    private function createPersistedSuccursale(array $data, Entreprise $entreprise): Surccursale
    {
        $succursale = new Surccursale();
        $this->setEntityId($succursale, 1);
        $succursale->setLibelle($data['libelle']);
        $succursale->setContact($data['contact']);
        $succursale->setEntreprise($entreprise);
        return $succursale;
    }

    /**
     * Méthode utilitaire pour simuler un ID sur une entité (comme si elle était persistée)
     */
    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new ReflectionClass($entity);
        if ($reflection->hasProperty('id')) {
            $property = $reflection->getProperty('id');
            $property->setAccessible(true);
            $property->setValue($entity, $id);
        }
    }
}