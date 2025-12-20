<?php

namespace App\Tests\Controller\ApiClient;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Client;
use App\Entity\Boutique;
use App\Entity\Surccursale;
use App\Entity\Entreprise;
use App\Entity\User;
use App\Entity\TypeUser;
use App\Entity\Pays;
use App\Entity\Abonnement;
use App\Entity\ModuleAbonnement;
use App\Service\JwtService;

/**
 * Base class for API Client Controller tests
 * Provides common setup, utilities, and helper methods
 */
abstract class ApiClientTestBase extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $entityManager;
    protected JwtService $jwtService;
    protected array $testData = [];
    
    /**
     * Common setup for all API Client tests
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->jwtService = static::getContainer()->get(JwtService::class);
        
        // Start a database transaction for test isolation
        $this->entityManager->beginTransaction();
        
        // Set up test environment
        $this->setupTestEnvironment();
    }
    
    /**
     * Clean up after each test
     */
    protected function tearDown(): void
    {
        // Rollback the transaction to clean up test data
        if ($this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->rollback();
        }
        
        // Clean up test files
        $this->cleanupTestFiles();
        
        parent::tearDown();
    }
    
    /**
     * Set up the test environment with basic entities
     */
    protected function setupTestEnvironment(): void
    {
        // Create test pays (country)
        $pays = new Pays();
        $pays->setLibelle('Côte d\'Ivoire');
        $pays->setCode('CI');
        $pays->setIndicatif('+225');
        $pays->setActif(true);
        $pays->setIsActive(true);
        $this->entityManager->persist($pays);
        
        // Create test entreprise
        $entreprise = new Entreprise();
        $entreprise->setLibelle('Test Entreprise');
        $entreprise->setEmail('test@entreprise.com');
        $entreprise->setNumero('+225 0123456789');
        $entreprise->setPays($pays);
        $entreprise->setIsActive(true);
        $this->entityManager->persist($entreprise);
        
        // Create test boutique
        $boutique = new Boutique();
        $boutique->setLibelle('Test Boutique');
        $boutique->setSituation('456 Boutique Street');
        $boutique->setContact('+225 0987654321');
        $boutique->setEntreprise($entreprise);
        $boutique->setIsActive(true);
        $this->entityManager->persist($boutique);
        
        // Create test succursale
        $succursale = new Surccursale();
        $succursale->setLibelle('Test Succursale');
        $succursale->setContact('+225 0555666777');
        $succursale->setEntreprise($entreprise);
        $succursale->setIsActive(true);
        $this->entityManager->persist($succursale);
        
        // Note: Surccursale doesn't have a direct boutique relationship in the entity
        // but the controller expects it. This might need to be addressed in the entity model.
        
        // Create test user types
        $sadmType = new TypeUser();
        $sadmType->setCode('SADM');
        $sadmType->setLibelle('Super Admin');
        $sadmType->setIsActive(true);
        $this->entityManager->persist($sadmType);
        
        $adbType = new TypeUser();
        $adbType->setCode('ADB');
        $adbType->setLibelle('Admin Boutique');
        $adbType->setIsActive(true);
        $this->entityManager->persist($adbType);
        
        $regularType = new TypeUser();
        $regularType->setCode('REG');
        $regularType->setLibelle('Utilisateur Regular');
        $regularType->setIsActive(true);
        $this->entityManager->persist($regularType);
        
        // Create test users
        $superAdmin = new User();
        $superAdmin->setLogin('sadm@test.com');
        $superAdmin->setNom('Super');
        $superAdmin->setPrenoms('Admin');
        $superAdmin->setPassword('$2y$13$test'); // Mock password hash
        $superAdmin->setType($sadmType);
        $superAdmin->setEntreprise($entreprise);
        $superAdmin->setIsActive(true);
        $this->entityManager->persist($superAdmin);
        
        $boutiqueAdmin = new User();
        $boutiqueAdmin->setLogin('adb@test.com');
        $boutiqueAdmin->setNom('Boutique');
        $boutiqueAdmin->setPrenoms('Admin');
        $boutiqueAdmin->setPassword('$2y$13$test'); // Mock password hash
        $boutiqueAdmin->setType($adbType);
        $boutiqueAdmin->setBoutique($boutique);
        $boutiqueAdmin->setEntreprise($entreprise);
        $boutiqueAdmin->setIsActive(true);
        $this->entityManager->persist($boutiqueAdmin);
        
        $regularUser = new User();
        $regularUser->setLogin('user@test.com');
        $regularUser->setNom('Regular');
        $regularUser->setPrenoms('User');
        $regularUser->setPassword('$2y$13$test'); // Mock password hash
        $regularUser->setType($regularType);
        $regularUser->setSurccursale($succursale);
        $regularUser->setBoutique($boutique);
        $regularUser->setEntreprise($entreprise);
        $regularUser->setIsActive(true);
        $this->entityManager->persist($regularUser);
        
        $this->entityManager->flush();
        
        // Create test subscription module
        $moduleAbonnement = new ModuleAbonnement();
        $moduleAbonnement->setCode('TEST_MODULE');
        $moduleAbonnement->setDescription('Test subscription module');
        $moduleAbonnement->setMontant('1000');
        $moduleAbonnement->setDuree('30');
        $moduleAbonnement->setEtat(true);
        $moduleAbonnement->setIsActive(true);
        $this->entityManager->persist($moduleAbonnement);
        
        // Create active subscription for the entreprise
        $abonnement = new Abonnement();
        $abonnement->setModuleAbonnement($moduleAbonnement);
        $abonnement->setEntreprise($entreprise);
        $abonnement->setEtat('actif'); // Must be lowercase 'actif'
        $abonnement->setType('PREMIUM');
        $abonnement->setDateFin(new \DateTime('+1 year')); // Active for 1 year
        $abonnement->setIsActive(true);
        $this->entityManager->persist($abonnement);
        
        $this->entityManager->flush();
        
        // Store test data for use in tests
        $this->testData = [
            'entreprise' => $entreprise,
            'boutique' => $boutique,
            'succursale' => $succursale,
            'users' => [
                'sadm' => $superAdmin,
                'adb' => $boutiqueAdmin,
                'regular' => $regularUser
            ],
            'userTypes' => [
                'sadm' => $sadmType,
                'adb' => $adbType,
                'regular' => $regularType
            ]
        ];
    }
    
    /**
     * Create a test client entity
     */
    protected function createTestClient(array $data = []): Client
    {
        $client = new Client();
        $client->setNom($data['nom'] ?? 'Test');
        $client->setPrenom($data['prenom'] ?? 'Client');
        $client->setNumero($data['numero'] ?? '+225 0123456789');
        
        if (isset($data['boutique'])) {
            $client->setBoutique($data['boutique']);
        } else {
            $client->setBoutique($this->testData['boutique']);
        }
        
        if (isset($data['succursale'])) {
            $client->setSurccursale($data['succursale']);
        } else {
            $client->setSurccursale($this->testData['succursale']);
        }
        
        $client->setEntreprise($this->testData['entreprise']);
        $client->setIsActive(true);
        
        if (isset($data['photo'])) {
            $client->setPhoto($data['photo']);
        }
        
        $this->entityManager->persist($client);
        $this->entityManager->flush();
        
        return $client;
    }
    
    /**
     * Make an authenticated API request
     */
    protected function makeAuthenticatedRequest(
        string $method,
        string $uri,
        array $parameters = [],
        array $files = [],
        array $server = [],
        string $content = null,
        string $userType = 'sadm'
    ): Response {
        // Add authentication headers (mock JWT for now)
        $server['HTTP_AUTHORIZATION'] = 'Bearer ' . $this->generateMockToken($userType);
        $server['CONTENT_TYPE'] = 'application/json';
        
        // Add database environment parameter to ensure test environment is used
        if (strpos($uri, '?') !== false) {
            $uri .= '&env=dev';
        } else {
            $uri .= '?env=dev';
        }
        
        $this->client->request($method, $uri, $parameters, $files, $server, $content);
        
        return $this->client->getResponse();
    }
    
    /**
     * Generate a real JWT token for testing
     */
    protected function generateMockToken(string $userType = 'sadm'): string
    {
        $user = $this->testData['users'][$userType];
        
        $payload = [
            'id' => $user->getId(),
            'login' => $user->getLogin(),
            'roles' => $user->getRoles(),
            'entreprise_id' => $user->getEntreprise()->getId()
        ];
        
        return $this->jwtService->generateToken($payload);
    }
    
    /**
     * Assert that response has correct JSON structure
     */
    protected function assertJsonResponse(Response $response, int $expectedStatusCode = 200): array
    {
        $this->assertEquals($expectedStatusCode, $response->getStatusCode());
        $this->assertTrue($response->headers->contains('Content-Type', 'application/json'));
        
        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content);
        
        return $content;
    }
    
    /**
     * Assert that response contains client data structure
     */
    protected function assertClientDataStructure(array $clientData): void
    {
        $requiredFields = ['id', 'nom', 'prenom', 'numero'];
        
        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $clientData);
        }
        
        // Optional fields should be present but can be null
        $optionalFields = ['photo', 'boutique', 'succursale', 'entreprise', 'createdAt'];
        foreach ($optionalFields as $field) {
            $this->assertArrayHasKey($field, $clientData);
        }
    }
    
    /**
     * Assert pagination structure
     */
    protected function assertPaginationStructure(array $response): void
    {
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('pagination', $response);
        
        $pagination = $response['pagination'];
        $this->assertArrayHasKey('current_page', $pagination);
        $this->assertArrayHasKey('per_page', $pagination);
        $this->assertArrayHasKey('total', $pagination);
        $this->assertArrayHasKey('last_page', $pagination);
    }
    
    /**
     * Assert error response structure
     */
    protected function assertErrorResponse(Response $response, int $expectedStatusCode, string $expectedMessage = null): array
    {
        $content = $this->assertJsonResponse($response, $expectedStatusCode);
        
        $this->assertArrayHasKey('message', $content);
        $this->assertArrayHasKey('statusCode', $content);
        
        if ($expectedMessage) {
            $this->assertEquals($expectedMessage, $content['message']);
        }
        
        return $content;
    }
    
    /**
     * Clean up test files created during tests
     */
    protected function cleanupTestFiles(): void
    {
        $testUploadDir = sys_get_temp_dir() . '/test_uploads';
        if (is_dir($testUploadDir)) {
            $files = glob($testUploadDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($testUploadDir);
        }
    }
    
    /**
     * Get test upload directory
     */
    protected function getTestUploadDir(): string
    {
        $dir = sys_get_temp_dir() . '/test_uploads';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        return $dir;
    }
}