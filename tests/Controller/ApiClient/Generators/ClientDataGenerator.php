<?php

namespace App\Tests\Controller\ApiClient\Generators;

use Eris\Generator;

/**
 * Custom generators for Client API property-based testing
 */
class ClientDataGenerator
{
    /**
     * Generate valid client names (Ivorian names)
     */
    public static function validClientName(): Generator
    {
        $ivorianNames = [
            'Kouassi', 'Kone', 'Traore', 'Ouattara', 'Bamba', 'Yao', 'Assi', 'Diabate',
            'Doumbia', 'Coulibaly', 'Sanogo', 'Diarrassouba', 'N\'Guessan', 'Konan',
            'Silue', 'Toure', 'Gbagbo', 'Bedie', 'Tano', 'Akoto'
        ];
        
        return Generator\elements($ivorianNames);
    }
    
    /**
     * Generate valid client first names (Ivorian first names)
     */
    public static function validClientFirstName(): Generator
    {
        $ivorianFirstNames = [
            'Yao', 'Kouame', 'Koffi', 'Akissi', 'Amenan', 'Aya', 'Fatou', 'Aminata',
            'Moussa', 'Ibrahim', 'Mamadou', 'Ousmane', 'Adama', 'Seydou', 'Bakayoko',
            'Marie', 'Jean', 'Paul', 'Pierre', 'Francois', 'Emmanuel', 'Desire'
        ];
        
        return Generator\elements($ivorianFirstNames);
    }
    
    /**
     * Generate valid Ivorian phone numbers
     */
    public static function validIvorianPhoneNumber(): Generator
    {
        return Generator\bind(
            Generator\choose(1, 9), // First digit after country code
            function ($firstDigit) {
                return Generator\bind(
                    Generator\choose(0, 9), // Second digit
                    function ($secondDigit) use ($firstDigit) {
                        return Generator\bind(
                            Generator\string()->withCharset('0123456789')->withSize(8), // Remaining 8 digits
                            function ($remainingDigits) use ($firstDigit, $secondDigit) {
                                return Generator\constant("+225 0{$firstDigit}{$secondDigit}{$remainingDigits}");
                            }
                        );
                    }
                );
            }
        );
    }
    
    /**
     * Generate valid client data structure
     */
    public static function validClientData(): Generator
    {
        return Generator\bind(
            self::validClientName(),
            function ($nom) {
                return Generator\bind(
                    self::validClientFirstName(),
                    function ($prenom) use ($nom) {
                        return Generator\bind(
                            self::validIvorianPhoneNumber(),
                            function ($numero) use ($nom, $prenom) {
                                return Generator\bind(
                                    Generator\choose(1, 10), // boutique ID
                                    function ($boutiqueId) use ($nom, $prenom, $numero) {
                                        return Generator\bind(
                                            Generator\choose(1, 20), // succursale ID
                                            function ($succursaleId) use ($nom, $prenom, $numero, $boutiqueId) {
                                                return Generator\constant([
                                                    'nom' => $nom,
                                                    'prenoms' => $prenom,
                                                    'numero' => $numero,
                                                    'boutique' => $boutiqueId,
                                                    'succursale' => $succursaleId
                                                ]);
                                            }
                                        );
                                    }
                                );
                            }
                        );
                    }
                );
            }
        );
    }
    
    /**
     * Generate invalid client data (missing required fields)
     */
    public static function invalidClientData(): Generator
    {
        return Generator\oneOf(
            // Missing nom
            Generator\constant([
                'prenoms' => 'Valid Prenom',
                'numero' => '+225 0123456789',
                'boutique' => 1,
                'succursale' => 1
            ]),
            // Missing prenoms
            Generator\constant([
                'nom' => 'Valid Nom',
                'numero' => '+225 0123456789',
                'boutique' => 1,
                'succursale' => 1
            ]),
            // Missing numero
            Generator\constant([
                'nom' => 'Valid Nom',
                'prenoms' => 'Valid Prenom',
                'boutique' => 1,
                'succursale' => 1
            ]),
            // Empty strings
            Generator\constant([
                'nom' => '',
                'prenoms' => '',
                'numero' => '',
                'boutique' => 1,
                'succursale' => 1
            ])
        );
    }
    
