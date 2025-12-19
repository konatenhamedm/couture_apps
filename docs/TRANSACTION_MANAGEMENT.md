# Gestion Centralisée des Transactions

## Vue d'ensemble

La gestion des transactions a été centralisée dans l'`EntityManagerProvider` et le trait `DatabaseEnvironmentTrait` pour offrir une approche cohérente et sûre des opérations de base de données.

## Nouvelles Méthodes Disponibles

### EntityManagerProvider

```php
// Gestion manuelle des transactions
$entityManagerProvider->beginTransaction();
$entityManagerProvider->commit();
$entityManagerProvider->rollback();

// Opérations d'entité
$entityManagerProvider->persist($entity);
$entityManagerProvider->flush();

// Vérification de l'état des transactions
$entityManagerProvider->isTransactionActive();

// Exécution automatique avec gestion d'erreurs
$result = $entityManagerProvider->executeInTransaction(function($em) {
    // Vos opérations ici
    $em->persist($entity);
    $em->flush();
    return $entity;
});
```

### DatabaseEnvironmentTrait (disponible dans tous les contrôleurs API)

```php
// Gestion manuelle des transactions
$this->beginTransaction();
$this->commit();
$this->rollback();

// Exécution automatique avec gestion d'erreurs
$result = $this->executeInTransaction(function($em) {
    // Vos opérations ici
    return $result;
});

// Vérification de l'état des transactions
$this->isTransactionActive();
```

## Patterns d'Utilisation

### 1. Gestion Automatique (Recommandé)

```php
public function createEntity($data): Response
{
    try {
        $result = $this->executeInTransaction(function($em) use ($data) {
            $entity = new MyEntity();
            $entity->setData($data);
            
            // Validation
            if (!$this->validateEntity($entity)) {
                throw new \Exception('Validation failed');
            }
            
            $em->persist($entity);
            $em->flush();
            
            return $entity;
        });
        
        return $this->responseData(['entity' => $result]);
        
    } catch (\Exception $e) {
        return $this->createCustomErrorResponse($e->getMessage(), 500);
    }
}
```

### 2. Gestion Manuelle

```php
public function createEntityManual($data): Response
{
    $this->beginTransaction();
    
    try {
        $entity = new MyEntity();
        $entity->setData($data);
        
        if (!$this->validateEntity($entity)) {
            $this->rollback();
            return $this->createCustomErrorResponse('Validation failed', 400);
        }
        
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
        
        $this->commit();
        
        return $this->responseData(['entity' => $entity]);
        
    } catch (\Exception $e) {
        $this->rollback();
        return $this->createCustomErrorResponse($e->getMessage(), 500);
    }
}
```

### 3. Opérations Multiples

```php
public function createMultipleEntities($dataArray): Response
{
    try {
        $results = $this->executeInTransaction(function($em) use ($dataArray) {
            $entities = [];
            
            foreach ($dataArray as $data) {
                $entity = new MyEntity();
                $entity->setData($data);
                
                if (!$this->validateEntity($entity)) {
                    throw new \Exception("Validation failed for entity: " . $data['id']);
                }
                
                $em->persist($entity);
                $entities[] = $entity;
            }
            
            $em->flush();
            return $entities;
        });
        
        return $this->responseData([
            'message' => count($results) . ' entities created successfully',
            'entities' => $results
        ]);
        
    } catch (\Exception $e) {
        return $this->createCustomErrorResponse($e->getMessage(), 500);
    }
}
```

## Avantages

### 1. **Cohérence**
- Même interface pour toutes les opérations de transaction
- Gestion d'erreurs standardisée
- Respect de l'environnement de base de données (dev/prod)

### 2. **Sécurité**
- Rollback automatique en cas d'erreur
- Validation des transactions avant commit
- Gestion des exceptions centralisée

### 3. **Simplicité**
- Moins de code boilerplate
- Gestion automatique des erreurs avec `executeInTransaction`
- Interface claire et intuitive

