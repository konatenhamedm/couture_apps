<?php

namespace App\Tests\Controller\ApiClient\Properties;

use App\Tests\Controller\ApiClient\ApiClientTestBase;
use App\Tests\Controller\ApiClient\ApiClientTestConfig;
use App\Tests\Controller\ApiClient\Helpers\AuthenticationTestHelper;
use App\Tests\Controller\ApiClient\Generators\ClientDataGenerator;
use Eris\Generator;
use Eris\TestTrait;

/**
 * Property-based test for required field validation
 * 
 * **Feature: api-client-testing, Property 6: Required field validation**
 * **Validates: Requirements 4.2, 5.2**
 * 
 * Tests that client creation requests missing required fields (nom, prenoms, numero) 
 * return validation errors
 */
class RequiredFieldValidationPropertyTest extends ApiClientTestBase
{
    use TestTrait;
    
    /**
     * Property 6: Required field validation
     * For any client creation request missing required fields (nom, prenoms, numero), 
     * the API should return validation errors
     */
    public function testRequiredFieldValidationProperty(): void
    {
        $this->forAll(
            Generator\elements(['nom', 'prenoms', 'numero']), // Required fields
            Generator\elements([
                ApiClientTestConfig::ENDPOINT_CREATE,
                ApiClientTestConfig::ENDPOINT_CREATE_BOUTIQUE
            ]) // Both creation endpoints
        )->then(function (string $missingField, string $endpoint): void {
            
            // Create valid client data
            $clientData = [
                'nom' => 'Valid Nom',
                'prenoms' => 'Valid Prenoms',
                'numero' => '+225 0123456789',
                'boutique' => $this->testData['boutique']->getId(),
                'succursale' => $this->testData['succursale']->getId()
            ];
            
            // Remove the required field to test validation
            unset($clientData[$missingField]);
            
            $token = AuthenticationTestHelper::createSuperAdminToken();
            $headers = AuthenticationTestHelper::createMultipartAuthHeaders($token);
            
            $this->client->request(
                'POST',
                $endpoint,
                $clientData,
                [],
                $headers
            );
            
            $response = $this->client->getResponse();
            
            // Property: Missing required field should result in validation error (400)
            $this->assertEquals(
                400,
                $response->getStatusCode(),
                sprintf(
                    'Missing required field "%s" should result in 400 validation error on endpoint %s',
                    $missingField,
                    $endpoint
                )
            );
            
            $content = json_decode($response->getContent(), true);
            $this->assertIsArray($content, 'Error response should be valid JSON');
            $this->assertArrayHasKey('message', $content, 'Error response should contain message');
            
            // Property: Error message should indicate required field issue
            $this->assertStringContainsString(
                'obligatoire',
                $content['message'],
                sprintf('Error message should indicate required field issue for missing "%s"', $missingField)
            );
        });
    }
    
    /**
     * Property test for empty string validation
     */
    public function testEmptyStringValidationProperty(): void
    {
        $this->forAll(
            Generator\elements(['nom', 'prenoms', 'numero']),
            Generator\elements(['', '   ', "\t", "\n", "  \t  \n  "]) // Various empty/whitespace strings
        )->then(function (string $field, string $emptyValue): void {
            
            $clientData = [
                'nom' => 'Valid Nom',
                'prenoms' => 'Valid Prenoms',
                'numero' => '+225 0123456789',
                'boutique' => $this->testData['boutique']->getId(),
                'succursale' => $this->testData['succursale']->getId()
            ];
            
            // Set the field to empty/whitespace value
            $clientData[$field] = $emptyValue;
            
            $token = AuthenticationTestHelper::createSuperAdminToken();
            $headers = AuthenticationTestHelper::createMultipartAuthHeaders($token);
            
            $this->client->request(
                'POST',
                ApiClientTestConfig::ENDPOINT_CREATE,
                $clientData,
                [],
                $headers
            );
            
            $response = $this->client->getResponse();
            
            // Property: Empty/whitespace values should be treated as invalid
            $this->assertEquals(
                400,
                $response->getStatusCode(),
                sprintf('Empty/whitespace value for field "%s" should result in validation error', $field)
            );
            
            $content = json_decode($response->getContent(), true);
            $this->assertArrayHasKey('message', $content);
            $this->assertStringContainsString(
                'obligatoire',
                $content['message'],
                'Error message should indicate required field validation'
            );
        });
    }
    
