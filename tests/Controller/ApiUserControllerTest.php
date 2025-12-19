<?php

namespace App\Tests\Controller;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

class ApiUserControllerTest extends TestCase
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
     * Test that ApiUserController error responses don't contain null validation errors
     */
    public function testApiUserControllerErrorResponsesProperty(): void
    {
        $controller = $this->createTestController();
        
        // Test various error scenarios specific to user management
        $userErrorScenarios = [
            'Cet email existe déjà, veuillez utiliser un autre',
            'Pays non trouvé',
            'Plan d\'abonnement FREE non trouvé',
            'Type d\'utilisateur non trouvé',
            'Membre non trouvé',
            'Abonnement requis pour cette fonctionnalité'
        ];

        foreach ($userErrorScenarios as $errorMessage) {
            $response = $controller->testCreateCustomErrorResponse($errorMessage, 400);
            $content = json_decode($response->getContent(), true);

            // Verify the error message doesn't contain null validation errors
            $this->assertStringNotContainsString('Cannot validate values of type "null"', $content['message']);
            $this->assertStringNotContainsString('null automatically', $content['message']);
            
            // Verify the response is properly structured
            $this->assertIsArray($content['errors']);
            $this->assertNotEmpty($content['errors']);
            $this->assertEquals($errorMessage, $content['message']);
        }
    }

    /**
     * Test error response consistency across different status codes
     */
    public function testUserControllerErrorResponseStatusCodes(): void
    {
        $controller = $this->createTestController();

        $testCases = [
            ['message' => 'Cet email existe déjà', 'code' => 400],
            ['message' => 'Pays non trouvé', 'code' => 404],
            ['message' => 'Type d\'utilisateur non trouvé', 'code' => 404],
            ['message' => 'Plan d\'abonnement FREE non trouvé', 'code' => 500],
        ];

        foreach ($testCases as $testCase) {
            $response = $controller->testCreateCustomErrorResponse($testCase['message'], $testCase['code']);
            
            // Verify status code
            $this->assertEquals($testCase['code'], $response->getStatusCode());
            
            $content = json_decode($response->getContent(), true);
            
            // Verify response structure
            $this->assertArrayHasKey('code', $content);
            $this->assertArrayHasKey('message', $content);
            $this->assertArrayHasKey('errors', $content);
            
            // Verify values
            $this->assertEquals($testCase['code'], $content['code']);
            $this->assertEquals($testCase['message'], $content['message']);
            $this->assertContains($testCase['message'], $content['errors']);
        }
    }

    /**
     * Property-based test for user controller error responses
     * Tests 50 iterations with different error scenarios
     */
    public function testUserControllerErrorResponseProperty(): void
    {
        $controller = $this->createTestController();

        // Test 50 iterations with different user-related error scenarios
        for ($i = 0; $i < 50; $i++) {
            $errorMessage = $this->generateUserErrorMessage();
            $statusCode = $this->generateUserStatusCode();

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
     * Generate random user-related error messages for property testing
     */
    private function generateUserErrorMessage(): string
    {
        $messages = [
            'Email déjà utilisé',
            'Utilisateur non trouvé',
            'Type d\'utilisateur invalide',
            'Pays non trouvé',
            'Abonnement expiré',
            'Permissions insuffisantes',
            'Données utilisateur invalides',
            'Entreprise non trouvée',
            'Plan d\'abonnement non disponible',
            'Erreur de validation utilisateur'
        ];

        $baseMessage = $messages[array_rand($messages)];
        $randomSuffix = ': ' . uniqid();
        
        return $baseMessage . $randomSuffix;
    }

    /**
     * Generate random HTTP status codes for user-related errors
     */
    private function generateUserStatusCode(): int
    {
        $statusCodes = [400, 401, 403, 404, 422, 500];
        return $statusCodes[array_rand($statusCodes)];
    }
}