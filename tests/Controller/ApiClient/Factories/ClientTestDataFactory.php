<?php

namespace App\Tests\Controller\ApiClient\Factories;

use App\Entity\Client;
use App\Entity\Boutique;
use App\Entity\Surccursale;
use App\Entity\Entreprise;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Tests\Controller\ApiClient\Helpers\FileUploadTestHelper;

/**
 * Factory class for generating test data for Client API tests
 */
class ClientTestDataFactory
{
    /**
     * Create valid client data for API requests
     */
    public static function createValidClientData(array $overrides = []): array
    {
        $defaultData = [
            'nom' => 'Kouassi',
            'prenoms' => 'Yao Jean',
            'numero' => '+225 0123456789',
            'boutique' => 1,
            'succursale' => 1
        ];
        
        return array_merge($defaultData, $overrides);
    }
    
    /**
     * Create invalid client data (missing required fields)
     */
    public static function createInvalidClientData(string $missingField = 'nom'): array
    {
        $validData = self::createValidClientData();
        unset($validData[$missingField]);
        
        return $validData;
    }
    
    /**
     * Create client data with photo
     */
    public static function createClientWithPhoto(array $overrides = []): array
    {
        $data = self::createValidClientData($overrides);
        $data['photo'] = FileUploadTestHelper::createValidImageFile();
        
        return $data;
    }
    
    /**
     * Create multiple clients data
     */
    public static function createMultipleClients(int $count): array
    {
        $clients = [];
        
        for ($i = 1; $i <= $count; $i++) {
            $clients[] = self::createValidClientData([
                'nom' => "Client{$i}",
                'prenoms' => "Prenom{$i}",
                'numero' => "+225 012345678{$i}"
            ]);
        }
        
        return $clients;
    }
    
    /**
     * Create client data specifically for boutique
     */
    public static function createClientForBoutique(int $boutiqueId = 1, array $overrides = []): array
    {
        return self::createValidClientData(array_merge([
            'boutique' => $boutiqueId,
            'succursale' => null
        ], $overrides));
    }
    
    /**
     * Create client data specifically for succursale
     */
    public static function createClientForSuccursale(int $succursaleId = 1, int $boutiqueId = 1, array $overrides = []): array
    {
        return self::createValidClientData(array_merge([
            'boutique' => $boutiqueId,
            'succursale' => $succursaleId
        ], $overrides));
    }
    
    /**
     * Create client data with various validation errors
     */
    public static function createClientDataWithValidationErrors(): array
    {
        return [
            'empty_nom' => [
                'nom' => '',
                'prenoms' => 'Valid Prenom',
                'numero' => '+225 0123456789'
            ],
            'empty_prenoms' => [
                'nom' => 'Valid Nom',
                'prenoms' => '',
                'numero' => '+225 0123456789'
            ],
            'empty_numero' => [
                'nom' => 'Valid Nom',
                'prenoms' => 'Valid Prenom',
                'numero' => ''
            ],
            'invalid_numero_format' => [
                'nom' => 'Valid Nom',
                'prenoms' => 'Valid Prenom',
                'numero' => 'invalid-phone'
            ],
            'whitespace_only_nom' => [
                'nom' => '   ',
                'prenoms' => 'Valid Prenom',
                'numero' => '+225 0123456789'
            ],
            'null_values' => [
                'nom' => null,
                'prenoms' => null,
                'numero' => null
            ]
        ];
    }
    
    /**
     * Create update data for existing client
     */
    public static function createUpdateData(array $changes = []): array
    {
        $defaultChanges = [
            'nom' => 'Updated Nom',
            'prenoms' => 'Updated Prenoms',
            'numero' => '+225 0987654321'
        ];
        
        return array_merge($defaultChanges, $changes);
    }
    
    /**
     * Create partial update data (only some fields)
     */
    public static function createPartialUpdateData(array $fieldsToUpdate = ['nom']): array
    {
        $allUpdates = [
            'nom' => 'Partial Update Nom',
            'prenoms' => 'Partial Update Prenoms',
            'numero' => '+225 0555666777',
            'boutique' => 2,
            'succursale' => 2
        ];
        
        $partialUpdate = [];
        foreach ($fieldsToUpdate as $field) {
            if (isset($allUpdates[$field])) {
                $partialUpdate[$field] = $allUpdates[$field];
            }
        }
        
        return $partialUpdate;
    }
    
