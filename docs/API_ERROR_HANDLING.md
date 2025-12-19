# Guide de Gestion des Erreurs API

## Problème Résolu

Avant cette correction, les contrôleurs API utilisaient souvent le pattern suivant qui causait des erreurs de validation :

```php
// ❌ PROBLÉMATIQUE - Cause l'erreur "Cannot validate values of type 'null' automatically"
return $this->errorResponse(null, "Message d'erreur", 400);
```

## Solution

Utilisez maintenant la méthode `createCustomErrorResponse()` disponible dans tous les contrôleurs API :

```php
// ✅ CORRECT - Utilise la nouvelle méthode
return $this->createCustomErrorResponse("Message d'erreur", 400);
```

## Méthodes Disponibles

### `createCustomErrorResponse(string $message, int $statusCode = 400): JsonResponse`

Crée une réponse d'erreur JSON sans validation d'entité.

**Paramètres :**
- `$message` : Le message d'erreur à afficher
- `$statusCode` : Le code de statut HTTP (par défaut : 400)

**Retour :**
```json
{
    "code": 400,
    "message": "Message d'erreur",
    "errors": ["Message d'erreur"]
}
```

## Exemples d'Utilisation

### Erreurs de Validation Métier

```php
// Vérification d'unicité
if ($userRepository->findOneByInEnvironment(['login' => $email])) {
    return $this->createCustomErrorResponse("Cet email existe déjà", 400);
}

// Ressource non trouvée
if (!$entity) {
    return $this->createCustomErrorResponse("Ressource non trouvée", 404);
}

// Erreur serveur
if (!$service->isAvailable()) {
    return $this->createCustomErrorResponse("Service temporairement indisponible", 500);
}
```

### Codes de Statut Recommandés

- **400 Bad Request** : Données invalides, contraintes métier violées
- **401 Unauthorized** : Authentification requise
- **403 Forbidden** : Permissions insuffisantes
- **404 Not Found** : Ressource non trouvée
- **422 Unprocessable Entity** : Erreurs de validation de formulaire
- **500 Internal Server Error** : Erreurs serveur internes

## Migration des Contrôleurs Existants

Pour migrer un contrôleur existant :

1. **Identifier** tous les appels `errorResponse(null, ...)`
2. **Remplacer** par `createCustomErrorResponse(...)`
3. **Tester** que les réponses d'erreur fonctionnent correctement

### Exemple de Migration

```php
// Avant
if (!$pays) {
    return $this->errorResponse(null, "Pays non trouvé", 404);
}

// Après
if (!$pays) {
    return $this->createCustomErrorResponse("Pays non trouvé", 404);
}
```

## Tests

Des tests de propriétés ont été ajoutés pour vérifier que :

1. Aucune erreur de validation null n'est générée
2. La structure des réponses d'erreur est cohérente
3. Les codes de statut sont correctement appliqués

Voir `tests/Controller/ApiFixtureControllerTest.php` et `tests/Controller/ApiUserControllerTest.php` pour des exemples.

## Contrôleurs Corrigés

Les contrôleurs suivants ont été mis à jour :

- ✅ `ApiFixtureController` - Entièrement corrigé avec tests
- ✅ `ApiUserController` - Entièrement corrigé avec tests
- ⚠️ Autres contrôleurs - Méthode disponible, migration recommandée

## Bonnes Pratiques

1. **Toujours utiliser** `createCustomErrorResponse()` pour les erreurs sans validation d'entité
2. **Réserver** `errorResponse($entity)` pour la validation d'entités Doctrine
3. **Fournir des messages d'erreur** clairs et spécifiques
4. **Utiliser les codes de statut HTTP** appropriés
5. **Tester** les réponses d'erreur dans les tests unitaires