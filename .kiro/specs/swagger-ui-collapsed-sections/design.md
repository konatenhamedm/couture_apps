# Design Document: Swagger UI Collapsed Sections

## Overview

Cette conception détaille l'implémentation de la configuration Swagger UI pour afficher les sections d'API fermées par défaut. La solution utilise les paramètres de configuration de NelmioApiDoc pour personnaliser l'interface Swagger UI sans affecter la fonctionnalité existante.

## Architecture

### Configuration Layer
- **NelmioApiDoc Configuration**: Point central de configuration dans `config/packages/nelmio_api_doc.yaml`
- **Swagger UI Parameters**: Paramètres spécifiques pour contrôler l'affichage de l'interface
- **Environment Support**: Possibilité de configurations différentes par environnement

### Interface Layer
- **Swagger UI Frontend**: Interface utilisateur web générée automatiquement
- **Custom CSS/JS**: Personnalisations additionnelles si nécessaire
- **Responsive Behavior**: Maintien de la responsivité sur tous les appareils

## Components and Interfaces

### 1. Configuration Component

**File**: `config/packages/nelmio_api_doc.yaml`

**Structure**:
```yaml
nelmio_api_doc:
  documentation:
    # Configuration existante...
  
  # Nouvelle section pour Swagger UI
  swagger_ui_config:
    docExpansion: "none"        # Sections fermées par défaut
    defaultModelsExpandDepth: 1  # Profondeur d'expansion des modèles
    defaultModelExpandDepth: 1   # Profondeur d'expansion des propriétés
    displayOperationId: false    # Masquer les IDs d'opération
    displayRequestDuration: true # Afficher la durée des requêtes
    filter: true                 # Activer la recherche
    showExtensions: false        # Masquer les extensions
    showCommonExtensions: false  # Masquer les extensions communes
```

**Interface**:
- Input: Configuration YAML
- Output: Paramètres Swagger UI appliqués

### 2. Template Override Component (si nécessaire)

**Purpose**: Personnalisation avancée de l'interface Swagger UI

**Files**:
- `templates/bundles/NelmioApiDocBundle/SwaggerUi/index.html.twig` (override optionnel)

**Functionality**:
- Injection de CSS/JavaScript personnalisé
- Modification de l'apparence par défaut
- Configuration dynamique côté client

## Data Models

### Configuration Schema

```yaml
SwaggerUIConfig:
  docExpansion: string         # "none" | "list" | "full"
  defaultModelsExpandDepth: integer
  defaultModelExpandDepth: integer
  displayOperationId: boolean
  displayRequestDuration: boolean
  filter: boolean
  showExtensions: boolean
  showCommonExtensions: boolean
```

### Environment Variables (optionnel)

```env
SWAGGER_DOC_EXPANSION=none
SWAGGER_FILTER_ENABLED=true
SWAGGER_SHOW_DURATION=true
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Section expansion independence
*For any* Swagger UI interface with multiple API sections, expanding one section should not affect the collapsed/expanded state of other sections
**Validates: Requirements 1.4**

### Property 2: UI interaction consistency
*For any* section header in the Swagger UI, clicking it should toggle the expansion state of that specific section
**Validates: Requirements 1.2**

### Property 3: API documentation completeness preservation
*For any* existing API endpoint, the collapsed UI configuration should not remove or hide any endpoint documentation or functionality
**Validates: Requirements 2.4**

### Property 4: Functional equivalence across UI states
*For any* API endpoint, the testing and interaction functionality should work identically whether the containing section is collapsed or expanded
**Validates: Requirements 3.1, 3.4**

### Property 5: Expanded section content completeness
*For any* expanded API section, all endpoint details, parameters, schemas, and interactive features should be fully displayed and functional
**Validates: Requirements 3.2**

### Property 6: Search functionality independence
*For any* search query in Swagger UI, results should include endpoints from both collapsed and expanded sections without discrimination
**Validates: Requirements 3.3**

### Property 7: Configuration parameter support
*For any* valid docExpansion value ("none", "list", "full"), the Swagger UI should apply the corresponding display behavior correctly
**Validates: Requirements 4.3**

## Error Handling

### Configuration Errors
- **Invalid docExpansion values**: Default to "none" if invalid value provided
- **Missing configuration**: Fall back to Swagger UI defaults
- **YAML syntax errors**: Log error and use default configuration

### Runtime Errors
- **JavaScript errors**: Graceful degradation to standard Swagger UI behavior
- **CSS loading failures**: Maintain functionality without custom styling
- **Template override errors**: Fall back to default NelmioApiDoc templates

### Validation
- Configuration parameter validation at application startup
- Error logging for invalid configuration values
- Graceful fallback mechanisms for all error scenarios

## Testing Strategy

### Unit Tests
- Configuration loading and validation
- Parameter application verification
- Error handling scenarios
- Environment-specific configuration loading

### Integration Tests
- Full Swagger UI rendering with collapsed sections
- Section expansion/collapse functionality
- Search functionality across collapsed sections
- API endpoint accessibility and functionality

### Property-Based Tests
- **Minimum 100 iterations per property test**
- Each property test references its design document property
- **Tag format**: Feature: swagger-ui-collapsed-sections, Property {number}: {property_text}

**Property Test Configuration**:
- Use Symfony's WebTestCase for browser simulation
- Generate random API endpoint configurations
- Test UI state consistency across different section combinations
- Verify functional equivalence between UI states

**Testing Framework**: PHPUnit with Symfony WebTestCase for integration testing