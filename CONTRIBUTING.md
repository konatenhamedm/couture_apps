# Guide de Contribution - Ateliya

Merci de votre intérêt pour contribuer à Ateliya ! Ce guide vous aidera à comprendre comment participer au développement de notre plateforme.

## 📋 Table des matières

- [Code de conduite](#code-de-conduite)
- [Comment contribuer](#comment-contribuer)
- [Standards de développement](#standards-de-développement)
- [Processus de Pull Request](#processus-de-pull-request)
- [Signalement de bugs](#signalement-de-bugs)
- [Suggestions de fonctionnalités](#suggestions-de-fonctionnalités)

## Code de conduite

En participant à ce projet, vous acceptez de respecter notre code de conduite :

- Soyez respectueux et inclusif
- Acceptez les critiques constructives
- Concentrez-vous sur ce qui est le mieux pour la communauté
- Montrez de l'empathie envers les autres membres

## Comment contribuer

### Types de contributions

Nous accueillons plusieurs types de contributions :

- 🐛 **Correction de bugs**
- ✨ **Nouvelles fonctionnalités**
- 📚 **Amélioration de la documentation**
- 🧪 **Tests**
- 🎨 **Améliorations UI/UX**
- 🔧 **Optimisations de performance**

### Avant de commencer

1. Consultez les [issues existantes](https://github.com/votre-repo/ateliya/issues)
2. Créez une issue pour discuter des changements majeurs
3. Fork le repository
4. Créez une branche pour votre contribution

## Standards de développement

### Environnement de développement

```bash
# Cloner votre fork
git clone https://github.com/votre-username/ateliya.git
cd ateliya

# Installer les dépendances
composer install

# Configurer l'environnement
cp .env .env.local
# Modifier .env.local avec vos paramètres

# Créer la base de données
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### Standards de code

#### PHP

- Suivez les standards **PSR-12**
- Utilisez **PHPStan** niveau 8 minimum
- Documentez vos méthodes avec **PHPDoc**
- Respectez les conventions de nommage Symfony

```php
<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Service de gestion des mesures client
 */
class MesureService
{
    /**
     * Calcule la taille recommandée basée sur les mesures
     *
     * @param array<string, float> $mesures
     * @return string
     */
    public function calculerTailleRecommandee(array $mesures): string
    {
        // Implémentation...
    }
}
```

#### Base de données

- Utilisez les **migrations Doctrine**
- Nommez les entités en français (ex: `Client`, `Mesure`)
- Utilisez des relations appropriées
- Indexez les colonnes fréquemment utilisées

#### API

- Respectez les principes **REST**
- Utilisez les codes de statut HTTP appropriés
- Documentez avec **OpenAPI/Swagger**
- Validez toutes les entrées

```php
/**
 * @OA\Post(
 *     path="/api/clients",
 *     summary="Créer un nouveau client",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(ref="#/components/schemas/ClientInput")
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Client créé avec succès"
 *     )
 * )
 */
public function create(Request $request): JsonResponse
{
    // Implémentation...
}
```

### Tests

#### Tests unitaires

```php
<?php

namespace App\Tests\Service;

use App\Service\MesureService;
use PHPUnit\Framework\TestCase;

class MesureServiceTest extends TestCase
{
    public function testCalculerTailleRecommandee(): void
    {
        $service = new MesureService();
        $mesures = ['tour_poitrine' => 90, 'tour_taille' => 75];
        
        $taille = $service->calculerTailleRecommandee($mesures);
        
        $this->assertEquals('M', $taille);
    }
}
```

#### Tests d'intégration

```php
<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ClientControllerTest extends WebTestCase
{
    public function testCreateClient(): void
    {
        $client = static::createClient();
        
        $client->request('POST', '/api/clients', [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->getValidToken(),
        ], json_encode([
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'email' => 'jean.dupont@example.com'
        ]));
        
        $this->assertResponseStatusCodeSame(201);
    }
}
```

### Sécurité

- **Validez** toutes les entrées utilisateur
- Utilisez les **ParamConverter** Symfony
- Implémentez la **limitation de débit**
- Chiffrez les données sensibles
- Utilisez **HTTPS** en production

## Processus de Pull Request

### 1. Préparation

```bash
# Créer une branche feature
git checkout -b feature/nouvelle-fonctionnalite

# Ou une branche bugfix
git checkout -b bugfix/correction-bug-123
```

### 2. Développement

- Écrivez du code propre et testé
- Committez régulièrement avec des messages clairs
- Respectez les conventions de commit

```bash
# Exemples de messages de commit
git commit -m "feat: ajouter système de notifications push"
git commit -m "fix: corriger calcul des mesures pour les enfants"
git commit -m "docs: mettre à jour la documentation API"
```

### 3. Tests

```bash
# Lancer tous les tests
composer test

# Tests unitaires seulement
./vendor/bin/phpunit

# Analyse statique
./vendor/bin/phpstan analyse

# Style de code
./vendor/bin/php-cs-fixer fix --dry-run
```

### 4. Soumission

1. **Push** votre branche
2. Créez une **Pull Request**
3. Remplissez le template de PR
4. Assignez des reviewers

### Template de Pull Request

```markdown
## Description
Brève description des changements apportés.

## Type de changement
- [ ] Bug fix
- [ ] Nouvelle fonctionnalité
- [ ] Breaking change
- [ ] Documentation

## Tests
- [ ] Tests unitaires ajoutés/mis à jour
- [ ] Tests d'intégration ajoutés/mis à jour
- [ ] Tests manuels effectués

## Checklist
- [ ] Code respecte les standards PSR-12
- [ ] Documentation mise à jour
- [ ] Pas de breaking changes non documentés
- [ ] Tests passent
```

## Signalement de bugs

### Avant de signaler

1. Vérifiez que le bug n'est pas déjà signalé
2. Testez avec la dernière version
3. Reproduisez le bug de manière consistante

### Template de bug report

```markdown
**Description du bug**
Description claire et concise du problème.

**Étapes pour reproduire**
1. Aller à '...'
2. Cliquer sur '...'
3. Voir l'erreur

**Comportement attendu**
Description de ce qui devrait se passer.

**Captures d'écran**
Si applicable, ajoutez des captures d'écran.

**Environnement**
- OS: [ex: macOS 12.0]
- Navigateur: [ex: Chrome 95]
- Version PHP: [ex: 8.2]
- Version Symfony: [ex: 7.4]

**Informations supplémentaires**
Tout autre contexte utile.
```

## Suggestions de fonctionnalités

### Template de feature request

```markdown
**Problème à résoudre**
Description claire du problème que cette fonctionnalité résoudrait.

**Solution proposée**
Description de la solution souhaitée.

**Alternatives considérées**
Autres solutions envisagées.

**Contexte supplémentaire**
Tout autre contexte ou capture d'écran.
```

## Ressources utiles

### Documentation

- [Documentation Symfony](https://symfony.com/doc/current/index.html)
- [Doctrine ORM](https://www.doctrine-project.org/projects/orm.html)
- [PHPUnit](https://phpunit.de/documentation.html)

### Outils

- **IDE recommandé** : PhpStorm, VS Code
- **Débogage** : Symfony Profiler, Xdebug
- **Base de données** : MySQL Workbench, phpMyAdmin

### Communauté

- **Discord** : [Lien vers le serveur Discord]
- **Forum** : [Lien vers le forum]
- **Email** : dev@ateliya.com

## Reconnaissance

Tous les contributeurs seront mentionnés dans le fichier [CONTRIBUTORS.md](CONTRIBUTORS.md).

---

**Merci de contribuer à Ateliya ! 🧵✨**