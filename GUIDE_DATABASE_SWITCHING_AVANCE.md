# Guide Avancé - Système de Switching de Base de Données

## Vue d'ensemble

Le système de switching de base de données a été amélioré avec deux nouvelles fonctionnalités :

1. **Méthode `findOneBy` dans `DatabaseEnvironmentTrait`**
2. **Repositories adaptés avec `BaseRepository`**

## 1. Nouvelle méthode `findOneBy` dans le Trait

### Utilisation dans les contrôleurs

```php
// Dans un contrôleur API
class ApiCategorieMesureController extends ApiInterface
{
    public function findByCode(string $code): Response
    {
        // Utilise automatiquement le bon environnement (dev/prod)
        $categorie = $this->findOneBy(CategorieMesure::class, ['code' => $code]);
        
        if ($categorie) {
            return $this->responseData($categorie, 'group1');
        }
        
        return $this->response(null, 404);
    }
}
```

### Méthodes disponibles dans le trait

```php
// Toutes ces méthodes utilisent automatiquement le bon environnement
$this->findAll(Entity::class);
$this->findBy(Entity::class, ['field' => 'value'], ['field' => 'ASC']);
$this->findOneBy(Entity::class, ['field' => 'value']); // NOUVEAU
$this->find(Entity::class, $id);
$this->count(Entity::class, ['field' => 'value']);
$this->save($entity);
$this->remove($entity);
```

## 2. Repositories Adaptés avec BaseRepository

### Structure

```
src/Repository/
├── BaseRepository.php          # Repository de base avec switching automatique
├── CategorieMesureRepository.php # Exemple adapté
└── PaysRepository.php          # Exemple adapté
```

### Création d'un Repository Adapté

```php
<?php

namespace App\Repository;

use App\Entity\MonEntite;
use App\Service\EntityManagerProvider;
use Doctrine\Persistence\ManagerRegistry;

class MonEntiteRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry, EntityManagerProvider $entityManagerProvider)
    {
        parent::__construct($registry, MonEntite::class, $entityManagerProvider);
    }

    // Méthodes personnalisées qui utilisent automatiquement le bon environnement
    public function findActiveEntities(): array
    {
        return $this->findByInEnvironment(['isActive' => true], ['name' => 'ASC']);
    }

    public function findByCode(string $code): ?MonEntite
    {
        return $this->findOneByInEnvironment(['code' => $code]);
    }
}
```

### Méthodes disponibles dans BaseRepository

```php
// Méthodes de base (équivalentes au trait)
$repository->findAllInEnvironment();
$repository->findByInEnvironment(['field' => 'value']);
$repository->findOneByInEnvironment(['field' => 'value']);
$repository->findInEnvironment($id);
$repository->countInEnvironment(['field' => 'value']);
$repository->saveInEnvironment($entity);
$repository->removeInEnvironment($entity);

// Méthodes avancées
$repository->createQueryBuilderForEnvironment('alias');
```

### Utilisation dans les contrôleurs

```php
class ApiPaysController extends ApiInterface
{
    // Injection du repository adapté
    public function searchByCode(string $code, PaysRepository $paysRepository): Response
    {
        // Le repository utilise automatiquement le bon environnement
        $pays = $paysRepository->findByCode($code);
        
        return $this->responseData($pays, 'group1');
    }
}
```

## 3. Configuration des Services

### Fichier `config/services_repositories.yaml`

```yaml
services:
    # Repository de base
    App\Repository\BaseRepository:
        abstract: true
        arguments:
            $registry: '@doctrine'
            $entityManagerProvider: '@App\Service\EntityManagerProvider'

    # Repositories spécifiques
    App\Repository\MonEntiteRepository:
        parent: App\Repository\BaseRepository
        arguments:
            $entityClass: 'App\Entity\MonEntite'
        tags: ['doctrine.repository_service']
```

## 4. Quand utiliser quoi ?

### Utilisez le Trait (`DatabaseEnvironmentTrait`) quand :
- ✅ Opérations CRUD simples
- ✅ Pas besoin de logique métier complexe
- ✅ Requêtes directes et rapides
- ✅ Contrôleurs légers

```php
// Simple et direct
$users = $this->findBy(User::class, ['isActive' => true]);
$user = $this->findOneBy(User::class, ['email' => $email]);
```

### Utilisez les Repositories Adaptés quand :
- ✅ Logique métier complexe
- ✅ Requêtes personnalisées avec QueryBuilder
- ✅ Méthodes réutilisables
- ✅ Tests unitaires spécifiques

```php
// Logique métier dans le repository
$activeUsers = $userRepository->findActiveUsersWithProjects();
$stats = $userRepository->getUserStatistics($dateRange);
```

## 5. Exemples Pratiques

### Exemple 1 : Contrôleur avec Trait uniquement

```php
class ApiSimpleController extends ApiInterface
{
    public function index(): Response
    {
        $items = $this->findAll(Item::class);
        return $this->responseData($items, 'group1');
    }

    public function show(int $id): Response
    {
        $item = $this->find(Item::class, $id);
        return $this->responseData($item, 'group1');
    }
}
```

### Exemple 2 : Contrôleur avec Repository Adapté

```php
class ApiAdvancedController extends ApiInterface
{
    public function getStatistics(ItemRepository $itemRepository): Response
    {
        $stats = $itemRepository->getMonthlyStatistics();
        return $this->responseData($stats, 'group1');
    }

    public function searchComplex(Request $request, ItemRepository $itemRepository): Response
    {
        $criteria = json_decode($request->getContent(), true);
        $results = $itemRepository->complexSearch($criteria);
        return $this->responseData($results, 'group1');
    }
}
```

### Exemple 3 : Utilisation Mixte

```php
class ApiMixedController extends ApiInterface
{
    public function simpleList(): Response
    {
        // Utilise le trait pour les opérations simples
        $items = $this->findBy(Item::class, ['status' => 'active']);
        return $this->responseData($items, 'group1');
    }

    public function complexAnalysis(ItemRepository $itemRepository): Response
    {
        // Utilise le repository pour la logique complexe
        $analysis = $itemRepository->performComplexAnalysis();
        return $this->responseData($analysis, 'group1');
    }
}
```

## 6. Tests

### Test avec différents environnements

```bash
# Test en développement
curl "http://localhost:8000/api/pays/search/code/CI?env=dev"

# Test en production
curl "http://localhost:8000/api/pays/search/code/CI?env=prod"

# Test avec header
curl -H "X-Database-Env: dev" "http://localhost:8000/api/pays/active/repository"
```

## 7. Avantages

### Trait `DatabaseEnvironmentTrait`
- ✅ Simple et rapide à utiliser
- ✅ Pas de configuration supplémentaire
- ✅ Idéal pour les opérations CRUD basiques

### Repositories Adaptés
- ✅ Logique métier centralisée
- ✅ Méthodes réutilisables
- ✅ Tests unitaires plus faciles
- ✅ Séparation des responsabilités
- ✅ QueryBuilder avancé

### Les deux approches
- ✅ Switching automatique dev/prod
- ✅ Pas de modification du code existant
- ✅ Session persistence
- ✅ Support des headers HTTP

## 8. Migration Progressive

Vous pouvez migrer progressivement :

1. **Étape 1** : Utilisez le trait pour tous les nouveaux contrôleurs
2. **Étape 2** : Créez des repositories adaptés pour la logique complexe
3. **Étape 3** : Migrez les anciens contrôleurs selon les besoins

Le système est rétrocompatible et fonctionne avec l'approche existante !