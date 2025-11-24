# Documentation API - Gestion des Stocks

## Vue d'ensemble

L'API de gestion des stocks d'Ateliya permet de gérer complètement les entrées et sorties de stock des modèles de vêtements dans les boutiques. Elle offre un suivi détaillé des mouvements de stock avec historique complet et traçabilité.

## Authentification

Toutes les routes nécessitent une authentification JWT valide et un abonnement actif.

```http
Authorization: Bearer {votre_token_jwt}
```

## Endpoints disponibles

### 1. Historique des mouvements de stock d'une boutique

**GET** `/api/stock/{id}`

Récupère l'historique paginé de tous les mouvements de stock (entrées et sorties) d'une boutique spécifique.

#### Paramètres

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `id` | integer | ✅ | ID de la boutique |
| `page` | integer | ❌ | Numéro de page (défaut: 1) |
| `limit` | integer | ❌ | Éléments par page (défaut: 20, max: 100) |

#### Exemple de requête

```http
GET /api/stock/1?page=1&limit=20
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

#### Réponse de succès (200)

```json
{
  "status": "SUCCESS",
  "message": "Historique des mouvements récupéré avec succès",
  "data": [
    {
      "id": 15,
      "type": "Entree",
      "quantite": 75,
      "date": "2025-01-15T14:30:00+00:00",
      "boutique": {
        "id": 1,
        "libelle": "Boutique Centre-Ville",
        "adresse": "123 Rue de la Mode, Paris"
      },
      "entreprise": {
        "id": 1,
        "nom": "Atelier Couture Pro"
      },
      "ligneEntres": [
        {
          "id": 25,
          "quantite": 25,
          "modele": {
            "id": 8,
            "quantite": 150,
            "prix": "45.99",
            "taille": "M",
            "modele": {
              "id": 3,
              "libelle": "Robe d'été fleurie",
              "description": "Belle robe légère pour l'été"
            }
          }
        }
      ],
      "createdAt": "2025-01-15T10:30:00+00:00",
      "updatedAt": "2025-01-15T10:30:00+00:00",
      "createdBy": {
        "id": 5,
        "nom": "Dupont",
        "prenom": "Marie"
      }
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 45,
    "total_pages": 3
  }
}
```

### 2. Historique détaillé d'un modèle

**GET** `/api/stock/modeleBoutique/{id}`

Récupère l'historique détaillé de tous les mouvements d'un modèle spécifique dans une boutique.

#### Paramètres

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `id` | integer | ✅ | ID du modèle de boutique |
| `page` | integer | ❌ | Numéro de page (défaut: 1) |
| `limit` | integer | ❌ | Éléments par page (défaut: 20, max: 100) |

#### Exemple de requête

```http
GET /api/stock/modeleBoutique/8?page=1&limit=10
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

#### Réponse de succès (200)

```json
{
  "status": "SUCCESS",
  "message": "Historique du modèle récupéré avec succès",
  "data": [
    {
      "id": 42,
      "quantite": 15,
      "modele": {
        "id": 8,
        "quantite": 125,
        "prix": "89.99",
        "taille": "L",
        "modele": {
          "id": 3,
          "libelle": "Chemise en lin",
          "description": "Chemise légère en lin naturel",
          "quantiteGlobale": 450
        },
        "createdAt": "2025-01-10T09:15:00+00:00",
        "updatedAt": "2025-01-15T14:22:00+00:00"
      },
      "entreStock": {
        "id": 12,
        "type": "Entree",
        "quantite": 50,
        "date": "2025-01-15T14:00:00+00:00",
        "createdAt": "2025-01-15T14:00:00+00:00",
        "boutique": {
          "id": 1,
          "libelle": "Boutique Centre-Ville"
        }
      }
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total": 28,
    "total_pages": 3
  }
}
```

### 3. Créer une entrée de stock

**POST** `/api/stock/entree`

Enregistre une entrée de stock (réapprovisionnement) avec mise à jour automatique des quantités.

#### Corps de la requête

```json
{
  "boutiqueId": 1,
  "lignes": [
    {
      "quantite": 50,
      "modeleBoutiqueId": 5
    },
    {
      "quantite": 30,
      "modeleBoutiqueId": 8
    },
    {
      "quantite": 20,
      "modeleBoutiqueId": 12
    }
  ]
}
```

#### Validation des données

- `boutiqueId` : **obligatoire**, doit exister
- `lignes` : **obligatoire**, minimum 1 ligne
- `quantite` : **obligatoire**, doit être > 0
- `modeleBoutiqueId` : **obligatoire**, doit exister

#### Réponse de succès (201)

```json
{
  "status": "SUCCESS",
  "message": "Entrée de stock créée avec succès",
  "data": {
    "id": 15,
    "type": "Entree",
    "quantite": 100,
    "boutique": {
      "id": 1,
      "libelle": "Boutique Centre-Ville"
    },
    "entreprise": {
      "id": 1,
      "nom": "Atelier Couture Pro"
    },
    "ligneEntres": [
      {
        "id": 25,
        "quantite": 50,
        "modele": {
          "id": 5,
          "quantite": 200,
          "prix": "35.99",
          "taille": "S"
        }
      }
    ],
    "createdAt": "2025-01-15T10:30:00+00:00",
    "createdBy": {
      "id": 5,
      "nom": "Dupont",
      "prenom": "Marie"
    }
  }
}
```

### 4. Mettre à jour une entrée de stock

**PUT** `/api/stock/entree/{id}`

Met à jour une entrée de stock existante. Les anciennes lignes sont supprimées et remplacées.

#### Paramètres

| Paramètre | Type | Requis | Description |
|-----------|------|--------|-------------|
| `id` | integer | ✅ | ID de l'entrée de stock à modifier |

#### Corps de la requête

```json
{
  "boutiqueId": 1,
  "lignes": [
    {
      "quantite": 60,
      "modeleBoutiqueId": 5
    },
    {
      "quantite": 40,
      "modeleBoutiqueId": 8
    }
  ]
}
```

#### Réponse de succès (200)

```json
{
  "status": "SUCCESS",
  "message": "Entrée de stock mise à jour avec succès",
  "data": {
    "id": 1,
    "type": "Entree",
    "quantite": 100,
    "updatedAt": "2025-01-15T15:45:00+00:00"
  }
}
```

### 5. Créer une sortie de stock

**POST** `/api/stock/sortie`

Enregistre une sortie de stock (vente, transfert, perte) avec vérification automatique des quantités disponibles.

#### Corps de la requête

```json
{
  "boutiqueId": 1,
  "lignes": [
    {
      "quantite": 20,
      "modeleBoutiqueId": 5
    },
    {
      "quantite": 15,
      "modeleBoutiqueId": 8
    },
    {
      "quantite": 10,
      "modeleBoutiqueId": 12
    }
  ]
}
```

#### Validation des données

- Vérification automatique de la disponibilité du stock
- `quantite` doit être ≤ quantité disponible
- Mise à jour automatique des stocks après validation

#### Réponse de succès (201)

```json
{
  "status": "SUCCESS",
  "message": "Sortie de stock créée avec succès",
  "data": {
    "id": 20,
    "type": "Sortie",
    "quantite": 45,
    "boutique": {
      "id": 1,
      "libelle": "Boutique Centre-Ville"
    },
    "entreprise": {
      "id": 1,
      "nom": "Atelier Couture Pro"
    },
    "ligneEntres": [
      {
        "id": 35,
        "quantite": 20,
        "modele": {
          "id": 5,
          "quantite": 130,
          "prix": "35.99"
        }
      }
    ],
    "createdAt": "2025-01-15T16:00:00+00:00",
    "createdBy": {
      "id": 5,
      "nom": "Dupont",
      "prenom": "Marie"
    }
  }
}
```

## Gestion des erreurs

### Codes d'erreur courants

| Code | Description | Exemple |
|------|-------------|---------|
| 400 | Données invalides | Stock insuffisant, modèle introuvable |
| 401 | Non authentifié | Token JWT manquant/invalide |
| 403 | Abonnement requis | Fonctionnalité premium |
| 404 | Ressource non trouvée | Boutique/modèle inexistant |
| 500 | Erreur serveur | Erreur interne |

### Exemple d'erreur - Stock insuffisant

```json
{
  "status": "ERROR",
  "message": "Stock insuffisant pour le modèle ID 5 (disponible: 10, demandé: 20)"
}
```

### Exemple d'erreur - Abonnement requis

```json
{
  "status": "ERROR",
  "message": "Abonnement requis pour cette fonctionnalité"
}
```

## Fonctionnalités avancées

### Traçabilité complète

- Chaque mouvement est tracé avec l'utilisateur responsable
- Horodatage précis de création et modification
- Historique complet des modifications

### Mise à jour automatique des stocks

- **Entrées** : Ajout automatique aux quantités
- **Sorties** : Soustraction avec vérification préalable
- **Mise à jour globale** : Synchronisation des stocks globaux

### Pagination intelligente

- Pagination automatique pour les grandes listes
- Paramètres configurables (page, limit)
- Informations de pagination dans la réponse

### Validation robuste

- Vérification de l'existence des ressources
- Validation des quantités
- Contrôle des permissions et abonnements

## Exemples d'utilisation

### Scénario 1 : Réapprovisionnement d'une boutique

```bash
# 1. Créer une entrée de stock
curl -X POST "https://api.ateliya.com/api/stock/entree" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "boutiqueId": 1,
    "lignes": [
      {"quantite": 50, "modeleBoutiqueId": 5},
      {"quantite": 30, "modeleBoutiqueId": 8}
    ]
  }'

# 2. Vérifier l'historique
curl -X GET "https://api.ateliya.com/api/stock/1" \
  -H "Authorization: Bearer {token}"
```

### Scénario 2 : Vente de produits

```bash
# 1. Créer une sortie de stock
curl -X POST "https://api.ateliya.com/api/stock/sortie" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "boutiqueId": 1,
    "lignes": [
      {"quantite": 2, "modeleBoutiqueId": 5},
      {"quantite": 1, "modeleBoutiqueId": 8}
    ]
  }'
```

### Scénario 3 : Suivi d'un modèle spécifique

```bash
# Consulter l'historique détaillé d'un modèle
curl -X GET "https://api.ateliya.com/api/stock/modeleBoutique/8" \
  -H "Authorization: Bearer {token}"
```

## Bonnes pratiques

### Gestion des stocks

1. **Vérifiez toujours** les quantités disponibles avant les sorties
2. **Utilisez la pagination** pour les grandes listes
3. **Tracez les mouvements** avec des utilisateurs identifiés
4. **Validez les données** avant envoi

### Performance

1. **Limitez les requêtes** avec une pagination appropriée
2. **Utilisez le cache** pour les données fréquemment consultées
3. **Groupez les opérations** quand possible

### Sécurité

1. **Authentifiez toujours** les requêtes
2. **Vérifiez les permissions** sur les boutiques
3. **Validez les entrées** utilisateur
4. **Loggez les opérations** sensibles

## Support

Pour toute question sur l'API de gestion des stocks :

- **Documentation interactive** : https://api.ateliya.com/docs
- **Email** : api-support@ateliya.com
- **Discord** : [Lien vers le serveur Discord]