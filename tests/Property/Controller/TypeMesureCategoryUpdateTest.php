<?php

namespace App\Tests\Property\Controller;

use App\Entity\CategorieMesure;
use App\Entity\CategorieTypeMesure;
use App\Entity\Entreprise;
use App\Entity\TypeMesure;
use App\Entity\User;
use App\Repository\CategorieMesureRepository;
use App\Repository\CategorieTypeMesureRepository;
use App\Repository\TypeMesureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Eris\Generators;
use Eris\TestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class TypeMesureCategoryUpdateTest extends WebTestCase
{
    use TestTrait;

    private $client;
    private $entityManager;
    private $user;
    private $entreprise;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get(EntityManagerInterface::class);
        
        // Create test user and entreprise
        $this->entreprise = new Entreprise();
        $this->entreprise->setLibelle('Test Entreprise');
        $this->entreprise->setEmail('test@example.com');
        $this->entreprise->setIsActive(true);
        $this->entityManager->persist($this->entreprise);
        
        $this->user = new User();
        $this->user->setLogin('testuser');
        $this->user->setPassword('password');
        $this->user->setEntreprise($this->entreprise);
        $this->user->setIsActive(true);
        $this->entityManager->persist($this->user);
        
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        // Clean up test data
        if ($this->entityManager) {
            $this->entityManager->clear();
        }
        parent::tearDown();
    }

    /**
     * Feature: type-mesure-category-update, Property 1: Complete Payload Processing
     * For any valid update request with libelle and categories array containing idCategorie fields,
     * the controller should successfully parse the payload and update both the TypeMesure libelle and replace all associated categories.
     * Validates: Requirements 1.1, 1.2, 1.3
     */
    public function testCompletePayloadProcessing(): void
    {
        $this->forAll(
            Generators::string()->suchThat(fn($s) => !empty(trim($s)) && strlen($s) <= 255),
            Generators::seq(Generators::pos())->suchThat(fn($arr) => count($arr) <= 5)
        )->then(function (string $libelle, array $categoryIds) {
            // Create test TypeMesure
            $typeMesure = new TypeMesure();
            $typeMesure->setLibelle('Original Libelle');
            $typeMesure->setEntreprise($this->entreprise);
            $typeMesure->setCreatedBy($this->user);
            $typeMesure->setUpdatedBy($this->user);
            $this->entityManager->persist($typeMesure);

            // Create test categories
            $categories = [];
            foreach ($categoryIds as $i => $categoryId) {
                $category = new CategorieMesure();
                $category->setLibelle("Category $i");
                $category->setEntreprise($this->entreprise);
                $category->setCreatedBy($this->user);
                $category->setUpdatedBy($this->user);
                $this->entityManager->persist($category);
                $categories[] = $category;
            }

            $this->entityManager->flush();

            // Prepare payload
            $payload = [
                'libelle' => $libelle,
                'categories' => array_map(fn($cat) => ['idCategorie' => $cat->getId()], $categories)
            ];

            // Mock authentication
            $this->client->loginUser($this->user);

            // Make request
            $this->client->request(
                'PUT',
                '/api/typeMesure/update/' . $typeMesure->getId(),
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode($payload)
            );

            $response = $this->client->getResponse();
            
            // Verify successful response
            $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
            
            $responseData = json_decode($response->getContent(), true);
            
            // Verify response structure
            $this->assertArrayHasKey('code', $responseData);
            $this->assertArrayHasKey('message', $responseData);
            $this->assertArrayHasKey('data', $responseData);
            $this->assertEquals(200, $responseData['code']);
            
            // Verify libelle was updated
            $this->assertEquals($libelle, $responseData['data']['libelle']);
            
            // Verify categories were updated
            $this->assertArrayHasKey('categories', $responseData['data']);
            $this->assertCount(count($categories), $responseData['data']['categories']);
            
            // Verify each category is present
            $responseCategoryIds = array_column($responseData['data']['categories'], 'idCategorie');
            $expectedCategoryIds = array_map(fn($cat) => $cat->getId(), $categories);
            sort($responseCategoryIds);
            sort($expectedCategoryIds);
            $this->assertEquals($expectedCategoryIds, $responseCategoryIds);

            // Clean up for next iteration
            $this->entityManager->remove($typeMesure);
            foreach ($categories as $category) {
                $this->entityManager->remove($category);
            }
            $this->entityManager->flush();
        });
    }

    /**
     * Feature: type-mesure-category-update, Property 3: Invalid Category Error Handling
     * For any payload containing non-existent category IDs, the system should return a 400 error
     * with specific details and not modify any existing associations.
     * Validates: Requirements 1.4, 2.3, 3.2
     */
    public function testInvalidCategoryErrorHandling(): void
    {
        $this->forAll(
            Generators::string()->suchThat(fn($s) => !empty(trim($s)) && strlen($s) <= 255),
            Generators::pos()->suchThat(fn($id) => $id > 999999) // Use very high IDs that won't exist
        )->then(function (string $libelle, int $invalidCategoryId) {
            // Create test TypeMesure
            $typeMesure = new TypeMesure();
            $typeMesure->setLibelle('Original Libelle');
            $typeMesure->setEntreprise($this->entreprise);
            $typeMesure->setCreatedBy($this->user);
            $typeMesure->setUpdatedBy($this->user);
            $this->entityManager->persist($typeMesure);
            $this->entityManager->flush();

            $originalLibelle = $typeMesure->getLibelle();

            // Prepare payload with invalid category ID
            $payload = [
                'libelle' => $libelle,
                'categories' => [['idCategorie' => $invalidCategoryId]]
            ];

            // Mock authentication
            $this->client->loginUser($this->user);

            // Make request
            $this->client->request(
                'PUT',
                '/api/typeMesure/update/' . $typeMesure->getId(),
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode($payload)
            );

            $response = $this->client->getResponse();
            
            // Verify error response
            $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
            
            $responseData = json_decode($response->getContent(), true);
            
            // Verify error response structure
            $this->assertArrayHasKey('code', $responseData);
            $this->assertArrayHasKey('message', $responseData);
            $this->assertArrayHasKey('errors', $responseData);
            $this->assertEquals(400, $responseData['code']);
            $this->assertEquals('Category not found', $responseData['message']);
            $this->assertContains("Category {$invalidCategoryId} not found", $responseData['errors']);

            // Verify no changes were made to the TypeMesure
            $this->entityManager->refresh($typeMesure);
            $this->assertEquals($originalLibelle, $typeMesure->getLibelle());

            // Clean up
            $this->entityManager->remove($typeMesure);
            $this->entityManager->flush();
        });
    }

    /**
     * Feature: type-mesure-category-update, Property 4: Empty Categories Handling
     * For any update with an empty categories array, the system should remove all existing category associations from the TypeMesure.
     * Validates: Requirements 2.4
     */
    public function testEmptyCategoriesHandling(): void
    {
        $this->forAll(
            Generators::string()->suchThat(fn($s) => !empty(trim($s)) && strlen($s) <= 255)
        )->then(function (string $libelle) {
            // Create test TypeMesure with existing categories
            $typeMesure = new TypeMesure();
            $typeMesure->setLibelle('Original Libelle');
            $typeMesure->setEntreprise($this->entreprise);
            $typeMesure->setCreatedBy($this->user);
            $typeMesure->setUpdatedBy($this->user);
            $this->entityManager->persist($typeMesure);

            // Create and associate some categories
            $category1 = new CategorieMesure();
            $category1->setLibelle('Category 1');
            $category1->setEntreprise($this->entreprise);
            $category1->setCreatedBy($this->user);
            $category1->setUpdatedBy($this->user);
            $this->entityManager->persist($category1);

            $association1 = new CategorieTypeMesure();
            $association1->setTypeMesure($typeMesure);
            $association1->setCategorieMesure($category1);
            $association1->setEntreprise($this->entreprise);
            $association1->setCreatedBy($this->user);
            $association1->setUpdatedBy($this->user);
            $this->entityManager->persist($association1);

            $this->entityManager->flush();

            // Prepare payload with empty categories
            $payload = [
                'libelle' => $libelle,
                'categories' => []
            ];

            // Mock authentication
            $this->client->loginUser($this->user);

            // Make request
            $this->client->request(
                'PUT',
                '/api/typeMesure/update/' . $typeMesure->getId(),
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode($payload)
            );

            $response = $this->client->getResponse();
            
            // Verify successful response
            $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
            
            $responseData = json_decode($response->getContent(), true);
            
            // Verify libelle was updated
            $this->assertEquals($libelle, $responseData['data']['libelle']);
            
            // Verify all categories were removed
            $this->assertArrayHasKey('categories', $responseData['data']);
            $this->assertEmpty($responseData['data']['categories']);

            // Clean up
            $this->entityManager->remove($typeMesure);
            $this->entityManager->remove($category1);
            $this->entityManager->flush();
        });
    }

    /**
     * Feature: type-mesure-category-update, Property 6: Malformed JSON Error Handling
     * For any malformed JSON payload, the API should return a 400 error with parsing details.
     * Validates: Requirements 3.4
     */
    public function testMalformedJsonErrorHandling(): void
    {
        $this->forAll(
            Generators::oneOf(
                Generators::constant('{"libelle": "test", "categories": [}'), // Missing closing bracket
                Generators::constant('{"libelle": "test" "categories": []}'), // Missing comma
                Generators::constant('{"libelle": test, "categories": []}'), // Unquoted string
                Generators::constant('{libelle: "test", "categories": []}'), // Unquoted key
                Generators::constant('{"libelle": "test", "categories": [{"idCategorie": }]}') // Missing value
            )
        )->then(function (string $malformedJson) {
            // Create test TypeMesure
            $typeMesure = new TypeMesure();
            $typeMesure->setLibelle('Original Libelle');
            $typeMesure->setEntreprise($this->entreprise);
            $typeMesure->setCreatedBy($this->user);
            $typeMesure->setUpdatedBy($this->user);
            $this->entityManager->persist($typeMesure);
            $this->entityManager->flush();

            // Mock authentication
            $this->client->loginUser($this->user);

            // Make request with malformed JSON
            $this->client->request(
                'PUT',
                '/api/typeMesure/update/' . $typeMesure->getId(),
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                $malformedJson
            );

            $response = $this->client->getResponse();
            
            // Verify error response
            $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
            
            $responseData = json_decode($response->getContent(), true);
            
            // Verify error response structure
            $this->assertArrayHasKey('code', $responseData);
            $this->assertArrayHasKey('message', $responseData);
            $this->assertArrayHasKey('errors', $responseData);
            $this->assertEquals(400, $responseData['code']);
            $this->assertEquals('Malformed JSON', $responseData['message']);

            // Clean up
            $this->entityManager->remove($typeMesure);
            $this->entityManager->flush();
        });
    }
}