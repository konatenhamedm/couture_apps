# Design Document - Client API Testing

## Overview

This design document outlines a comprehensive testing framework for the Client API endpoints in the Ateliya Couture application. The testing system will validate all CRUD operations, authentication mechanisms, role-based access control, file upload functionality, and environment switching capabilities. The design emphasizes automated testing with property-based testing for comprehensive coverage and unit tests for specific scenarios.

## Architecture

The testing architecture follows a layered approach:

```
┌─────────────────────────────────────────┐
│           Test Orchestrator             │
│  (Main test runner and coordination)    │
└─────────────────────────────────────────┘
                    │
┌─────────────────────────────────────────┐
│         Test Categories Layer           │
│ ┌─────────┐ ┌─────────┐ ┌─────────────┐ │
│ │  Auth   │ │  CRUD   │ │ Role-Based  │ │
│ │ Tests   │ │ Tests   │ │   Access    │ │
│ └─────────┘ └─────────┘ └─────────────┘ │
│ ┌─────────┐ ┌─────────┐ ┌─────────────┐ │
│ │  File   │ │ Batch   │ │Environment  │ │
│ │ Upload  │ │  Ops    │ │ Switching   │ │
│ └─────────┘ └─────────┘ └─────────────┘ │
└─────────────────────────────────────────┘
                    │
┌─────────────────────────────────────────┐
│        API Client & Utilities           │
│ ┌─────────────┐ ┌─────────────────────┐ │
│ │ HTTP Client │ │ Test Data Generator │ │
│ └─────────────┘ └─────────────────────┘ │
│ ┌─────────────┐ ┌─────────────────────┐ │
│ │Auth Manager │ │ Response Validator  │ │
│ └─────────────┘ └─────────────────────┘ │
└─────────────────────────────────────────┘
                    │
┌─────────────────────────────────────────┐
│          Target API Layer               │
│        (Client API Endpoints)           │
└─────────────────────────────────────────┘
```

## Components and Interfaces

### 1. Test Orchestrator
**Purpose**: Coordinates test execution, manages test environments, and aggregates results.

**Key Methods**:
- `runAllTests()`: Executes complete test suite
- `setupTestEnvironment()`: Prepares test data and authentication
- `cleanupTestEnvironment()`: Removes test data after execution
- `generateTestReport()`: Creates comprehensive test results report

### 2. Authentication Manager
**Purpose**: Handles JWT token generation, validation, and role-based authentication for tests.

**Key Methods**:
- `getValidToken(userType)`: Returns valid JWT token for specified user role
- `getExpiredToken()`: Returns expired token for negative testing
- `getMalformedToken()`: Returns invalid token for security testing
- `loginWithCredentials(username, password, env)`: Performs login and returns token

### 3. HTTP Client Wrapper
**Purpose**: Provides standardized HTTP request handling with environment switching support.

**Key Methods**:
- `get(endpoint, headers, params)`: Performs GET requests with environment support
- `post(endpoint, data, headers, files)`: Handles POST requests including file uploads
- `put(endpoint, data, headers, files)`: Manages PUT requests for updates
- `delete(endpoint, headers)`: Executes DELETE operations
- `setEnvironment(env)`: Switches between dev/prod environments

### 4. Test Data Generator
**Purpose**: Creates realistic test data for client entities and related objects.

**Key Methods**:
- `generateClient()`: Creates random client data
- `generateClientWithPhoto()`: Creates client data with photo file
- `generateInvalidClient()`: Creates invalid data for negative testing
- `generateBulkClients(count)`: Creates multiple client records
- `generatePhotoFile(format, size)`: Creates test image files

### 5. Response Validator
**Purpose**: Validates API responses against expected schemas and business rules.

**Key Methods**:
- `validateClientResponse(response)`: Validates client entity structure
- `validateErrorResponse(response, expectedCode)`: Validates error responses
- `validatePaginationResponse(response)`: Validates paginated results
- `validateFileUploadResponse(response)`: Validates photo upload responses

## Data Models

### Client Test Entity
```typescript
interface ClientTestData {
  nom: string;           // Client last name
  prenoms: string;       // Client first name(s)
  numero: string;        // Phone number
  boutique?: number;     // Boutique ID (optional)
  succursale?: number;   // Succursale ID (optional)
  photo?: File;          // Photo file (optional)
}
```

### Test Configuration
```typescript
interface TestConfig {
  baseUrl: string;       // API base URL
  environment: 'dev' | 'prod';
  credentials: {
    admin: { login: string; password: string };
    manager: { login: string; password: string };
  };
  timeouts: {
    request: number;     // Request timeout in ms
    test: number;        // Individual test timeout
  };
  fileUpload: {
    maxSize: number;     // Maximum file size
    allowedFormats: string[];
  };
}
```

