# ✅ Configuration des Environnements - Résumé

## 🎯 Configuration Complétée

Votre application Symfony est maintenant configurée avec deux environnements distincts :

### 📁 Fichiers Créés/Modifiés

1. **`.env`** - Configuration par défaut (DEV)
2. **`.env.dev`** - Configuration spécifique DEV
   - Base de données : `app_couture_dev`
3. **`.env.prod`** - Configuration spécifique PROD
   - Base de données : `app_couture_prod`
4. **`.env.local`** - Surcharges locales (non commité)
5. **`bin/switch-env.sh`** - Script de basculement (Linux/Mac)
6. **`bin/switch-env.bat`** - Script de basculement (Windows)
7. **`README_ENVIRONNEMENTS.md`** - Guide complet

### 🗄️ Bases de Données Créées

- ✅ `app_couture_dev` - Base de développement
- ✅ `app_couture_prod` - Base de production

## 🚀 Utilisation Rapide

### Basculer en DEV (Linux/Mac)
```bash
./bin/switch-env.sh dev
```

### Basculer en PROD (Linux/Mac)
```bash
./bin/switch-env.sh prod
```

### Basculer en DEV (Windows)
```cmd
bin\switch-env.bat dev
```

### Basculer en PROD (Windows)
```cmd
bin\switch-env.bat prod
```

## 🔍 Vérifier l'Environnement Actuel

```bash
php bin/console about
```

## 📝 Commandes Utiles

### Démarrer le serveur en DEV
```bash
symfony server:start
# ou
php -S localhost:8000 -t public/
```

### Démarrer le serveur en PROD
```bash
APP_ENV=prod symfony server:start
# ou
APP_ENV=prod php -S localhost:8000 -t public/
```

### Vider le cache
```bash
# DEV
php bin/console cache:clear

# PROD
php bin/console cache:clear --env=prod
```

## ⚠️ Important

1. **Ne jamais commiter** `.env.local` dans Git (déjà dans .gitignore)
2. **Toujours tester** en DEV avant de déployer en PROD
3. **Sauvegarder** la base PROD avant toute modification
4. Les **migrations** doivent être exécutées sur les deux environnements

## 🔧 Prochaines Étapes

1. Corriger les problèmes de migration si nécessaire
2. Configurer les credentials de production dans `.env.prod`
3. Tester l'application dans les deux environnements
4. Configurer le déploiement automatique si souhaité

# Mettre à jour la base DEV
php bin/console d:s:u --force --em=dev

# Mettre à jour la base PROD
php bin/console d:s:u --force --em=prod

# Mettre à jour la base DEFAULT
php bin/console d:s:u --force --em=default

## 📞 Support

Pour toute question, consultez :
- `README_ENVIRONNEMENTS.md` - Guide détaillé
- [Documentation Symfony](https://symfony.com/doc/current/configuration.html)
- [Documentation Doctrine](https://www.doctrine-project.org/)