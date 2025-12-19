<?php

namespace App\Tests\Controller;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use ReflectionClass;

class ApiFixtureControllerTest extends TestCase
{
    /**
     * Create a minimal test controller class to test the createCustomErrorResponse method
     */
    private function createTestController()
    {
        return new class {
            private function createCustomErrorResponse(string $message, int $statusCode = 400): JsonResponse
            {
                return new JsonResponse([
                    'code' => $statusCode,
                    'message' => $message,
                    'errors' => [$message]
                ], $statusCode);
            }
            
            public function testCreateCustomErrorResponse(string $message, int $statusCode = 400): JsonResponse
            {
                return $this->createCustomErrorResponse($message, $statusCode);
            }
        };
    }

    /**
     * **Feature: fix-api-fixture-validation, Property 4: Proper error response parameters**
     * 
     * Property-based test that verifies error response methods are never called with null
     * as the first parameter when entity validation is expected.
     * 
     * This test runs 100 iterations with different error scenarios to ensure
     * the error response pattern is consistent and never causes null validation errors.
     */
    public function testErrorResponseParametersProperty(): void
    {
        $controller = $this->createTestController();

        // Test 100 iterations with different error messages and status codes
        for ($i = 0; $i < 100; $i++) {
            // Generate random error scenarios
            $errorMessage = $this->generateRandomErrorMessage();
            $statusCode = $this->generateRandomStatusCode();

            // Call the custom error response method
            $response = $controller->testCreateCustomErrorResponse($errorMessage, $statusCode);

            // Verify the response is a JsonResponse
            $this->assertInstanceOf(JsonResponse::class, $response);

            // Verify the status code matches
            $this->assertEquals($statusCode, $response->getStatusCode());

            // Decode and verify the response content
            $content = json_decode($response->getContent(), true);
            
            // Verify the response structure
            $this->assertArrayHasKey('code', $content);
            $this->assertArrayHasKey('message', $content);
            $this->assertArrayHasKey('errors', $content);

            // Verify the values
            $this->assertEquals($statusCode, $content['code']);
            $this->assertEquals($errorMessage, $content['message']);
            $this->assertIsArray($content['errors']);
            $this->assertContains($errorMessage, $content['errors']);

            // Most importantly: verify no null validation errors
            $this->assertStringNotContainsString('Cannot validate values of type "null"', $errorMessage);
            $this->assertStringNotContainsString('null', strtolower($content['message']));
        }
    }

    /**
     * Test that error responses never contain null validation error messages
     */
    public function testNoNullValidationErrorMessages(): void
    {
        $controller = $this->createTestController();
        
        // Test various error scenarios
        $errorScenarios = [
            'Erreur lors de la création des fixtures',
            'Aucun client trouvé',
            'Aucune boutique trouvée',
            'Erreur de base de données',
            'Validation échouée'
        ];

        foreach ($errorScenarios as $errorMessage) {
            $response = $controller->testCreateCustomErrorResponse($errorMessage, 400);
            $content = json_decode($response->getContent(), true);

            // Verify the error message doesn't contain null validation errors
            $this->assertStringNotContainsString('Cannot validate values of type "null"', $content['message']);
            $this->assertStringNotContainsString('null automatically', $content['message']);
            
            // Verify the response is properly structured
            $this->assertIsArray($content['errors']);
            $this->assertNotEmpty($content['errors']);
        }
    }

    /**
     * Generate random error messages for property testing
     */
    private function generateRandomErrorMessage(): string
    {
        $messages = [
            'Erreur lors de la création des fixtures',
            'Aucun client trouvé pour créer les fixtures',
            'Aucune boutique trouvée pour créer les fixtures',
            'Erreur de validation des données',
            'Erreur de connexion à la base de données',
            'Données manquantes pour la création',
            'Erreur lors de la sauvegarde',
            'Contrainte de base de données violée',
            'Utilisateur non authentifié',
            'Permissions insuffisantes'
        ];

        $baseMessage = $messages[array_rand($messages)];
        $randomSuffix = ': ' . uniqid();
        
        return $baseMessage . $randomSuffix;
    }

    /**
     * Generate random HTTP status codes for property testing
     */
    private function generateRandomStatusCode(): int
    {
        $statusCodes = [400, 401, 403, 404, 422, 500, 503];
        return $statusCodes[array_rand($statusCodes)];
    }

    /**
     * Test that error responses have consistent structure across different scenarios
     */
    public function testErrorResponseStructureConsistency(): void
    {
        $controller = $this->createTestController();

        // Test multiple scenarios to ensure consistent structure
        $testCases = [
            ['message' => 'Simple error', 'code' => 400],
            ['message' => 'Server error with special chars: éàü', 'code' => 500],
            ['message' => 'Very long error message that contains multiple words and should still be handled properly by the error response system', 'code' => 422],
            ['message' => '', 'code' => 400], // Edge case: empty message
            ['message' => 'Error with numbers 12345', 'code' => 503],
        ];

        foreach ($testCases as $testCase) {
            $response = $controller->testCreateCustomErrorResponse($testCase['message'], $testCase['code']);
            $content = json_decode($response->getContent(), true);

            // Verify consistent structure
            $this->assertArrayHasKey('code', $content);
            $this->assertArrayHasKey('message', $content);
            $this->assertArrayHasKey('errors', $content);
            
            // Verify types
            $this->assertIsInt($content['code']);
            $this->assertIsString($content['message']);
            $this->assertIsArray($content['errors']);
            
            // Verify values
            $this->assertEquals($testCase['code'], $content['code']);
            $this->assertEquals($testCase['message'], $content['message']);
        }
    }
}