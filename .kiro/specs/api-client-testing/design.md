# Design Document

## Overview

Ce document décrit la conception d'une suite de tests complète pour l'API de gestion des clients (`ApiClientController`) dans le système Ateliya. La solution comprend des tests unitaires, des tests d'intégration, et des tests basés sur les propriétés pour valider tous les aspects fonctionnels et non-fonctionnels de l'API.

## Architecture

### Test Architecture Pattern
La suite de tests suit une architecture en couches :

```
┌─────────────────────────────────────┐
│        Property-Based Tests        │  ← Validation des propriétés universelles
├─────────────────────────────────────┤
│         Integration Tests           │  ← Tests des endpoints complets
├─────────────────────────────────────┤
│           Unit Tests               │  ← Tests des composants isolés
├─────────────────────────────────────┤
│         Test Utilities             │  ← Helpers et fixtures
└─────────────────────────────────────┘
```

### Test Environment Setup
- **Database**: Base de données de test isolée avec transactions rollback
- **Authentication**: Mock JWT tokens pour différents types d'utilisateurs
- **File System**: Système de fichiers temporaire pour les tests d'upload
- **Subscription**: Mock du système d'abonnement pour tester les différents états

## Components and Interfaces

### Core Test Components

#### 1. ApiClientControllerTest
**Responsabilité**: Tests d'intégration des endpoints
**Interface**:
```php
class ApiClientControllerTest extends WebTestCase
{
    public function testListAllClients(): void
    public function testListClientsByUserRole(): void
    public function testGetClientDetails(): void
    public function testCreateClientForSuccursale(): void
    public function testCreateClientForBoutique(): void
    public function testUpdateClient(): void
    public function testDeleteClient(): void
    public function testBulkDeleteClients(): void
}
```

#### 2. ClientTestDataFactory
**Responsabilité**: Génération de données de test
**Interface**:
```php
class ClientTestDataFactory
{
    public static function createValidClientData(): array
    public static function createInvalidClientData(): array
    public static function createClientWithPhoto(): array
    public static function createMultipleClients(int $count): array
    public static function createClientForBoutique(): array
    public static function createClientForSuccursale(): array
}
```

#### 3. AuthenticationTestHelper
**Responsabilité**: Gestion de l'authentification dans les tests
**Interface**:
```php
class AuthenticationTestHelper
{
    public static function createSuperAdminToken(): string
    public static function createBoutiqueAdminToken(): string
    public static function createRegularUserToken(): string
    public static function createUserWithoutSubscription(): string
    public static function authenticateClient(KernelBrowser $client, string $token): void
}
```

#### 4. FileUploadTestHelper
**Responsabilité**: Gestion des tests d'upload de fichiers
**Interface**:
```php
class FileUploadTestHelper
{
    public static function createValidImageFile(): UploadedFile
    public static function createInvalidImageFile(): UploadedFile
    public static function createLargeImageFile(): UploadedFile
    public static function cleanupTestFiles(): void
}
```

### Data Models

#### ClientTestData Structure
```php
class ClientTestData
{
    public string $nom;
    public string $prenoms;
    public string $numero;
    public ?int $boutique;
    public ?int $succursale;
    public ?UploadedFile $photo;
    public array $expectedResponse;
    public int $expectedStatusCode;
}
```

#### TestScenario Structure
```php
class TestScenario
{
    public string $name;
    public string $method;
    public string $endpoint;
    public array $data;
    public array $headers;
    public int $expectedStatusCode;
    public array $expectedResponseStructure;
    public callable $assertions;
}
```

## Data Models

### Test Database Schema
Les tests utilisent les mêmes entités que le système principal :
- **Client**: Entité principale avec nom, prénom, numéro, photo
- **Boutique**: Entité boutique pour les associations
- **Succursale**: Entité succursale pour les associations  
- **Entreprise**: Contexte d'environnement
- **User**: Utilisateurs avec différents rôles
- **TypeUser**: Types d'utilisateurs (SADM, ADB, etc.)

