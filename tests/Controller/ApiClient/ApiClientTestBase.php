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

/**
 * Base class for API Client Controller tests
 * Provides common setup, utilities, and helper methods
 */
abstract class ApiClientTestBase extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $entityManager;
    protected array $testData = [];
    
    /**
     * Common setup for all API Client tests
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        
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
        // Create test entreprise
        $entreprise = new Entreprise();
        $entreprise->setNom('Test Entreprise');
        $entreprise->setEmail('test@entreprise.com');
        $entreprise->setTelephone('+225 0123456789');
        $entreprise->setAdresse('123 Test Street');
        $entreprise->setIsActive(true);
        $this->entityManager->persist($entreprise);
        
        // Create test boutique
        $boutique = new Boutique();
        $boutique->setNom('Test Boutique');
        $boutique->setAdresse('456 Boutique Street');
        $boutique->setTelephone('+225 0987654321');
        $boutique->setEntreprise($entreprise);
        $boutique->setIsActive(true);
        $this->entityManager->persist($boutique);
        
        // Create test succursale
        $succursale = new Surccursale();
        $succursale->setNom('Test Succursale');
        $succursale->setAdresse('789 Succursale Street');
        $succursale->setTelephone('+225 0555666777');
        $succursale->setBoutique($boutique);
        $succursale->setEntreprise($entreprise);
        $succursale->setIsActive(true);
        $this->entityManager->persist($succursale);
        
        // Create test user types
        $sadmType = new TypeUser();
        $sadmType->setCode('SADM');
        $sadmType->setLibelle('Super Admin');
        $sadmType->setEntreprise($entreprise);
        $sadmType->setIsActive(true);
        $this->entityManager->persist($sadmType);
        
        $adbType = new TypeUser();
        $adbType->setCode('ADB');
        $adbType->setLibelle('Admin Boutique');
        $adbType->setEntreprise($entreprise);
        $adbType->setIsActive(true);
        $this->entityManager->persist($adbType);
        
        $regularType = new TypeUser();
        $regularType->setCode('REG');
        $regularType->setLibelle('Utilisateur Regular');
        $regularType->setEntreprise($entreprise);
        $regularType->setIsActive(true);
        $this->entityManager->persist($regularType);
        
        // Create test users
        $superAdmin = new User();
        $superAdmin->setEmail('sadm@test.com');
        $superAdmin->setNom('Super');
        $superAdmin->setPrenom('Admin');
        $superAdmin->setType($sadmType);
        $superAdmin->setEntreprise($entreprise);
        $superAdmin->setIsActive(true);
        $this->entityManager->persist($superAdmin);
        
        $boutiqueAdmin = new User();
        $boutiqueAdmin->setEmail('adb@test.com');
        $boutiqueAdmin->setNom('Boutique');
        $boutiqueAdmin->setPrenom('Admin');
        $boutiqueAdmin->setType($adbType);
        $boutiqueAdmin->setBoutique($boutique);
        $boutiqueAdmin->setEntreprise($entreprise);
        $boutiqueAdmin->setIsActive(true);
        $this->entityManager->persist($boutiqueAdmin);
        
        $regularUser = new User();
        $regularUser->setEmail('user@test.com');
        $regularUser->setNom('Regular');
        $regularUser->setPrenom('User');
        $regularUser->setType($regularType);
        $regularUser->setSurccursale($succursale);
        $regularUser->setBoutique($boutique);
        $regularUser->setEntreprise($entreprise);
        $regularUser->setIsActive(true);
        $this->entityManager->persist($regularUser);
        
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
        
        $this->client->request($method, $uri, $parameters, $files, $server, $content);
        
        return $this->client->getResponse();
    }
    
    /**
     * Generate a mock JWT token for testing
     */
    protected function generateMockToken(string $userType = 'sadm'): string
    {
        // For now, return a simple mock token
        // In a real implementation, this would generate a proper JWT
        return base64_encode(json_encode([
            'user_id' => $this->testData['users'][$userType]->getId(),
            'user_type' => $userType,
            'exp' => time() + 3600
        ]));
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