    /**
     * Create bulk delete data (array of IDs)
     */
    public static function createBulkDeleteData(array $clientIds): array
    {
        return ['ids' => $clientIds];
    }
    
    /**
     * Create random client data for property-based testing
     */
    public static function createRandomClientData(): array
    {
        $noms = ['Kouassi', 'Kone', 'Traore', 'Ouattara', 'Bamba', 'Yao', 'Assi', 'Diabate'];
        $prenoms = ['Jean', 'Marie', 'Paul', 'Pierre', 'Fatou', 'Aminata', 'Moussa', 'Ibrahim'];
        
        return [
            'nom' => $noms[array_rand($noms)],
            'prenoms' => $prenoms[array_rand($prenoms)] . ' ' . $prenoms[array_rand($prenoms)],
            'numero' => '+225 ' . sprintf('%010d', mt_rand(1000000000, 9999999999)),
            'boutique' => mt_rand(1, 5),
            'succursale' => mt_rand(1, 10)
        ];
    }
    
    /**
     * Create client data with edge case values
     */
    public static function createEdgeCaseClientData(): array
    {
        return [
            'very_long_nom' => [
                'nom' => str_repeat('A', 255), // Very long name
                'prenoms' => 'Normal Prenom',
                'numero' => '+225 0123456789'
            ],
            'special_characters' => [
                'nom' => "O'Connor-Smith",
                'prenoms' => 'Jean-François',
                'numero' => '+225 0123456789'
            ],
            'unicode_characters' => [
                'nom' => 'Kôné',
                'prenoms' => 'Fatoumata',
                'numero' => '+225 0123456789'
            ],
            'numbers_in_name' => [
                'nom' => 'Client123',
                'prenoms' => 'Test456',
                'numero' => '+225 0123456789'
            ],
            'minimum_length' => [
                'nom' => 'A',
                'prenoms' => 'B',
                'numero' => '+1'
            ]
        ];
    }
    
    /**
     * Create client data with different phone number formats
     */
    public static function createClientDataWithPhoneVariations(): array
    {
        return [
            'international_format' => [
                'nom' => 'Test',
                'prenoms' => 'Client',
                'numero' => '+225 01 23 45 67 89'
            ],
            'local_format' => [
                'nom' => 'Test',
                'prenoms' => 'Client',
                'numero' => '01 23 45 67 89'
            ],
            'no_spaces' => [
                'nom' => 'Test',
                'prenoms' => 'Client',
                'numero' => '+2250123456789'
            ],
            'with_dashes' => [
                'nom' => 'Test',
                'prenoms' => 'Client',
                'numero' => '+225-01-23-45-67-89'
            ],
            'with_parentheses' => [
                'nom' => 'Test',
                'prenoms' => 'Client',
                'numero' => '+225 (01) 23 45 67 89'
            ]
        ];
    }
    
    /**
     * Create expected response structure for client
     */
    public static function createExpectedClientResponse(array $clientData = []): array
    {
        return [
            'id' => $clientData['id'] ?? 1,
            'nom' => $clientData['nom'] ?? 'Test Nom',
            'prenom' => $clientData['prenom'] ?? 'Test Prenom',
            'numero' => $clientData['numero'] ?? '+225 0123456789',
            'photo' => $clientData['photo'] ?? null,
            'boutique' => $clientData['boutique'] ?? null,
            'succursale' => $clientData['succursale'] ?? null,
            'entreprise' => $clientData['entreprise'] ?? null,
            'createdAt' => $clientData['createdAt'] ?? null,
            'updatedAt' => $clientData['updatedAt'] ?? null
        ];
    }
    
    /**
     * Create pagination test data
     */
    public static function createPaginationTestData(): array
    {
        return [
            'page_1_size_10' => ['page' => 1, 'size' => 10],
            'page_2_size_5' => ['page' => 2, 'size' => 5],
            'page_1_size_1' => ['page' => 1, 'size' => 1],
            'large_page_size' => ['page' => 1, 'size' => 100],
            'zero_page' => ['page' => 0, 'size' => 10],
            'negative_page' => ['page' => -1, 'size' => 10],
            'zero_size' => ['page' => 1, 'size' => 0],
            'negative_size' => ['page' => 1, 'size' => -5]
        ];
    }
    
    /**
     * Generate test data for property-based testing
     */
    public static function generatePropertyTestData(int $count = 100): \Generator
    {
        for ($i = 0; $i < $count; $i++) {
            yield self::createRandomClientData();
        }
    }
}