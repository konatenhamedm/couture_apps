<?php

namespace App\Tests\Controller\ApiClient;

/**
 * Configuration constants and settings for API Client tests
 */
class ApiClientTestConfig
{
    // API Endpoints
    public const ENDPOINT_LIST_ALL = '/api/client/';
    public const ENDPOINT_LIST_BY_ROLE = '/api/client/entreprise';
    public const ENDPOINT_GET_ONE = '/api/client/get/one/%d';
    public const ENDPOINT_CREATE = '/api/client/create';
    public const ENDPOINT_CREATE_BOUTIQUE = '/api/client/create/boutique';
    public const ENDPOINT_UPDATE = '/api/client/update/%d';
    public const ENDPOINT_DELETE = '/api/client/delete/%d';
    public const ENDPOINT_BULK_DELETE = '/api/client/delete/all/items';
    
    // HTTP Status Codes
    public const STATUS_OK = 200;
    public const STATUS_CREATED = 201;
    public const STATUS_BAD_REQUEST = 400;
    public const STATUS_UNAUTHORIZED = 401;
    public const STATUS_FORBIDDEN = 403;
    public const STATUS_NOT_FOUND = 404;
    public const STATUS_UNPROCESSABLE_ENTITY = 422;
    public const STATUS_INTERNAL_SERVER_ERROR = 500;
    
    // User Types
    public const USER_TYPE_SADM = 'SADM';
    public const USER_TYPE_ADB = 'ADB';
    public const USER_TYPE_REGULAR = 'REG';
    
    // Error Messages
    public const ERROR_SUBSCRIPTION_REQUIRED = 'Abonnement requis pour cette fonctionnalité';
    public const ERROR_RESOURCE_NOT_FOUND = 'Cette ressource est inexistante';
    public const ERROR_SUCCESS_MESSAGE = 'Operation effectuées avec succès';
    public const ERROR_CLIENT_RETRIEVAL = 'Erreur lors de la récupération des clients';
    public const ERROR_CLIENT_UPDATE = 'Erreur lors de la mise à jour du client';
    public const ERROR_CLIENT_DELETION = 'Erreur lors de la suppression du client';
    public const ERROR_BULK_DELETION = 'Erreur lors de la suppression des clients';
    
    // File Upload Settings
    public const UPLOAD_MAX_SIZE = 5 * 1024 * 1024; // 5MB
    public const UPLOAD_ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif'];
    public const UPLOAD_ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif'];
    public const UPLOAD_FILE_PREFIX = 'document_01';
    
    // Pagination Settings
    public const DEFAULT_PAGE_SIZE = 10;
    public const MAX_PAGE_SIZE = 100;
    public const MIN_PAGE_SIZE = 1;
    
    // Property-Based Testing Settings
    public const PROPERTY_TEST_ITERATIONS = 100;
    public const PROPERTY_TEST_MAX_SIZE = 1000;
    
    // Test Data Limits
    public const MAX_NAME_LENGTH = 255;
    public const MIN_NAME_LENGTH = 1;
    public const MAX_PHONE_LENGTH = 20;
    public const MIN_PHONE_LENGTH = 5;
    
    // Required Fields
    public const REQUIRED_FIELDS = ['nom', 'prenoms', 'numero'];
    public const OPTIONAL_FIELDS = ['photo', 'boutique', 'succursale'];
    
    // Response Structure Fields
    public const RESPONSE_FIELDS_CLIENT = [
        'id', 'nom', 'prenom', 'numero', 'photo', 
        'boutique', 'succursale', 'entreprise', 'createdAt'
    ];
    
    public const RESPONSE_FIELDS_PAGINATION = [
        'current_page', 'per_page', 'total', 'last_page'
    ];
    
    public const RESPONSE_FIELDS_ERROR = [
        'message', 'statusCode', 'data', 'errors'
    ];
    
    // Test Environment Settings
    public const TEST_DATABASE_NAME = 'test_ateliya';
    public const TEST_UPLOAD_DIR = '/tmp/test_uploads';
    public const TEST_JWT_SECRET = 'test-jwt-secret-key';
    public const TEST_JWT_EXPIRY = 3600; // 1 hour
    
