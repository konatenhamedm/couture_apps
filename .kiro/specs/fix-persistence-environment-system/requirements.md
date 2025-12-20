# Requirements Document

## Introduction

Ce document définit les exigences pour résoudre le problème de persistance dans le système à deux environnements qui empêche la création, mise à jour et suppression d'entités (notamment les clients). L'erreur "Column 'libelle' cannot be null" indique un problème dans la gestion des entités liées lors des opérations de persistance.

## Glossary

- **System**: Le système de gestion d'atelier (Ateliya)
- **Environment_System**: Le système à deux environnements (dev/prod) mis en place récemment
- **Entity_Manager**: Le gestionnaire d'entités Doctrine ORM
- **Persistence_Operation**: Opération de sauvegarde, mise à jour ou suppression en base de données
- **Related_Entity**: Entité liée (Boutique, Surccursale, Entreprise, etc.) qui possède une colonne 'libelle'
- **Client_Entity**: L'entité Client qui ne possède pas de colonne 'libelle'
- **Cascade_Persist**: Mécanisme Doctrine qui persiste automatiquement les entités liées

## Requirements

### Requirement 1

**User Story:** En tant que développeur, je veux que les opérations de persistance fonctionnent correctement avec le système à deux environnements, afin que les utilisateurs puissent créer, modifier et supprimer des entités sans erreur.

#### Acceptance Criteria

1. WHEN a user creates a client THEN the system SHALL persist the client without "Column 'libelle' cannot be null" errors
2. WHEN a user updates a client THEN the system SHALL save the changes without constraint violation errors
3. WHEN a user deletes a client THEN the system SHALL remove the client without persistence errors
4. WHEN the system associates related entities to a client THEN the system SHALL ensure all required fields are properly set
5. WHEN the system uses cascade persist THEN the system SHALL validate that related entities have all required non-null fields

### Requirement 2

**User Story:** En tant que développeur, je veux que le système gère correctement les entités liées lors des opérations de persistance, afin d'éviter les erreurs de contrainte de base de données.

#### Acceptance Criteria

1. WHEN the system retrieves entities from environment-specific repositories THEN the system SHALL ensure entities are properly managed by the correct EntityManager
2. WHEN the system associates a Boutique to a client THEN the system SHALL verify the Boutique has a valid 'libelle' field
3. WHEN the system associates a Surccursale to a client THEN the system SHALL verify the Surccursale has a valid 'libelle' field
4. WHEN the system uses getManagedEntityFromEnvironment THEN the system SHALL return entities that are properly attached to the current EntityManager context
5. WHEN the system performs cascade operations THEN the system SHALL ensure all related entities are in a valid state for persistence

### Requirement 3

**User Story:** En tant que développeur, je veux diagnostiquer et corriger les problèmes de mapping entre les environnements, afin que les opérations de persistance utilisent les bonnes tables et colonnes.

#### Acceptance Criteria

1. WHEN the system performs a persistence operation THEN the system SHALL use the correct database schema for the current environment
2. WHEN the system encounters entity mapping conflicts THEN the system SHALL log detailed error information for debugging
3. WHEN the system switches between environments THEN the system SHALL clear entity manager caches to avoid stale references
4. WHEN the system validates entities before persistence THEN the system SHALL check that all non-nullable fields are properly set
5. WHEN the system detects schema mismatches THEN the system SHALL provide clear error messages indicating the specific problem

### Requirement 4

**User Story:** En tant que développeur, je veux améliorer la gestion des entités détachées dans le système à deux environnements, afin d'éviter les erreurs de persistance liées aux proxies et entités non gérées.

#### Acceptance Criteria

1. WHEN the system retrieves entities from different environments THEN the system SHALL ensure entities are properly reattached to the current EntityManager
2. WHEN the system encounters detached entities THEN the system SHALL refresh or merge them with the current persistence context
3. WHEN the system uses proxy entities THEN the system SHALL resolve them to managed entities before persistence operations
4. WHEN the system performs cross-environment operations THEN the system SHALL handle entity state transitions correctly
5. WHEN the system validates entity relationships THEN the system SHALL ensure all related entities are in the same persistence context

### Requirement 5

**User Story:** En tant que développeur, je veux implémenter une validation robuste des entités avant persistance, afin de détecter et corriger les problèmes avant qu'ils causent des erreurs de base de données.

#### Acceptance Criteria

1. WHEN the system prepares an entity for persistence THEN the system SHALL validate all required fields are set
2. WHEN the system detects missing required fields THEN the system SHALL provide specific error messages indicating which fields are missing
3. WHEN the system validates related entities THEN the system SHALL check that cascade operations will not fail
4. WHEN the system encounters validation errors THEN the system SHALL prevent the persistence operation and return detailed error information
5. WHEN the system performs bulk operations THEN the system SHALL validate each entity individually before proceeding