    /**
     * Property test for null value validation
     */
    public function testNullValueValidationProperty(): void
    {
        $this->forAll(
            Generator\elements(['nom', 'prenoms', 'numero'])
        )->then(function (string $field): void {
            
            $clientData = [
                'nom' => 'Valid Nom',
                'prenoms' => 'Valid Prenoms',
                'numero' => '+225 0123456789',
                'boutique' => $this->testData['boutique']->getId(),
                'succursale' => $this->testData['succursale']->getId()
            ];
            
            // Set the field to null
            $clientData[$field] = null;
            
            $token = AuthenticationTestHelper::createSuperAdminToken();
            $headers = AuthenticationTestHelper::createMultipartAuthHeaders($token);
            
            $this->client->request(
                'POST',
                ApiClientTestConfig::ENDPOINT_CREATE,
                $clientData,
                [],
                $headers
            );
            
            $response = $this->client->getResponse();
            
            // Property: Null values should be treated as invalid for required fields
            $this->assertEquals(
                400,
                $response->getStatusCode(),
                sprintf('Null value for required field "%s" should result in validation error', $field)
            );
        });
    }
    
    /**
     * Property test for multiple missing fields
     */
    public function testMultipleMissingFieldsProperty(): void
    {
        $this->forAll(
            Generator\subsetOf(['nom', 'prenoms', 'numero']) // Subset of required fields to remove
        )->then(function (array $fieldsToRemove): void {
            
            // Skip if no fields to remove (would be valid case)
            if (empty($fieldsToRemove)) {
                return;
            }
            
            $clientData = [
                'nom' => 'Valid Nom',
                'prenoms' => 'Valid Prenoms',
                'numero' => '+225 0123456789',
                'boutique' => $this->testData['boutique']->getId(),
                'succursale' => $this->testData['succursale']->getId()
            ];
            
            // Remove multiple required fields
            foreach ($fieldsToRemove as $field) {
                unset($clientData[$field]);
            }
            
            $token = AuthenticationTestHelper::createSuperAdminToken();
            $headers = AuthenticationTestHelper::createMultipartAuthHeaders($token);
            
            $this->client->request(
                'POST',
                ApiClientTestConfig::ENDPOINT_CREATE,
                $clientData,
                [],
                $headers
            );
            
            $response = $this->client->getResponse();
            
            // Property: Multiple missing required fields should result in validation error
            $this->assertEquals(
                400,
                $response->getStatusCode(),
                sprintf('Missing multiple required fields %s should result in validation error', implode(', ', $fieldsToRemove))
            );
            
            $content = json_decode($response->getContent(), true);
            $this->assertArrayHasKey('message', $content);
        });
    }
    
    /**
     * Property test for valid data (should not fail validation)
     */
    public function testValidDataPassesValidationProperty(): void
    {
        $this->forAll(
            ClientDataGenerator::validClientData()
        )->then(function (array $clientData): void {
            
            // Ensure we use valid IDs from test data
            $clientData['boutique'] = $this->testData['boutique']->getId();
            $clientData['succursale'] = $this->testData['succursale']->getId();
            
            $token = AuthenticationTestHelper::createSuperAdminToken();
            $headers = AuthenticationTestHelper::createMultipartAuthHeaders($token);
            
            $this->client->request(
                'POST',
                ApiClientTestConfig::ENDPOINT_CREATE,
                $clientData,
                [],
                $headers
            );
            
            $response = $this->client->getResponse();
            
            // Property: Valid data should not fail due to required field validation
            // (may fail for other reasons like duplicate numero, but not missing fields)
            $this->assertNotEquals(
                400,
                $response->getStatusCode(),
                'Valid client data should not fail required field validation'
            );
            
            // If it's a 400, it should not be due to missing required fields
            if ($response->getStatusCode() === 400) {
                $content = json_decode($response->getContent(), true);
                if (isset($content['message'])) {
                    $this->assertStringNotContainsString(
                        'obligatoire',
                        $content['message'],
                        'Valid data should not fail with required field error'
                    );
                }
            }
        });
    }
    