    /**
     * Generate client data with edge cases
     */
    public static function edgeCaseClientData(): Generator
    {
        return Generator\oneOf(
            // Very long names
            Generator\bind(
                Generator\string()->withSize(255),
                function ($longString) {
                    return Generator\constant([
                        'nom' => $longString,
                        'prenoms' => 'Normal Prenom',
                        'numero' => '+225 0123456789',
                        'boutique' => 1,
                        'succursale' => 1
                    ]);
                }
            ),
            // Special characters
            Generator\constant([
                'nom' => "O'Connor-Smith",
                'prenoms' => 'Jean-François',
                'numero' => '+225 0123456789',
                'boutique' => 1,
                'succursale' => 1
            ]),
            // Unicode characters
            Generator\constant([
                'nom' => 'Kôné',
                'prenoms' => 'Fatoumata',
                'numero' => '+225 0123456789',
                'boutique' => 1,
                'succursale' => 1
            ]),
            // Numbers in names
            Generator\constant([
                'nom' => 'Client123',
                'prenoms' => 'Test456',
                'numero' => '+225 0123456789',
                'boutique' => 1,
                'succursale' => 1
            ])
        );
    }
    
    /**
     * Generate phone number variations
     */
    public static function phoneNumberVariations(): Generator
    {
        return Generator\oneOf(
            Generator\constant('+225 01 23 45 67 89'),      // International with spaces
            Generator\constant('01 23 45 67 89'),           // Local format
            Generator\constant('+2250123456789'),           // No spaces
            Generator\constant('+225-01-23-45-67-89'),      // With dashes
            Generator\constant('+225 (01) 23 45 67 89'),    // With parentheses
            Generator\constant('0123456789'),               // Simple format
            Generator\constant('+33123456789'),             // Different country code
            Generator\constant('invalid-phone'),            // Invalid format
            Generator\constant(''),                         // Empty
            Generator\constant('123')                       // Too short
        );
    }
    
    /**
     * Generate update data
     */
    public static function updateData(): Generator
    {
        return Generator\bind(
            Generator\oneOf(
                self::validClientName(),
                Generator\constant(null)
            ),
            function ($nom) {
                return Generator\bind(
                    Generator\oneOf(
                        self::validClientFirstName(),
                        Generator\constant(null)
                    ),
                    function ($prenom) use ($nom) {
                        return Generator\bind(
                            Generator\oneOf(
                                self::validIvorianPhoneNumber(),
                                Generator\constant(null)
                            ),
                            function ($numero) use ($nom, $prenom) {
                                $data = [];
                                if ($nom !== null) $data['nom'] = $nom;
                                if ($prenom !== null) $data['prenoms'] = $prenom;
                                if ($numero !== null) $data['numero'] = $numero;
                                
                                return Generator\constant($data);
                            }
                        );
                    }
                );
            }
        );
    }
    
    /**
     * Generate bulk delete data
     */
    public static function bulkDeleteData(): Generator
    {
        return Generator\bind(
            Generator\choose(1, 20), // Number of IDs
            function ($count) {
                return Generator\bind(
                    Generator\vector($count, Generator\choose(1, 1000)), // Array of IDs
                    function ($ids) {
                        return Generator\constant(['ids' => $ids]);
                    }
                );
            }
        );
    }
    
    /**
     * Generate pagination parameters
     */
    public static function paginationParams(): Generator
    {
        return Generator\bind(
            Generator\choose(1, 10), // Page number
            function ($page) {
                return Generator\bind(
                    Generator\choose(1, 50), // Page size
                    function ($size) use ($page) {
                        return Generator\constant([
                            'page' => $page,
                            'size' => $size
                        ]);
                    }
                );
            }
        );
    }
    
    /**
     * Generate HTTP status codes for testing
     */
    public static function httpStatusCodes(): Generator
    {
        return Generator\elements([200, 201, 400, 401, 403, 404, 422, 500]);
    }
    
    /**
     * Generate user types
     */
    public static function userTypes(): Generator
    {
        return Generator\elements(['SADM', 'ADB', 'REG', 'EMP', 'CLI']);
    }
    
    /**
     * Generate boolean values for subscription status
     */
    public static function subscriptionStatus(): Generator
    {
        return Generator\bool();
    }
    
    /**
     * Generate file upload scenarios
     */
    public static function fileUploadScenarios(): Generator
    {
        return Generator\elements([
            'valid_jpg',
            'valid_png',
            'invalid_format',
            'too_large',
            'empty_file',
            'corrupted',
            'no_file'
        ]);
    }
}