    // Mock Data
    public const MOCK_ENTREPRISE_ID = 1;
    public const MOCK_BOUTIQUE_ID = 1;
    public const MOCK_SUCCURSALE_ID = 1;
    public const MOCK_USER_SADM_ID = 1;
    public const MOCK_USER_ADB_ID = 2;
    public const MOCK_USER_REGULAR_ID = 3;
    
    /**
     * Get endpoint URL with parameters
     */
    public static function getEndpointUrl(string $endpoint, ...$params): string
    {
        return sprintf($endpoint, ...$params);
    }
    
    /**
     * Get all API endpoints
     */
    public static function getAllEndpoints(): array
    {
        return [
            'list_all' => self::ENDPOINT_LIST_ALL,
            'list_by_role' => self::ENDPOINT_LIST_BY_ROLE,
            'get_one' => self::ENDPOINT_GET_ONE,
            'create' => self::ENDPOINT_CREATE,
            'create_boutique' => self::ENDPOINT_CREATE_BOUTIQUE,
            'update' => self::ENDPOINT_UPDATE,
            'delete' => self::ENDPOINT_DELETE,
            'bulk_delete' => self::ENDPOINT_BULK_DELETE
        ];
    }
    
    /**
     * Get all HTTP status codes used in tests
     */
    public static function getAllStatusCodes(): array
    {
        return [
            'ok' => self::STATUS_OK,
            'created' => self::STATUS_CREATED,
            'bad_request' => self::STATUS_BAD_REQUEST,
            'unauthorized' => self::STATUS_UNAUTHORIZED,
            'forbidden' => self::STATUS_FORBIDDEN,
            'not_found' => self::STATUS_NOT_FOUND,
            'unprocessable_entity' => self::STATUS_UNPROCESSABLE_ENTITY,
            'internal_server_error' => self::STATUS_INTERNAL_SERVER_ERROR
        ];
    }
    
    /**
     * Get all user types
     */
    public static function getAllUserTypes(): array
    {
        return [
            'sadm' => self::USER_TYPE_SADM,
            'adb' => self::USER_TYPE_ADB,
            'regular' => self::USER_TYPE_REGULAR
        ];
    }
    
    /**
     * Get file upload configuration
     */
    public static function getUploadConfig(): array
    {
        return [
            'max_size' => self::UPLOAD_MAX_SIZE,
            'allowed_types' => self::UPLOAD_ALLOWED_TYPES,
            'allowed_extensions' => self::UPLOAD_ALLOWED_EXTENSIONS,
            'file_prefix' => self::UPLOAD_FILE_PREFIX
        ];
    }
    
    /**
     * Get pagination configuration
     */
    public static function getPaginationConfig(): array
    {
        return [
            'default_size' => self::DEFAULT_PAGE_SIZE,
            'max_size' => self::MAX_PAGE_SIZE,
            'min_size' => self::MIN_PAGE_SIZE
        ];
    }
    
    /**
     * Get property-based testing configuration
     */
    public static function getPropertyTestConfig(): array
    {
        return [
            'iterations' => self::PROPERTY_TEST_ITERATIONS,
            'max_size' => self::PROPERTY_TEST_MAX_SIZE
        ];
    }
    
    /**
     * Validate if status code is expected for endpoint
     */
    public static function isValidStatusCodeForEndpoint(string $endpoint, int $statusCode): bool
    {
        $validCodes = [
            self::ENDPOINT_LIST_ALL => [200, 500],
            self::ENDPOINT_LIST_BY_ROLE => [200, 401, 403, 500],
            self::ENDPOINT_GET_ONE => [200, 401, 403, 404, 500],
            self::ENDPOINT_CREATE => [201, 400, 401, 403],
            self::ENDPOINT_CREATE_BOUTIQUE => [201, 400, 401, 403],
            self::ENDPOINT_UPDATE => [200, 400, 401, 403, 404, 500],
            self::ENDPOINT_DELETE => [200, 401, 403, 404, 500],
            self::ENDPOINT_BULK_DELETE => [200, 400, 401, 403, 500]
        ];
        
        return in_array($statusCode, $validCodes[$endpoint] ?? []);
    }
}