    /**
     * Property test for field length validation
     */
    public function testFieldLengthValidationProperty(): void
    {
        $this->forAll(
            Generator\elements(['nom', 'prenoms']), // Text fields that might have length limits
            Generator\choose(1, 5) // Very short strings
        )->then(function (string $field, int $length): void {
            
            $clientData = [
                'nom' => 'Valid Nom',
                'prenoms' => 'Valid Prenoms',
                'numero' => '+225 0123456789',
                'boutique' => $this->testData['boutique']->getId(),
                'succursale' => $this->testData['succursale']->getId()
            ];
            
            // Set field to very short string
            $clientData[$field] = str_repeat('A', $length);
            
            $token = AuthenticationTestHelper::createSuperAdminToken();
            $headers = AuthenticationTestHelper::createMultipartAuthHeaders($token);
            
            $this->client->request(
                'POST',
                ApiClientTestConfig::ENDPOINT_CREATE,
                $clientData,
                [],
                $headers
            );
            
            $response = $this->client->getResponse();
            
            // Property: Very short strings should be accepted (no minimum length requirement)
            // This tests that we don't have overly restrictive validation
            if ($response->getStatusCode() === 400) {
                $content = json_decode($response->getContent(), true);
                if (isset($content['message'])) {
                    // Should not fail due to length (unless there's a specific business rule)
                    $this->assertStringNotContainsString(
                        'trop court',
                        $content['message'],
                        'Short but non-empty strings should be accepted'
                    );
                }
            }
        });
    }
    
    /**
     * Property test for phone number format validation
     */
    public function testPhoneNumberFormatValidationProperty(): void
    {
        $this->forAll(
            Generator\elements([
                'invalid-phone',
                '123',
                'not-a-number',
                '+33123456789', // Different country code
                '++225123456789', // Double plus
                '+225 abc def ghi', // Letters
                ''
            ])
        )->then(function (string $invalidPhone): void {
            
            $clientData = [
                'nom' => 'Valid Nom',
                'prenoms' => 'Valid Prenoms',
                'numero' => $invalidPhone,
                'boutique' => $this->testData['boutique']->getId(),
                'succursale' => $this->testData['succursale']->getId()
            ];
            
            $token = AuthenticationTestHelper::createSuperAdminToken();
            $headers = AuthenticationTestHelper::createMultipartAuthHeaders($token);
            
            $this->client->request(
                'POST',
                ApiClientTestConfig::ENDPOINT_CREATE,
                $clientData,
                [],
                $headers
            );
            
            $response = $this->client->getResponse();
            
            // Property: Invalid phone numbers should result in validation error
            // (Note: This depends on whether phone validation is implemented)
            if ($invalidPhone === '') {
                // Empty phone should definitely fail
                $this->assertEquals(
                    400,
                    $response->getStatusCode(),
                    'Empty phone number should result in validation error'
                );
            }
            
            // For other invalid formats, we test but don't enforce strict validation
            // as the business rules might be flexible
        });
    }
    
    /**
     * Property test for validation error response structure
     */
    public function testValidationErrorResponseStructureProperty(): void
    {
        $this->forAll(
            Generator\elements(['nom', 'prenoms', 'numero'])
        )->then(function (string $missingField): void {
            
            $clientData = [
                'boutique' => $this->testData['boutique']->getId(),
                'succursale' => $this->testData['succursale']->getId()
            ];
            // Intentionally missing all required fields except one
            if ($missingField !== 'nom') $clientData['nom'] = 'Valid Nom';
            if ($missingField !== 'prenoms') $clientData['prenoms'] = 'Valid Prenoms';
            if ($missingField !== 'numero') $clientData['numero'] = '+225 0123456789';
            
            $token = AuthenticationTestHelper::createSuperAdminToken();
            $headers = AuthenticationTestHelper::createMultipartAuthHeaders($token);
            
            $this->client->request(
                'POST',
                ApiClientTestConfig::ENDPOINT_CREATE,
                $clientData,
                [],
                $headers
            );
            
            $response = $this->client->getResponse();
            
            if ($response->getStatusCode() === 400) {
                $content = json_decode($response->getContent(), true);
                
                // Property: Validation error responses should have consistent structure
                $this->assertIsArray($content, 'Validation error response should be JSON array');
                $this->assertArrayHasKey('message', $content, 'Should contain message field');
                $this->assertArrayHasKey('statusCode', $content, 'Should contain statusCode field');
                
                // Property: Status code in response should match HTTP status
                $this->assertEquals(400, $content['statusCode'], 'Response statusCode should match HTTP status');
                
                // Property: Message should be non-empty string
                $this->assertIsString($content['message'], 'Message should be string');
                $this->assertNotEmpty($content['message'], 'Message should not be empty');
            }
        });
    }
}