# Design Document - Fix Persistence Environment System

## Overview

Ce document décrit la solution pour résoudre les problèmes de persistance dans le système à deux environnements. Le problème principal est que lors de la création d'entités comme Client, le système génère une erreur "Column 'libelle' cannot be null" alors que l'entité Client n'a pas de colonne 'libelle'. Cette erreur indique un problème dans la gestion des entités liées et des opérations de cascade persist.

## Architecture

### Problème Identifié

L'analyse du code révèle plusieurs problèmes potentiels :

1. **Entités Liées avec Cascade Persist** : Les entités Boutique, Surccursale, Entreprise ont toutes une colonne 'libelle' obligatoire
2. **Gestion des Entités Détachées** : Le système à deux environnements peut créer des entités détachées qui causent des problèmes lors de la persistance
3. **Mapping d'Environnement** : Les méthodes `getManagedEntityFromEnvironment` peuvent retourner des entités dans un état incorrect
4. **Validation Insuffisante** : Le système ne valide pas suffisamment les entités liées avant la persistance

### Solution Proposée

La solution comprend trois composants principaux :

1. **Enhanced Entity Validation Service** : Service de validation renforcé pour les entités avant persistance
2. **Environment-Aware Entity Manager** : Gestionnaire d'entités amélioré pour le système à deux environnements
3. **Cascade Persist Validator** : Validateur spécialisé pour les opérations de cascade persist

## Components and Interfaces

### 1. Enhanced Entity Validation Service

```php
interface EntityValidationServiceInterface
{
    public function validateForPersistence(object $entity): ValidationResult;
    public function validateRelatedEntities(object $entity): ValidationResult;
    public function validateCascadeOperations(object $entity): ValidationResult;
}
```

### 2. Environment Entity Manager

```php
interface EnvironmentEntityManagerInterface
{
    public function ensureEntityIsManaged(object $entity): object;
    public function refreshEntityInCurrentContext(object $entity): object;
    public function validateEntityContext(object $entity): bool;
}
```

### 3. Persistence Operation Handler

```php
interface PersistenceOperationHandlerInterface
{
    public function safePersist(object $entity): PersistenceResult;
    public function safeUpdate(object $entity): PersistenceResult;
    public function safeRemove(object $entity): PersistenceResult;
}
```

## Data Models

### ValidationResult

```php
class ValidationResult
{
    private bool $isValid;
    private array $errors;
    private array $warnings;
    private ?object $correctedEntity;
}
```

### PersistenceResult

