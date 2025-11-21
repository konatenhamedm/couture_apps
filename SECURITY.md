# Politique de Sécurité - Ateliya

## Versions supportées

Nous fournissons des mises à jour de sécurité pour les versions suivantes d'Ateliya :

| Version | Supportée          |
| ------- | ------------------ |
| 2.x.x   | ✅ Oui             |
| 1.9.x   | ✅ Oui             |
| 1.8.x   | ❌ Non             |
| < 1.8   | ❌ Non             |

## Signalement de vulnérabilités

### Processus de signalement

Si vous découvrez une vulnérabilité de sécurité dans Ateliya, nous vous demandons de nous la signaler de manière responsable :

1. **NE PAS** créer d'issue publique sur GitHub
2. Envoyez un email à : **security@ateliya.com**
3. Incluez autant de détails que possible (voir template ci-dessous)

### Template de signalement

```
Objet : [SÉCURITÉ] Vulnérabilité dans Ateliya

Description de la vulnérabilité :
[Description détaillée]

Étapes pour reproduire :
1. [Étape 1]
2. [Étape 2]
3. [Étape 3]

Impact potentiel :
[Description de l'impact]

Environnement :
- Version d'Ateliya : 
- Version PHP : 
- Système d'exploitation : 
- Navigateur (si applicable) : 

Preuves de concept :
[Code, captures d'écran, logs, etc.]

Votre nom/pseudonyme (pour les remerciements) :
[Optionnel]
```

### Délais de réponse

- **Accusé de réception** : 24 heures
- **Évaluation initiale** : 72 heures
- **Mise à jour de statut** : 7 jours
- **Résolution** : Variable selon la criticité

### Classification des vulnérabilités

#### Critique (24-48h)
- Exécution de code à distance
- Injection SQL permettant l'accès aux données
- Contournement d'authentification
- Accès non autorisé aux données sensibles

#### Élevée (1 semaine)
- Élévation de privilèges
- XSS stocké
- CSRF sur actions critiques
- Divulgation d'informations sensibles

#### Moyenne (2 semaines)
- XSS réfléchi
- Divulgation d'informations non critiques
- Déni de service
- Problèmes de configuration

#### Faible (1 mois)
- Problèmes d'information
- Vulnérabilités nécessitant une interaction utilisateur complexe

## Mesures de sécurité implémentées

### Authentification et autorisation

- **JWT (JSON Web Tokens)** pour l'authentification API
- **Système OTP** pour la réinitialisation de mot de passe
- **Hachage sécurisé** des mots de passe (bcrypt)
- **Limitation du taux de requêtes** (rate limiting)
- **Vérification CSRF** sur les formulaires

### Protection des données

- **Chiffrement en transit** (HTTPS/TLS 1.3)
- **Chiffrement au repos** pour les données sensibles
- **Validation stricte** des entrées utilisateur
- **Échappement des sorties** pour prévenir XSS
- **Requêtes préparées** pour prévenir l'injection SQL

### Infrastructure

- **Pare-feu applicatif** (WAF)
- **Surveillance des intrusions** (IDS)
- **Sauvegardes chiffrées** régulières
- **Logs de sécurité** détaillés
- **Isolation des environnements**

### Conformité

- **RGPD** - Règlement Général sur la Protection des Données
- **PCI DSS** - Pour le traitement des paiements
- **ISO 27001** - Système de management de la sécurité

## Bonnes pratiques pour les utilisateurs

### Mots de passe

- Utilisez des mots de passe forts (12+ caractères)
- Activez l'authentification à deux facteurs quand disponible
- Ne réutilisez pas vos mots de passe
- Utilisez un gestionnaire de mots de passe

### Navigation sécurisée

- Vérifiez toujours l'URL (https://ateliya.com)
- Ne cliquez pas sur des liens suspects dans les emails
- Déconnectez-vous après utilisation sur des ordinateurs partagés
- Maintenez votre navigateur à jour

### Protection des données

- Ne partagez jamais vos identifiants
- Vérifiez régulièrement l'activité de votre compte
- Signalez immédiatement toute activité suspecte
- Sauvegardez vos données importantes

## Programme de bug bounty

### Récompenses

Nous offrons des récompenses pour la découverte responsable de vulnérabilités :

| Criticité | Récompense    |
|-----------|---------------|
| Critique  | 500€ - 2000€  |
| Élevée    | 200€ - 500€   |
| Moyenne   | 50€ - 200€    |
| Faible    | Remerciements |

### Conditions

- La vulnérabilité doit être nouvelle et non signalée
- Signalement responsable (pas de divulgation publique)
- Respect de nos systèmes et données
- Pas d'impact sur les utilisateurs

### Exclusions

- Vulnérabilités dans des services tiers
- Attaques de déni de service
- Ingénierie sociale
- Vulnérabilités nécessitant un accès physique

## Mises à jour de sécurité

### Notification

Les mises à jour de sécurité sont communiquées via :

- **Email** aux administrateurs système
- **Notifications** dans l'interface d'administration
- **Page de statut** : https://status.ateliya.com
- **Blog sécurité** : https://blog.ateliya.com/security

### Installation

```bash
# Vérifier les mises à jour
composer outdated

# Mettre à jour les dépendances de sécurité
composer update --with-dependencies

# Appliquer les migrations si nécessaire
php bin/console doctrine:migrations:migrate

# Vider le cache
php bin/console cache:clear --env=prod
```

## Audit de sécurité

### Audits internes

- **Tests de pénétration** trimestriels
- **Revue de code** pour chaque release
- **Analyse statique** automatisée (SAST)
- **Scan des dépendances** quotidien

### Audits externes

- Audit de sécurité annuel par un tiers
- Certification de conformité
- Tests d'intrusion par des experts

## Contact sécurité

### Équipe sécurité

- **Email principal** : security@ateliya.com
- **Email urgent** : urgent-security@ateliya.com
- **Téléphone d'urgence** : [Numéro 24/7]

### Clé PGP

Pour les communications sensibles, utilisez notre clé PGP :

```
-----BEGIN PGP PUBLIC KEY BLOCK-----
[Clé PGP publique]
-----END PGP PUBLIC KEY BLOCK-----
```

### Heures de disponibilité

- **Support sécurité** : 24/7 pour les urgences
- **Équipe développement** : Lun-Ven 9h-18h CET
- **Temps de réponse** : < 4h pour les urgences

## Ressources supplémentaires

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Guide de sécurité Symfony](https://symfony.com/doc/current/security.html)
- [Bonnes pratiques PHP](https://www.php.net/manual/en/security.php)

---

**La sécurité est une responsabilité partagée. Merci de nous aider à maintenir Ateliya sécurisé pour tous nos utilisateurs.**