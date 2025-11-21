# Documentation API - Ateliya

## Vue d'ensemble

L'API Ateliya est une API REST qui permet d'interagir avec la plateforme de gestion d'ateliers de couture. Elle utilise JSON pour les échanges de données et JWT pour l'authentification.

**URL de base** : `https://api.ateliya.com/api`
**Version** : v2.0
**Format** : JSON
**Authentification** : JWT Bearer Token

## Authentification

### Obtenir un token JWT

```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "motdepasse"
}
```

**Réponse** :
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "refresh_token": "def50200...",
  "expires_in": 3600,
  "user": {
    "id": 1,
    "email": "user@example.com",
    "nom": "Dupont",
    "prenom": "Jean"
  }
}
```

### Utiliser le token

Incluez le token dans l'en-tête Authorization de toutes vos requêtes :

```http
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

## Endpoints principaux

### Authentification

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/auth/register` | Inscription |
| POST | `/auth/login` | Connexion |
| POST | `/auth/refresh` | Rafraîchir le token |
| POST | `/auth/logout` | Déconnexion |
| POST | `/auth/forgot-password` | Mot de passe oublié |
| POST | `/auth/reset-password` | Réinitialiser le mot de passe |

### Utilisateurs

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/users/profile` | Profil utilisateur |
| PUT | `/users/profile` | Modifier le profil |
| POST | `/users/change-password` | Changer le mot de passe |
| GET | `/users/notifications` | Notifications |

### Boutiques

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/boutiques` | Liste des boutiques |
| POST | `/boutiques` | Créer une boutique |
| GET | `/boutiques/{id}` | Détails d'une boutique |
| PUT | `/boutiques/{id}` | Modifier une boutique |
| DELETE | `/boutiques/{id}` | Supprimer une boutique |

### Clients

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/clients` | Liste des clients |
| POST | `/clients` | Créer un client |
| GET | `/clients/{id}` | Détails d'un client |
| PUT | `/clients/{id}` | Modifier un client |
| DELETE | `/clients/{id}` | Supprimer un client |
| GET | `/clients/{id}/mesures` | Mesures d'un client |
| POST | `/clients/{id}/mesures` | Ajouter des mesures |

### Mesures

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/mesures/categories` | Catégories de mesures |
| GET | `/mesures/types` | Types de mesures |
| POST | `/mesures` | Enregistrer des mesures |
| PUT | `/mesures/{id}` | Modifier des mesures |
| DELETE | `/mesures/{id}` | Supprimer des mesures |

### Réservations

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/reservations` | Liste des réservations |
| POST | `/reservations` | Créer une réservation |
| GET | `/reservations/{id}` | Détails d'une réservation |
| PUT | `/reservations/{id}` | Modifier une réservation |
| DELETE | `/reservations/{id}` | Annuler une réservation |
| POST | `/reservations/{id}/confirm` | Confirmer une réservation |

### Paiements

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/paiements` | Liste des paiements |
| POST | `/paiements` | Créer un paiement |
| GET | `/paiements/{id}` | Détails d'un paiement |
| POST | `/paiements/{id}/refund` | Rembourser un paiement |

## Exemples détaillés

### Créer un client

```http
POST /api/clients
Authorization: Bearer {token}
Content-Type: application/json

{
  "nom": "Dupont",
  "prenom": "Marie",
  "email": "marie.dupont@example.com",
  "telephone": "+33123456789",
  "adresse": {
    "rue": "123 Rue de la Paix",
    "ville": "Paris",
    "code_postal": "75001",
    "pays": "France"
  },
  "date_naissance": "1990-05-15",
  "genre": "F"
}
```

**Réponse** :
```json
{
  "id": 42,
  "nom": "Dupont",
  "prenom": "Marie",
  "email": "marie.dupont@example.com",
  "telephone": "+33123456789",
  "adresse": {
    "rue": "123 Rue de la Paix",
    "ville": "Paris",
    "code_postal": "75001",
    "pays": "France"
  },
  "date_naissance": "1990-05-15",
  "genre": "F",
  "created_at": "2024-01-15T10:30:00Z",
  "updated_at": "2024-01-15T10:30:00Z"
}
```

### Enregistrer des mesures

```http
POST /api/mesures
Authorization: Bearer {token}
Content-Type: application/json

{
  "client_id": 42,
  "modele_id": 1,
  "mesures": {
    "tour_poitrine": 90,
    "tour_taille": 75,
    "tour_hanches": 95,
    "longueur_bras": 58,
    "longueur_dos": 42
  },
  "notes": "Client préfère les vêtements ajustés"
}
```

### Créer une réservation

```http
POST /api/reservations
Authorization: Bearer {token}
Content-Type: application/json

{
  "client_id": 42,
  "service": "Prise de mesures",
  "date_heure": "2024-02-01T14:00:00Z",
  "duree": 60,
  "notes": "Première consultation"
}
```

## Pagination

Les listes sont paginées par défaut. Utilisez les paramètres suivants :

- `page` : Numéro de page (défaut: 1)
- `limit` : Nombre d'éléments par page (défaut: 20, max: 100)

```http
GET /api/clients?page=2&limit=50
```

**Réponse** :
```json
{
  "data": [...],
  "pagination": {
    "current_page": 2,
    "per_page": 50,
    "total": 150,
    "total_pages": 3,
    "has_next": true,
    "has_prev": true
  }
}
```

## Codes de statut HTTP

| Code | Signification |
|------|---------------|
| 200 | OK - Succès |
| 201 | Created - Ressource créée |
| 204 | No Content - Succès sans contenu |
| 400 | Bad Request - Requête invalide |
| 401 | Unauthorized - Non authentifié |
| 403 | Forbidden - Accès refusé |
| 404 | Not Found - Ressource non trouvée |
| 422 | Unprocessable Entity - Erreur de validation |
| 429 | Too Many Requests - Limite de débit atteinte |
| 500 | Internal Server Error - Erreur serveur |

## Gestion des erreurs

### Format des erreurs

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Les données fournies sont invalides",
    "details": {
      "email": ["L'adresse email est requise"],
      "telephone": ["Le format du téléphone est invalide"]
    }
  }
}
```

## Support

- **Documentation interactive** : https://api.ateliya.com/docs
- **Email** : api-support@ateliya.com
- **Status** : https://status.ateliya.com