### 4. **Maintenabilité**
- Logique centralisée dans `EntityManagerProvider`
- Facilite les modifications futures
- Tests unitaires centralisés

## Exemples Pratiques

### Création de Fixtures avec Transactions

```php
// Avant (problématique)
$entityManager->beginTransaction();
try {
    $entity = new Entity();
    $entityManager->persist($entity);
    $entityManager->flush();
    $entityManager->commit();
} catch (\Exception $e) {
    $entityManager->rollback();
    throw $e;
}

// Après (avec gestion centralisée)
$result = $this->executeInTransaction(function($em) {
    $entity = new Entity();
    $em->persist($entity);
    $em->flush();
    return $entity;
});
```

### Validation et Persistence

```php
public function createWithValidation($data): Response
{
    try {
        $entity = $this->executeInTransaction(function($em) use ($data) {
            $entity = new MyEntity();
            $entity->setData($data);
            
            // Validation Symfony
            $errors = $this->validator->validate($entity);
            if (count($errors) > 0) {
                throw new \Exception('Validation errors: ' . (string) $errors);
            }
            
            // Validation métier
            if (!$this->businessValidation($entity)) {
                throw new \Exception('Business validation failed');
            }
            
            $em->persist($entity);
            $em->flush();
            
            return $entity;
        });
        
        return $this->responseData(['entity' => $entity]);
        
    } catch (\Exception $e) {
        return $this->createCustomErrorResponse($e->getMessage(), 400);
    }
}
```

## Migration des Contrôleurs Existants

### Étapes de Migration

1. **Identifier** les blocs `beginTransaction()` / `commit()` / `rollback()` manuels
2. **Remplacer** par `executeInTransaction()` quand possible
3. **Tester** que les opérations fonctionnent correctement
4. **Simplifier** le code en supprimant la gestion manuelle des erreurs

### Exemple de Migration

```php
// Avant
public function oldMethod(): Response
{
    $em = $this->getEntityManager();
    $em->beginTransaction();
    
    try {
        $entity = new Entity();
        $em->persist($entity);
        $em->flush();
        $em->commit();
        
        return $this->responseData(['entity' => $entity]);
    } catch (\Exception $e) {
        $em->rollback();
        return $this->createCustomErrorResponse($e->getMessage(), 500);
    }
}

// Après
public function newMethod(): Response
{
    try {
        $entity = $this->executeInTransaction(function($em) {
            $entity = new Entity();
            $em->persist($entity);
            $em->flush();
            return $entity;
        });
        
        return $this->responseData(['entity' => $entity]);
    } catch (\Exception $e) {
        return $this->createCustomErrorResponse($e->getMessage(), 500);
    }
}
```

## Tests

Des tests complets ont été ajoutés pour vérifier :

1. **Délégation correcte** des opérations vers l'EntityManager
2. **Gestion d'erreurs** avec rollback automatique
3. **Cohérence** des transactions dans différents scénarios
4. **Property-based testing** pour la robustesse

Voir `tests/Service/EntityManagerProviderTest.php` pour les détails.

## Bonnes Pratiques

1. **Préférer `executeInTransaction()`** pour la plupart des cas
2. **Utiliser la gestion manuelle** seulement pour des cas complexes
3. **Toujours valider** les entités avant persistence
4. **Gérer les exceptions** de manière appropriée
5. **Tester** les opérations de transaction dans les tests unitaires

## Contrôleurs Mis à Jour

- ✅ `EntityManagerProvider` - Nouvelles méthodes de transaction
- ✅ `DatabaseEnvironmentTrait` - Méthodes disponibles dans tous les contrôleurs
- ✅ `ApiFixtureControllerSimplified` - Exemple d'utilisation
- ✅ Tests complets avec property-based testing

Cette approche centralisée améliore la robustesse, la maintenabilité et la cohérence de la gestion des transactions dans toute l'application ! 🎉