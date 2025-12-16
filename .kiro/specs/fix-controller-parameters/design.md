# Design Document - Fix Controller Parameters

## Overview

Ce document décrit la conception d'un système automatisé pour corriger les erreurs de paramètres manquants dans les contrôleurs API. Le système identifiera et corrigera automatiquement les méthodes qui utilisent `$request` sans l'avoir déclaré comme paramètre, ainsi que les imports manquants.

## Architecture

Le système sera composé de trois composants principaux :

1. **Scanner** : Analyse les fichiers de contrôleurs pour identifier les problèmes
2. **Fixer** : Applique les corrections automatiquement
3. **Validator** : Vérifie que les corrections n'introduisent pas de nouveaux problèmes

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│   Scanner   │───▶│    Fixer    │───▶│  Validator  │
└─────────────┘    └─────────────┘    └─────────────┘
       │                   │                   │
       ▼                   ▼                   ▼
  Identify Issues    Apply Corrections   Verify Results
```

## Components and Interfaces

### Scanner Component

**Responsabilités :**
- Analyser les fichiers PHP des contrôleurs
- Identifier les méthodes utilisant `$request` sans paramètre
- Détecter les imports manquants
- Générer un rapport des problèmes trouvés

**Interface :**
```php
interface ControllerScannerInterface
{
    public function scanController(string $filePath): array;
    public function findMissingRequestParameters(string $content): array;
    public function findMissingImports(string $content): array;
}
```

### Fixer Component

**Responsabilités :**
- Corriger les signatures de méthodes
- Ajouter les imports manquants
- Préserver le code existant
- Maintenir le formatage du code

**Interface :**
```php
interface ControllerFixerInterface
{
    public function fixMethodSignature(string $content, string $methodName): string;
    public function addMissingImports(string $content, array $imports): string;
    public function fixController(string $filePath, array $issues): bool;
}
```

### Validator Component

**Responsabilités :**
- Vérifier la syntaxe PHP après corrections
- Valider que les imports sont corrects
- S'assurer qu'aucune nouvelle erreur n'est introduite

**Interface :**
```php
interface ControllerValidatorInterface
{
    public function validateSyntax(string $filePath): bool;
    public function validateImports(string $content): bool;
    public function validateMethodSignatures(string $content): bool;
}
```

## Data Models

### Issue Model
```php
class ControllerIssue
{
    private string $filePath;
    private string $issueType; // 'missing_request_param', 'missing_import'
    private string $methodName;
    private int $lineNumber;
    private string $description;
    private array $suggestedFix;
}
```

### Fix Report Model
```php
class FixReport
{
    private string $filePath;
    private array $appliedFixes;
    private bool $success;
    private array $errors;
    private string $backupPath;
}
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property Reflection

Après analyse des propriétés identifiées dans le prework, j'ai identifié les redondances suivantes :
- Les propriétés 1.2 et 2.1 sont identiques (import de Request)
- Les propriétés 2.2 et 2.3 peuvent être combinées en une propriété générale sur les imports
- Les propriétés 3.1, 3.2, 3.3 peuvent être combinées en une propriété générale sur la capacité de scanning

Voici les propriétés finales après élimination des redondances :

**Property 1: Request parameter consistency**
*For any* controller method that uses `$request` variable, the method signature should include `Request $request` as a parameter
**Validates: Requirements 1.1**

**Property 2: Import completeness**
*For any* controller file that uses a class, the file should contain the corresponding `use` import statement for that class
**Validates: Requirements 1.2, 2.1, 2.2, 2.3**

**Property 3: Dependency injection consistency**
*For any* controller method that uses service objects, all required services should be properly injected as method parameters
**Validates: Requirements 1.3**

**Property 4: Import preservation**
*For any* controller file, when new imports are added, existing imports should remain unchanged unless they are duplicates
**Validates: Requirements 2.4**

**Property 5: Comprehensive scanning**
*For any* controller file with issues, the scanner should identify all problems of the specified types (missing parameters, missing imports, missing injections)
**Validates: Requirements 3.1, 3.2, 3.3, 3.4**

**Property 6: Fix validation**
*For any* controller file after fixes are applied, the file should have valid PHP syntax and no undefined variable errors
**Validates: Requirements 3.5, 1.5, 2.5**

**Property 7: Route preservation**
*For any* controller method, when the method signature is updated, the route attributes should remain exactly the same
**Validates: Requirements 4.1**

**Property 8: Method body preservation**
*For any* controller method, when parameters are added to the signature, the method body content should remain unchanged
**Validates: Requirements 4.2**

## Error Handling

Le système gérera les erreurs suivantes :

1. **Erreurs de syntaxe** : Validation avant et après modifications
2. **Fichiers non trouvés** : Vérification d'existence des fichiers
3. **Permissions insuffisantes** : Gestion des droits d'écriture
4. **Conflits d'imports** : Détection des imports en double
5. **Méthodes complexes** : Gestion des signatures avec multiples paramètres

## Testing Strategy

### Unit Testing
- Tests pour chaque composant (Scanner, Fixer, Validator)
- Tests des cas d'erreur et des cas limites
- Tests de validation des expressions régulières
- Tests de préservation du code existant

### Property-Based Testing
- Utilisation de **PHPUnit** avec **Eris** pour les tests basés sur les propriétés
- Chaque test de propriété exécutera un minimum de 100 itérations
- Tests avec génération automatique de code PHP valide et invalide
- Validation des propriétés sur des échantillons aléatoires de code

**Exigences pour les tests basés sur les propriétés :**
- Chaque propriété de correction doit être implémentée par UN SEUL test basé sur les propriétés
- Chaque test doit être tagué avec un commentaire référençant explicitement la propriété de correction correspondante
- Format de tag requis : `**Feature: fix-controller-parameters, Property {number}: {property_text}**`
- Configuration minimale de 100 itérations par test de propriété

Les tests basés sur les propriétés et les tests unitaires sont complémentaires : les tests unitaires vérifient des exemples spécifiques et des cas limites, les tests de propriétés vérifient la correction universelle sur tous les inputs.