### Test Result Model
```typescript
interface TestResult {
  testName: string;
  status: 'passed' | 'failed' | 'skipped';
  duration: number;
  error?: string;
  details?: any;
}
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Authentication Token Validation
*For any* Client API endpoint, when called without a valid authentication token, the response should always be HTTP 401 Unauthorized
**Validates: Requirements 2.1, 2.2, 2.3**

### Property 2: Role-Based Access Control
*For any* user role and Client API endpoint, the returned data should only include clients that the user has permission to access based on their role and associated boutique/succursale
**Validates: Requirements 5.1, 5.2, 5.3, 5.4, 5.5**

### Property 3: Data Validation Consistency
*For any* Client API endpoint that accepts input data, when provided with invalid data, the response should always be HTTP 400 with descriptive validation errors
**Validates: Requirements 3.1, 3.2, 3.3**

### Property 4: CRUD Operation Idempotency
*For any* valid client data, creating a client and then retrieving it should return the same data that was originally submitted (excluding system-generated fields)
**Validates: Requirements 1.4, 1.5, 9.2, 9.3**

### Property 5: Environment Isolation
*For any* Client API operation performed in the development environment, it should not affect data in the production environment and vice versa
**Validates: Requirements 7.1, 7.2, 7.3, 7.4, 7.5**

### Property 6: File Upload Round Trip
*For any* valid image file uploaded with a client, the returned photo path should reference an accessible file that matches the original upload
**Validates: Requirements 4.1, 4.4**

### Property 7: Batch Operation Consistency
*For any* list of valid client IDs, performing batch delete should successfully remove all specified clients and leave all other clients unchanged
**Validates: Requirements 6.1, 6.2**

### Property 8: Response Format Consistency
*For any* Client API endpoint, the response should always be valid JSON with consistent structure including required fields and proper HTTP status codes
**Validates: Requirements 9.1, 9.2, 9.4, 9.5**

### Property 9: Subscription Requirement Enforcement
*For any* Client API endpoint (except basic listing), when called by a user without an active subscription, the response should always be HTTP 403 with subscription required message
**Validates: Requirements 2.4**

### Property 10: Pagination Consistency
*For any* paginated Client API endpoint, the total count of items across all pages should equal the sum of items in individual pages
**Validates: Requirements 10.1, 10.2**

## Error Handling

### Authentication Errors
- **401 Unauthorized**: Missing, expired, or invalid JWT tokens
- **403 Forbidden**: Valid token but insufficient permissions or no active subscription

### Validation Errors
- **400 Bad Request**: Invalid input data, missing required fields, or malformed requests
- **422 Unprocessable Entity**: Valid format but business rule violations

### Resource Errors
- **404 Not Found**: Requested client or related entity does not exist
- **409 Conflict**: Duplicate client data or constraint violations

### System Errors
- **500 Internal Server Error**: Database connectivity issues or unexpected system failures
- **503 Service Unavailable**: System maintenance or temporary unavailability

### File Upload Errors
- **413 Payload Too Large**: Uploaded file exceeds size limits
- **415 Unsupported Media Type**: Invalid file format for photo uploads

## Testing Strategy

### Dual Testing Approach

The testing strategy employs both unit testing and property-based testing approaches:

**Unit Testing Requirements**:
- Unit tests verify specific examples, edge cases, and error conditions
- Unit tests cover integration points between components
- Focus on concrete scenarios and known edge cases
- Validate specific API response formats and error messages

**Property-Based Testing Requirements**:
- Use Jest with fast-check library for property-based testing in JavaScript/TypeScript
- Configure each property-based test to run a minimum of 100 iterations
- Tag each property-based test with format: `**Feature: client-api-testing, Property {number}: {property_text}**`
- Each correctness property must be implemented by a single property-based test
- Property tests verify universal properties across all valid inputs
- Generate random test data to discover edge cases automatically

**Test Categories**:

1. **Authentication Tests** (Unit + Property)
   - Valid token scenarios
   - Invalid token scenarios  
   - Role-based access validation

2. **CRUD Operation Tests** (Unit + Property)
   - Create client scenarios
   - Read client scenarios
   - Update client scenarios
   - Delete client scenarios

3. **File Upload Tests** (Unit + Property)
   - Valid file upload scenarios
   - Invalid file format/size scenarios
   - Photo replacement scenarios

4. **Batch Operation Tests** (Unit + Property)
   - Multiple client deletion
   - Partial success scenarios
   - Error handling in batch operations

5. **Environment Switching Tests** (Unit + Property)
   - Development environment isolation
   - Production environment isolation
   - Cross-environment data validation

6. **Performance Tests** (Unit)
   - Response time validation
   - Pagination performance
   - Load testing scenarios

### Test Data Management

**Test Data Strategy**:
- Use the existing fixture system to populate test databases
- Generate random test data using property-based testing generators
- Implement cleanup procedures to maintain test isolation
- Use separate test databases for development and production testing

**File Upload Testing**:
- Generate test images in various formats (JPG, PNG, GIF)
- Create files of different sizes to test upload limits
- Use temporary file storage with automatic cleanup
- Validate uploaded files are accessible and correctly stored

### Property Reflection

After reviewing all properties identified in the prework analysis, I've identified several areas for consolidation:

**Redundancy Analysis**:
- Properties 2.1, 2.2, 2.3 (authentication failures) can be combined into a single comprehensive authentication property
- Properties 5.1, 5.2, 5.3, 5.4, 5.5 (role-based access) can be consolidated into one role-based filtering property
- Properties 7.1, 7.2, 7.3, 7.4, 7.5 (environment switching) can be combined into environment isolation property
- Properties 9.1, 9.2, 9.4, 9.5 (response format) can be consolidated into response format consistency property

**Consolidated Properties**:
The final set of properties eliminates redundancy while maintaining comprehensive coverage of all testable acceptance criteria.

## Correctness Properties (Continued)

### Property 1: Authentication Requirement Enforcement
*For any* Client API endpoint, when called without a valid authentication token (missing, expired, or malformed), the response should always be HTTP 401 Unauthorized
**Validates: Requirements 2.1, 2.2, 2.3**

### Property 2: Role-Based Data Filtering
*For any* authenticated user and Client API endpoint, the returned client data should only include clients that the user has permission to access based on their role (SADM sees all enterprise clients, ADB sees boutique clients, EMP/CAIS see succursale clients)
**Validates: Requirements 5.1, 5.2, 5.3, 5.4, 5.5**

### Property 3: Input Validation Consistency
*For any* Client API endpoint that accepts input data, when provided with invalid data (missing required fields, wrong data types, invalid formats), the response should always be HTTP 400 with descriptive validation errors
**Validates: Requirements 3.1, 3.2, 3.3**

### Property 4: CRUD Operation Data Integrity
*For any* valid client data, performing create then retrieve operations should return the same data that was originally submitted (excluding system-generated fields like ID, timestamps)
**Validates: Requirements 1.4, 1.5, 9.3**

### Property 5: Environment Data Isolation
*For any* Client API operation, performing the same operation in different environments (dev/prod) should maintain complete data isolation with no cross-environment data leakage
**Validates: Requirements 7.1, 7.2, 7.3, 7.4, 7.5**

### Property 6: File Upload Round Trip Integrity
*For any* valid image file uploaded with a client, the returned photo path should reference an accessible file that can be retrieved and matches the original upload characteristics
**Validates: Requirements 4.1, 4.4**

### Property 7: Batch Operation Atomicity
*For any* list of client IDs in batch delete operations, the operation should either succeed for all valid IDs or handle invalid IDs gracefully without affecting valid deletions
**Validates: Requirements 6.1, 6.2, 6.5**

### Property 8: Response Format Compliance
*For any* Client API endpoint response, the response should always be valid JSON with consistent structure, proper HTTP status codes, and required CORS headers
**Validates: Requirements 9.1, 9.2, 9.4, 9.5**

### Property 9: Subscription Access Control
*For any* Client API endpoint (except basic listing), when called by a user without an active subscription, the response should always be HTTP 403 with subscription required message
**Validates: Requirements 2.4**

### Property 10: Resource Existence Validation
*For any* Client API endpoint that references a specific client ID, when the ID does not exist, the response should always be HTTP 404 with appropriate not found message
**Validates: Requirements 3.4, 3.5**

### Property 11: File Format Validation
*For any* file upload attempt with invalid format or size, the Client API should reject the upload with HTTP 400 and descriptive error message
**Validates: Requirements 4.2, 4.3**

### Property 12: Pagination Metadata Accuracy
*For any* paginated Client API response, the pagination metadata should accurately reflect the total count, current page, and items per page
**Validates: Requirements 10.1, 10.2**

### Property 13: Special Character Handling
*For any* client data containing special characters or Unicode, the Client API should properly encode, store, and retrieve the data without corruption
**Validates: Requirements 8.3**

### Property 14: Large Payload Handling
*For any* Client API request with extremely large payloads, the system should either process successfully or return appropriate size limit errors
**Validates: Requirements 8.2**

### Property 15: Query Performance Consistency
*For any* Client API endpoint with filtering or search parameters, the response time should remain within acceptable thresholds regardless of dataset size
**Validates: Requirements 10.4**