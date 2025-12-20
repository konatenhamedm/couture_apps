# Test de l'API Dashboard Succursale

## Endpoint créé
```
POST /api/statistique/ateliya/succursale/{id}
```

## Exemple de requête

### URL
```
POST /api/statistique/ateliya/succursale/1
```

### Headers
```
Content-Type: application/json
Authorization: Bearer {your_token}
```

### Body (optionnel)
```json
{
    "dateDebut": "2025-01-01",
    "dateFin": "2025-01-31",
    "filtre": "mois",
    "valeur": "2025-01"
}
```

## Exemple de réponse attendue

```json
{
    "success": true,
    "data": {
        "succursale_id": 1,
        "succursale_nom": "Succursale Centre-ville",
        "periode": {
            "debut": "2025-01-01",
            "fin": "2025-01-31",
            "nbJours": 31
        },
        "kpis": {
            "chiffreAffaires": 1850000,
            "facturesActives": 18,
            "clientsActifs": 89,
            "mesuresEnCours": 12
        },
        "revenusQuotidiens": [
            {
                "jour": "Lun 15",
                "factures": 3,
                "mesures": 5,
                "paiements": 2,
                "revenus": 185000
            }
        ],
        "revenusParType": [
            {
                "type": "Factures",
                "revenus": 1200000
            },
            {
                "type": "Paiements factures",
                "revenus": 1200000
            },
            {
                "type": "Mesures",
                "revenus": 0
            },
            {
                "type": "Autres",
                "revenus": 0
            }
        ],
        "activitesSuccursale": [
            {
                "activite": "Factures clients",
                "nombre": 18,
                "revenus": 1200000,
                "progression": 100
            },
            {
                "activite": "Prises de mesures",
                "nombre": 12,
                "revenus": 0,
                "progression": 100
            },
            {
                "activite": "Paiements reçus",
                "nombre": 8,
                "revenus": 1200000,
                "progression": 100
            },
            {
                "activite": "Clients actifs",
                "nombre": 89,
                "revenus": 0,
                "progression": 100
            }
        ],
        "dernieresTransactions": [
            {
                "id": "FAC-20250130-001",
                "type": "Facture",
                "client": "Jean Kouame",
                "montant": 75000,
                "statut": "payée"
            },
            {
                "id": "PAI-20250130-002",
                "type": "Paiement",
                "client": "Marie Traore",
                "montant": 50000,
                "statut": "payé"
            }
        ]
    }
}
```

## Fonctionnalités implémentées

### 1. **Endpoint principal**
- Route: `/api/statistique/ateliya/succursale/{id}`
- Méthode: POST
- Paramètres: ID de la succursale dans l'URL
- Body optionnel avec filtres de date

### 2. **KPIs calculés**
- **Chiffre d'affaires**: Somme des paiements de factures de la succursale
- **Factures actives**: Nombre de factures actives dans la période
- **Clients actifs**: Nombre de clients ayant des factures dans la période
- **Mesures en cours**: Nombre de mesures avec date de retrait future

### 3. **Revenus quotidiens**
- Graphique jour par jour avec:
  - Nombre de factures créées
  - Nombre de mesures prises
  - Nombre de paiements reçus
  - Total des revenus du jour

### 4. **Répartition par type**
- Factures
- Paiements factures
- Mesures
- Autres

### 5. **Activités de la succursale**
- Factures clients (nombre + revenus)
- Prises de mesures (nombre)
- Paiements reçus (nombre + revenus)
- Clients actifs (nombre)

### 6. **Dernières transactions**
- 5 dernières factures et paiements
- Informations client et montant
- Statut de paiement

## Sécurité
- Vérification que la succursale appartient à l'entreprise de l'utilisateur
- Gestion des erreurs (succursale non trouvée, accès non autorisé)

## Méthodes ajoutées dans les repositories

### PaiementFactureRepository
- `sumBySuccursaleAndPeriod()`
- `sumBySuccursaleAndDay()`
- `countBySuccursaleAndPeriod()`
- `countBySuccursaleAndDay()`
- `findLatestBySuccursale()`

### FactureRepository
- `countActiveBySuccursaleAndPeriod()`
- `countBySuccursaleAndPeriod()`
- `countBySuccursaleAndDay()`
- `findLatestBySuccursale()`

### MesureRepository
- `countEnCoursBySuccursale()`
- `countBySuccursaleAndPeriod()`
- `countBySuccursaleAndDay()`

### ClientRepository
- `countActiveBySuccursaleAndPeriod()`