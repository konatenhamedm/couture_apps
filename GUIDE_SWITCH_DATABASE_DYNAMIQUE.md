# 🔄 Guide - Switch Dynamique de Base de Données

## 📋 Vue d'ensemble

Votre application Symfony peut maintenant basculer dynamiquement entre deux bases de données (dev et prod) en fonction d'un paramètre dans l'URL.

## 🎯 Fonctionnement

### Bases de données configurées
- **DEV**: `app_couture_dev`
- **PROD**: `app_couture_prod` (par défaut)

### Comment basculer

#### Via paramètre URL
```
# Utiliser la base de données DEV
https://votre-api.com/api/endpoint?env=dev

# Utiliser la base de données PROD (par défaut)
https://votre-api.com/api/endpoint?env=prod
https://votre-api.com/api/endpoint
```

#### Via Header HTTP
```bash
curl -H "X-Database-Env: dev" https://votre-api.com/api/endpoint
```

## 🔧 Exemples d'utilisation

### Exemple 1: Récupérer les données d'accueil en DEV
```
GET /api/accueil/1/yyy?env=dev
```

### Exemple 2: Récupérer les données d'accueil en PROD
```
GET /api/accueil/1/yyy?env=prod
# ou simplement
GET /api/accueil/1/yyy
```

### Exemple 3: Avec Postman/Insomnia
1. Ajoutez le paramètre de requête `env` avec la valeur `dev` ou `prod`
2. OU ajoutez un header `X-Database-Env` avec la valeur `dev` ou `prod`

## 💾 Persistance de la session

Une fois que vous avez spécifié `?env=dev`, toutes les requêtes suivantes dans la même session utiliseront automatiquement la base DEV jusqu'à ce que vous changiez explicitement avec `?env=prod`.

## 🛠️ Utilisation dans le code

### Dans un contrôleur

```php
use App\Service\DatabaseEnvironmentService;

class MonController extends AbstractController
{
    public function maMethode(DatabaseEnvironmentService $dbEnv)
    {
        // Vérifier l'environnement actuel
        $env = $dbEnv->getCurrentEnvironment(); // 'dev' ou 'prod'
        
        // Vérifier si on est en dev
        if ($dbEnv->isDev()) {
            // Logique spécifique dev
        }
        
        // Obtenir l'EntityManager approprié
        $em = $dbEnv->getEntityManager();
        
        // Obtenir la connexion appropriée
        $connection = $dbEnv->getConnection();
    }
}
```

### Dans un Repository

Les repositories utilisent automatiquement la bonne connexion grâce au listener.

```php
public function maMethode(FactureRepository $factureRepository)
{
    // Cette requête utilisera automatiquement la bonne base de données
    $factures = $factureRepository->findAll();
}
```

## ⚙️ Configuration technique

### Fichiers modifiés/créés

1. **`config/packages/doctrine.yaml`**
   - Ajout de 3 connexions: default, dev, prod
   - Ajout de 3 entity managers correspondants

2. **`config/services.yaml`**
   - Ajout des paramètres `database.dev.url` et `database.prod.url`
   - Configuration du `DatabaseSwitchListener`

3. **`src/EventListener/DatabaseSwitchListener.php`**
   - Listener qui détecte le paramètre `env` et bascule la connexion

4. **`src/Service/DatabaseEnvironmentService.php`**
   - Service helper pour accéder facilement à l'environnement actuel

## 🔒 Sécurité

### En production

Pour sécuriser l'accès à la base DEV en production, vous pouvez:

1. **Ajouter une vérification d'IP**
```php
// Dans DatabaseSwitchListener.php
if ($env === 'dev') {
    $allowedIps = ['127.0.0.1', '::1', 'votre-ip'];
    if (!in_array($request->getClientIp(), $allowedIps)) {
        return; // Ignorer la demande de switch
    }
}
```

2. **Ajouter une authentification**
```php
if ($env === 'dev') {
    $token = $request->headers->get('X-Dev-Token');
    if ($token !== 'votre-token-secret') {
        return;
    }
}
```

## 🧪 Tests

### Tester le switch

```bash
# Test en DEV
curl "http://localhost:8000/api/accueil/1/yyy?env=dev"

# Test en PROD
curl "http://localhost:8000/api/accueil/1/yyy?env=prod"

# Vérifier quelle base est utilisée
# Regardez les logs ou ajoutez un endpoint de debug
```

## 📊 Monitoring

Pour voir quelle base de données est utilisée, vous pouvez ajouter un header de réponse:

```php
// Dans ApiInterface ou un EventListener
$response->headers->set('X-Database-Used', $dbEnv->getCurrentEnvironment());
```

## ⚠️ Points importants

1. **Par défaut = PROD**: Si aucun paramètre n'est fourni, c'est la base PROD qui est utilisée
2. **Session**: L'environnement est stocké en session pour éviter de le passer à chaque requête
3. **Performance**: Le switch est fait au niveau de la requête, pas de surcharge significative
4. **Migrations**: Les migrations doivent être exécutées sur les deux bases séparément

## 🚀 Déploiement

En production, assurez-vous que:
1. Les deux bases de données existent
2. Les credentials sont corrects dans `config/services.yaml`
3. Les migrations sont à jour sur les deux bases
4. L'accès à la base DEV est sécurisé (voir section Sécurité)

## 🆘 Dépannage

### La base ne change pas
- Vérifiez que le paramètre `env` est bien passé
- Videz le cache: `php bin/console cache:clear`
- Vérifiez les logs Symfony

### Erreur de connexion
- Vérifiez que les deux bases existent
- Vérifiez les credentials dans `config/services.yaml`
- Testez la connexion manuellement

### Données incohérentes
- Assurez-vous que les migrations sont à jour sur les deux bases
- Vérifiez que les schémas sont identiques