```php
class PersistenceResult
{
    private bool $success;
    private ?object $entity;
    private array $errors;
    private string $operation;
}
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property Reflection

Après analyse des propriétés identifiées, plusieurs peuvent être consolidées pour éviter la redondance :

- Les propriétés 2.2 et 2.3 (validation de Boutique et Surccursale) peuvent être combinées en une propriété générale de validation des entités liées
- Les propriétés 1.4 et 2.5 (validation des entités liées et cascade) se chevauchent et peuvent être fusionnées
- Les propriétés 3.4 et 5.1 (validation des champs requis) sont redondantes
- Les propriétés 4.1 et 4.2 (gestion des entités détachées) peuvent être combinées

### Correctness Properties

Property 1: Client persistence operations succeed
*For any* valid client data, creating, updating, or deleting a client should complete without "Column 'libelle' cannot be null" or other constraint violation errors
**Validates: Requirements 1.1, 1.2, 1.3**

Property 2: Related entity validation before persistence
*For any* client with associated entities (Boutique, Surccursale, Entreprise), all related entities should have valid required fields (including 'libelle') before persistence operations
**Validates: Requirements 1.4, 1.5, 2.2, 2.3**

Property 3: Environment-specific entity management
*For any* entity retrieved from environment-specific repositories, the entity should be properly managed by the correct EntityManager and attached to the current persistence context
**Validates: Requirements 2.1, 2.4, 4.1, 4.2**

Property 4: Cascade operation validity
*For any* entity with cascade persist relationships, all related entities should be in a valid state for persistence and in the same persistence context
**Validates: Requirements 2.5, 4.5**

Property 5: Environment schema consistency
*For any* persistence operation, the system should use the correct database schema for the current environment and handle environment switches correctly
**Validates: Requirements 3.1, 3.3**

Property 6: Comprehensive error handling and logging
*For any* persistence error or validation failure, the system should provide detailed error messages and appropriate logging for debugging
**Validates: Requirements 3.2, 3.5, 5.2, 5.4**

Property 7: Pre-persistence validation completeness
*For any* entity prepared for persistence, all required non-nullable fields should be validated and any missing fields should prevent the operation with specific error messages
**Validates: Requirements 3.4, 5.1, 5.3**

Property 8: Detached entity resolution
*For any* detached or proxy entity encountered during persistence operations, the system should properly resolve and reattach it to the current EntityManager context
**Validates: Requirements 4.3, 4.4**

Property 9: Bulk operation individual validation
*For any* bulk persistence operation, each entity should be validated individually and invalid entities should not prevent valid ones from being processed
**Validates: Requirements 5.5**

## Error Handling

### Error Categories

1. **Validation Errors**: Missing required fields, invalid data formats
2. **Entity Management Errors**: Detached entities, wrong persistence context
3. **Environment Errors**: Wrong database schema, environment switching issues
4. **Cascade Errors**: Invalid related entities, cascade persist failures

### Error Response Strategy

- **Immediate Validation**: Validate entities before any persistence operation
- **Detailed Error Messages**: Provide specific information about what went wrong
- **Graceful Degradation**: Continue processing valid entities in bulk operations
- **Comprehensive Logging**: Log all errors with context for debugging

## Testing Strategy

### Unit Testing Requirements

Unit tests will focus on:
- Individual component functionality (validation services, entity managers)
- Specific error scenarios and edge cases
- Mock-based testing of environment switching
- Integration points between components

### Property-Based Testing Requirements

The testing framework will use **PHPUnit with Eris** for property-based testing in PHP. Each property-based test will run a minimum of 100 iterations to ensure comprehensive coverage.

Property-based tests will:
- Generate random client data and verify persistence operations succeed
- Create random entity associations and validate related entity requirements
- Test environment switching scenarios with various entity states
- Validate error handling across different failure modes

Each property-based test will be tagged with comments referencing the specific correctness property:
- Format: `**Feature: fix-persistence-environment-system, Property {number}: {property_text}**`

### Test Data Generation Strategy

- **Client Data Generator**: Creates valid client entities with random but realistic data
- **Related Entity Generator**: Creates Boutique, Surccursale, and Entreprise entities with proper 'libelle' fields
- **Environment State Generator**: Simulates different environment states and EntityManager contexts
- **Error Scenario Generator**: Creates specific error conditions for testing error handling

### Integration Testing

Integration tests will verify:
- End-to-end client creation, update, and deletion workflows
- Cross-environment operations and data consistency
- Real database operations in both dev and prod environments
- Performance impact of validation and entity management improvements

## Implementation Approach

### Phase 1: Enhanced Validation Service
- Create comprehensive entity validation service
- Implement pre-persistence validation for all entities
- Add specific validation for entities with 'libelle' fields

### Phase 2: Environment Entity Management
- Improve getManagedEntityFromEnvironment method
- Add entity context validation and reattachment logic
- Enhance environment switching with proper cache clearing

### Phase 3: Cascade Operation Safety
- Implement cascade persist validation
- Add related entity state verification
- Create safe persistence operation handlers

### Phase 4: Error Handling and Logging
- Enhance error messages with specific field information
- Add comprehensive logging for debugging
- Implement graceful error recovery mechanisms

### Phase 5: Testing and Validation
- Implement property-based tests for all correctness properties
- Add comprehensive unit tests for new components
- Perform integration testing across environments