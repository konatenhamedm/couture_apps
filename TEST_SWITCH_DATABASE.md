# 🧪 Test du Switch Dynamique de Base de Données

## Étapes de test

### 1. Vider le cache
```bash
php bin/console cache:clear
```

### 2. Tester l'endpoint de diagnostic

#### Test avec base PROD (par défaut)
```bash
curl "http://localhost:8000/api/test/database-info"
```

Réponse attendue:
```json
{
    "environment": "prod",
    "database": "niqj4716_ateliya_prod",
    "host": "127.0.0.1",
    "message": "Vous êtes connecté à la base de données: niqj4716_ateliya_prod"
}
```

#### Test avec base DEV
```bash
curl "http://localhost:8000/api/test/database-info?env=dev"
```

Réponse attendue:
```json
{
    "environment": "dev",
    "database": "niqj4716_ateliya_dev",
    "host": "127.0.0.1",
    "message": "Vous êtes connecté à la base de données: niqj4716_ateliya_dev"
}
```

#### Test avec base PROD explicite
```bash
curl "http://localhost:8000/api/test/database-info?env=prod"
```

### 3. Tester avec votre API existante

```bash
# Test avec DEV
curl "http://localhost:8000/api/accueil/1/yyy?env=dev"

# Test avec PROD
curl "http://localhost:8000/api/accueil/1/yyy?env=prod"
```

### 4. Tester avec Postman/Insomnia

1. **Méthode 1 - Query Parameter**
   - URL: `http://localhost:8000/api/accueil/1/yyy`
   - Ajouter un paramètre: `env` = `dev` ou `prod`

2. **Méthode 2 - Header**
   - URL: `http://localhost:8000/api/accueil/1/yyy`
   - Ajouter un header: `X-Database-Env: dev` ou `X-Database-Env: prod`

## Vérification dans les logs

Vous pouvez ajouter des logs pour voir quelle base est utilisée:

```php
// Dans votre contrôleur
$env = $dynamicDb->getCurrentEnvironment();
error_log("Using database environment: " . $env);
```

## Dépannage

### Le paramètre ?env= ne fonctionne pas

1. Vérifiez que le cache est vidé:
   ```bash
   php bin/console cache:clear
   ```

2. Vérifiez les logs d'erreur PHP

3. Testez l'endpoint de diagnostic:
   ```bash
   curl "http://localhost:8000/api/test/database-info?env=dev"
   ```

### Les données ne changent pas

1. Assurez-vous que les deux bases ont des données différentes
2. Vérifiez que le trait `DynamicDatabaseTrait` est utilisé dans vos repositories
3. Videz la session du navigateur

## Supprimer l'endpoint de test en production

Une fois les tests terminés, supprimez ou sécurisez le contrôleur de test:

```bash
rm src/Controller/Apis/ApiDatabaseTestController.php
```