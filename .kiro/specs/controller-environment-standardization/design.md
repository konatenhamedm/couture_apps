# Design Document

## Overview

Ce document décrit la conception pour standardiser l'utilisation de la méthode `findInEnvironment($id)` dans tous les contrôleurs API. L'objectif est de remplacer systématiquement les méthodes de récupération d'entités standard par la méthode respectueuse de l'environnement, garantissant ainsi l'intégrité des données dans un contexte multi-environnement.

## Architecture

### Pattern Actuel Problématique
```php
// ❌ Méthode non-respectueuse de l'environnement
$entity = $repository->find($id);
$entity = $repository->findOneBy(['id' => $id]);
```

### Pattern Cible Standardisé
```php
// ✅ Méthode respectueuse de l'environnement
$entity = $repository->findInEnvironment($id);
```

### Scope d'Application
- **Contrôleurs concernés**: Tous les contrôleurs dans `src/Controller/Apis/`
- **Méthodes concernées**: Toutes les méthodes avec paramètre `{id}` dans l'URL
- **Operations concernées**: GET, PUT, POST, DELETE avec ID

## Components and Interfaces

### 1. Controller Analysis Component
Responsable de l'analyse des contrôleurs existants pour identifier les patterns à modifier.

**Fonctionnalités:**
- Scanner tous les fichiers dans `src/Controller/Apis/`
- Identifier les méthodes avec paramètres `{id}` dans les routes
- Détecter l'usage de `find($id)` et `findOneBy(['id' => $id])`
- Générer un rapport des modifications nécessaires

### 2. Code Transformation Component
Responsable de la transformation du code pour appliquer le nouveau pattern.

**Fonctionnalités:**
- Remplacer `$repository->find($id)` par `$repository->findInEnvironment($id)`
- Remplacer `$repository->findOneBy(['id' => $id])` par `$repository->findInEnvironment($id)`
- Préserver la logique de gestion d'erreur existante
- Maintenir les noms de variables et la structure du code

### 3. Validation Component
Responsable de la validation que tous les contrôleurs respectent le nouveau standard.

**Fonctionnalités:**
- Vérifier l'absence de patterns non-conformes
- Valider que tous les `{id}` utilisent `findInEnvironment`
- Générer un rapport de conformité

## Data Models

### Controller Method Pattern
```php
class ControllerMethodPattern {
    public string $controllerName;
    public string $methodName;
    public string $routePattern;
    public string $currentPattern;
    public string $targetPattern;
    public bool $needsUpdate;
}
```

### Transformation Result
```php
class TransformationResult {
    public string $filePath;
    public array $modifiedMethods;
    public bool $success;
    public array $errors;
}
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property Reflection

Après analyse des critères d'acceptation, plusieurs propriétés peuvent être consolidées :
- Les propriétés 1.3, 1.4, 1.5 peuvent être combinées en une seule propriété sur l'usage uniforme dans toutes les opérations
- Les propriétés 2.1, 2.2, 2.3 peuvent être regroupées en une propriété de détection complète
- Les propriétés 3.1, 3.2 peuvent être combinées en une propriété de transformation
- Les propriétés 4.1, 4.2, 4.4 peuvent être regroupées en une propriété de validation globale

### Property 1: Environment Method Usage Uniformity
*For any* Controller_API method that receives a URL_ID_Parameter, the method should use `findInEnvironment($id)` and not use any Repository_Standard_Method
**Validates: Requirements 1.1, 1.2, 1.3, 1.4, 1.5**

### Property 2: Pattern Detection Completeness
*For any* Controller_API file, the analysis should detect all instances of `find($id)` and `findOneBy(['id' => $id])` patterns that need conversion
**Validates: Requirements 2.1, 2.2, 2.3, 2.4**

### Property 3: Code Transformation Correctness
*For any* code transformation, replacing Repository_Standard_Method with Environment_Method should preserve all existing error handling, null checks, variable names, and flow structure
**Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5**

### Property 4: Validation Completeness
*For any* set of Controller_API files after transformation, validation should confirm that all URL_ID_Parameter usage implements Environment_Method and no Repository_Standard_Method remains
**Validates: Requirements 4.1, 4.2, 4.4**

## Error Handling

### Pattern Preservation
La transformation doit préserver tous les patterns de gestion d'erreur existants :

```php
// Pattern existant à préserver
try {
    $entity = $repository->findInEnvironment($id); // Remplacé mais logique préservée
    
    if ($entity) {
        // Logique existante préservée
        $response = $this->response($entity);
    } else {
        $this->setMessage('Cette ressource est inexistante');
        $this->setStatusCode(404);
        $response = $this->response(null);
    }
} catch (\Exception $exception) {
    $this->setStatusCode(500);
    $this->setMessage($exception->getMessage());
    $response = $this->response([]);
}
```

### Error Cases
- **Entity Not Found**: Maintenir les réponses 404 existantes
- **Database Errors**: Préserver les try-catch et réponses 500
- **Invalid ID**: Conserver la validation des paramètres

## Testing Strategy

### Unit Testing
- Tests pour vérifier la détection correcte des patterns
- Tests pour valider les transformations de code
- Tests pour confirmer la préservation de la logique existante

### Property-Based Testing
Utilisation de **PHPUnit** avec des générateurs personnalisés pour les tests de propriétés.

**Configuration**: Chaque test de propriété exécutera un minimum de 100 itérations.

**Générateurs**:
- Générateur de code PHP avec différents patterns de repository
- Générateur de structures de contrôleurs avec diverses méthodes
- Générateur de patterns d'URL avec paramètres ID

**Tests de propriétés**:
- Propriété 1: Vérifier l'usage uniforme de `findInEnvironment`
- Propriété 2: Valider la détection complète des patterns
- Propriété 3: Confirmer la correction des transformations
- Propriété 4: Assurer la validation complète

### Integration Testing
- Tests sur de vrais fichiers de contrôleurs
- Validation que les transformations n'introduisent pas d'erreurs syntaxiques
- Vérification que les contrôleurs transformés fonctionnent correctement