### Test Data Relationships
```
Entreprise (1) ←→ (N) Boutique (1) ←→ (N) Client
Entreprise (1) ←→ (N) Succursale (1) ←→ (N) Client
User (1) ←→ (N) Client (via createdBy/updatedBy)
TypeUser (1) ←→ (N) User
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property Reflection

After analyzing all acceptance criteria, several properties can be consolidated to eliminate redundancy:

- Properties related to subscription checking (2.1, 3.3, 4.5, 7.5) can be combined into a single comprehensive subscription validation property
- Properties related to 404 responses (3.2, 6.2, 7.2) can be combined into a single not-found handling property  
- Properties related to success messages (7.3, 8.4) can be combined into a single success response property
- Properties related to error handling (1.3, 3.4, 7.4, 8.5, 10.3) can be combined into a single error handling property
- Properties related to authentication/authorization failures (10.4, 10.5) can be combined

### Core Properties

Property 1: Client list pagination consistency
*For any* valid pagination parameters, the client list endpoint should return results in the expected pagination format with proper metadata and all required client fields
**Validates: Requirements 1.1, 1.2, 1.4**

Property 2: Response headers consistency  
*For any* successful API response, the Content-Type header should be set to application/json
**Validates: Requirements 1.5**

Property 3: Role-based access filtering
*For any* authenticated user with active subscription, the client list should be filtered according to their role: SADM sees all enterprise clients, ADB sees boutique clients, others see succursale clients, all ordered by id ascending
**Validates: Requirements 2.2, 2.3, 2.4, 2.5**

Property 4: Client retrieval completeness
*For any* existing client ID with valid subscription, the get client endpoint should return complete client information including all relationships
**Validates: Requirements 3.1, 3.5**

Property 5: Client creation with associations
*For any* valid client data with required fields, the create endpoint should successfully create a client and properly associate it with provided boutique/succursale entities
**Validates: Requirements 4.1, 4.4, 5.1, 5.4**

Property 6: Required field validation
*For any* client creation request missing required fields (nom, prenoms, numero), the API should return validation errors
**Validates: Requirements 4.2, 5.2**

Property 7: Photo upload and naming
*For any* valid image file upload, the system should save the file with proper document naming convention and store it in the configured directory
**Validates: Requirements 4.3, 5.3, 9.3, 9.4**

Property 8: Trait entity configuration
*For any* successful client creation, the system should configure proper trait entity values including managed enterprise, created/updated timestamps and user references
**Validates: Requirements 5.5**

Property 9: Partial update behavior
*For any* existing client and partial update data, the update endpoint should modify only the provided fields while preserving others and updating audit fields
**Validates: Requirements 6.1, 6.3, 6.5**

Property 10: Photo replacement during update
*For any* client update with new photo file, the system should replace the existing photo with the new one
**Validates: Requirements 6.4**

Property 11: Client deletion success
*For any* existing client ID with valid subscription, the delete endpoint should remove the client and return success message "Operation effectuées avec succès"
**Validates: Requirements 7.1, 7.3**

Property 12: Bulk deletion with mixed IDs
*For any* array of client IDs (some valid, some invalid), the bulk delete endpoint should delete all existing clients, skip non-existent ones, and return success message
**Validates: Requirements 8.1, 8.3, 8.4**

Property 13: File validation and error handling
*For any* file upload, the system should validate format and size constraints, reject invalid files with appropriate errors, and handle upload failures gracefully
**Validates: Requirements 9.1, 9.2, 9.5**

Property 14: Subscription requirement enforcement
*For any* API endpoint requiring subscription, requests from users without active subscription should return 403 error with subscription required message
**Validates: Requirements 2.1, 3.3, 4.5, 7.5**

Property 15: Not found resource handling
*For any* request targeting non-existent client ID, the API should return 404 status code with "Cette ressource est inexistante" message
**Validates: Requirements 3.2, 6.2, 7.2**

Property 16: Structured error responses
*For any* validation error or constraint violation, the API should return structured error responses with appropriate HTTP status codes and field-specific messages
**Validates: Requirements 10.1, 10.2**

## Error Handling

### Error Categories and Responses

#### Authentication Errors (401)
- Missing or invalid JWT tokens
- Expired authentication tokens
- Malformed authorization headers

#### Authorization Errors (403)  
- Users without active subscription accessing protected endpoints
- Users accessing resources outside their permission scope
- Role-based access violations

#### Validation Errors (400)
- Missing required fields in request body
- Invalid data formats or types
- File upload validation failures
- Malformed JSON in request body

#### Not Found Errors (404)
- Requests for non-existent client IDs
- References to non-existent boutique/succursale entities
- Invalid endpoint paths

#### Server Errors (500)
- Database connection failures
- File system errors during upload
- Unexpected exceptions during processing
- External service failures

### Error Response Structure
All error responses follow a consistent structure:
```json
{
    "message": "Human-readable error description",
    "statusCode": 400,
    "data": null,
    "errors": {
        "field1": ["Specific validation error"],
        "field2": ["Another validation error"]
    }
}
```

## Testing Strategy

### Dual Testing Approach

The testing strategy employs both unit testing and property-based testing to provide comprehensive coverage:

**Unit Tests** verify:
- Specific examples that demonstrate correct behavior
- Edge cases and boundary conditions  
- Integration points between components
- Error conditions and exception handling

**Property-Based Tests** verify:
- Universal properties that should hold across all inputs
- Correctness guarantees for large input spaces
- Invariants that must be maintained
- Round-trip properties for data transformations

### Property-Based Testing Framework

**Framework**: PHPUnit with Eris (PHP property-based testing library)
**Configuration**: Minimum 100 iterations per property test
**Generators**: Custom generators for Client entities, User roles, File uploads, and API request data

### Test Categories

#### 1. API Endpoint Tests
- **Scope**: Full HTTP request/response cycle testing
- **Coverage**: All 8 endpoints with various input combinations
- **Authentication**: Different user roles and subscription states
- **Data Validation**: Valid and invalid request payloads

#### 2. Business Logic Tests  
- **Scope**: Core business rules and constraints
- **Coverage**: Role-based filtering, entity associations, validation rules
- **Edge Cases**: Boundary conditions, null values, empty collections

#### 3. File Upload Tests
- **Scope**: Photo upload functionality
- **Coverage**: Valid/invalid file types, size limits, naming conventions
- **Error Scenarios**: Upload failures, storage errors, permission issues

#### 4. Database Integration Tests
- **Scope**: Data persistence and retrieval
- **Coverage**: CRUD operations, transaction handling, constraint enforcement
- **Isolation**: Each test runs in isolated transaction with rollback

#### 5. Security Tests
- **Scope**: Authentication and authorization
- **Coverage**: JWT validation, role-based access, subscription checks
- **Attack Scenarios**: Invalid tokens, privilege escalation attempts

### Test Data Management

#### Fixtures and Factories
- **ClientFactory**: Generates valid client data with configurable associations
- **UserFactory**: Creates users with different roles and subscription states  
- **FileFactory**: Generates test files with various formats and sizes
- **DatabaseSeeder**: Sets up consistent test data across test runs

#### Test Database Strategy
- **Isolation**: Each test class uses separate database transactions
- **Cleanup**: Automatic rollback after each test method
- **Consistency**: Deterministic test data generation for reproducible results
- **Performance**: In-memory SQLite for fast test execution

### Continuous Integration

#### Test Execution Pipeline
1. **Static Analysis**: PHPStan and Psalm for code quality
2. **Unit Tests**: Fast-running isolated component tests
3. **Integration Tests**: API endpoint tests with database
4. **Property Tests**: Comprehensive property validation (100+ iterations)
5. **Coverage Analysis**: Minimum 90% code coverage requirement

#### Performance Benchmarks
- **Unit Tests**: < 5 seconds total execution time
- **Integration Tests**: < 30 seconds total execution time  
- **Property Tests**: < 60 seconds total execution time
- **Full Suite**: < 2 minutes total execution time