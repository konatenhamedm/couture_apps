# 💰 API Gestion Financière - Ateliya

Documentation des APIs pour la gestion financière de la plateforme Ateliya.

## 📋 Table des matières

- [Factures](#-factures)
- [Paiements](#-paiements)
- [Ventes](#-ventes)
- [Rapports](#-rapports)

## 📄 Factures

### Liste des factures par boutique
```http
GET /api/facture/boutique/{id}
```

**Réponse :**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "numero": "FAC-000001",
      "date": "2025-01-03 10:30:00",
      "montant": 50000,
      "paye": 25000,
      "reste": 25000,
      "client": {
        "id": 1,
        "nom": "Diallo",
        "prenom": "Aminata"
      }
    }
  ]
}
```

### Créer une facture
```http
POST /api/facture
```

**Corps de la requête :**
```json
{
  "clientId": 1,
  "boutiqueId": 1,
  "montant": 50000,
  "description": "Facture pour tailleur"
}
```

### Détails d'une facture
```http
GET /api/facture/{id}
```

## 💳 Paiements

### Paiements de factures par boutique
```http
GET /api/paiement/facture/boutique/{id}
```

**Réponse :**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "date": "2025-01-03 14:20:00",
      "montant": 25000,
      "modePaiement": "Espèces",
      "reference": "REF123",
      "facture": {
        "id": 1,
        "numero": "FAC-000001",
        "client": {
          "nom": "Diallo",
          "prenom": "Aminata"
        }
      }
    }
  ]
}
```

### Paiements de réservations par boutique
```http
GET /api/paiement/reservation/boutique/{id}
```

**Réponse :**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "date": "2025-01-03 16:45:00",
      "montant": 15000,
      "modePaiement": "Mobile Money",
      "reference": "MM789",
      "reservation": {
        "id": 1,
        "client": {
          "nom": "Sow",
          "prenom": "Mamadou",
          "numero": "77123456789"
        }
      }
    }
  ]
}
```

### Créer un paiement de facture
```http
POST /api/paiement/facture
```

**Corps de la requête :**
```json
{
  "factureId": 1,
  "montant": 25000,
  "modePaiement": "Espèces",
  "reference": "REF123"
}
```

### Créer un paiement de réservation
```http
POST /api/paiement/reservation
```

**Corps de la requête :**
```json
{
  "reservationId": 1,
  "montant": 15000,
  "modePaiement": "Mobile Money",
  "reference": "MM789"
}
```

## 🛒 Ventes

### Liste des ventes par boutique
```http
GET /api/vente/boutique/{id}
```

**Réponse :**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "numero": "VTE-2025-0001",
      "date": "2025-01-03 11:15:00",
      "montant": 30000,
      "modePaiement": "Espèces",
      "client": {
        "id": 1,
        "nom": "Ndiaye",
        "prenom": "Fatou"
      },
      "ligneVentes": [
        {
          "id": 1,
          "produit": "Tissu Wax",
          "quantite": 2,
          "prixUnitaire": 15000,
          "total": 30000
        }
      ]
    }
  ]
}
```

### Créer une vente
```http
POST /api/vente
```

**Corps de la requête :**
```json
{
  "boutiqueId": 1,
  "clientId": 1,
  "modePaiement": "Espèces",
  "lignes": [
    {
      "produit": "Tissu Wax",
      "quantite": 2,
      "prixUnitaire": 15000
    }
  ]
}
```

### Détails d'une vente
```http
GET /api/vente/{id}
```

### Statistiques des ventes
```http
GET /api/vente/stats/boutique/{id}
```

**Réponse :**
```json
{
  "success": true,
  "data": {
    "aujourd_hui": {
      "nombre": 5,
      "montant": 125000
    },
    "ce_mois": {
      "nombre": 45,
      "montant": 1250000
    }
  }
}
```

## 📊 Rapports

### Rapport financier
```http
POST /api/rapport/financier
```

**Corps de la requête :**
```json
{
  "periode": "mois",
  "dateDebut": "2025-01-01",
  "dateFin": "2025-01-31",
  "boutiqueId": 1,
  "typeRapport": "complet"
}
```

**Réponse :**
```json
{
  "success": true,
  "data": {
    "kpis": {
      "revenusTotal": 41700000,
      "factures": 24300000,
      "ventes": 11700000,
      "croissance": 1.85,
      "nombreTransactions": 567,
      "panierMoyen": 73500
    },
    "evolutionRevenus": [
      {
        "mois": "Jan",
        "factures": 3200000,
        "reservations": 2800000,
        "ventes": 1500000
      }
    ],
    "repartitionPaiements": [
      {
        "type": "Espèces",
        "montant": 15200000
      }
    ],
    "topBoutiques": [
      {
        "nom": "Boutique Centre-ville",
        "revenus": 10500000
      }
    ],
    "comparaisonPeriodes": {
      "periodeCourante": 41700000,
      "periodePrecedente": 35200000,
      "evolution": 18.5,
      "tendance": "hausse"
    }
  }
}
```

### Export PDF
```http
POST /api/rapport/export/pdf
```

### Export Excel
```http
POST /api/rapport/export/excel
```

## 🔧 Paramètres communs

### Périodes disponibles
- `jour` : Aujourd'hui
- `semaine` : Cette semaine
- `mois` : Ce mois
- `trimestre` : Ce trimestre
- `annee` : Cette année
- `personnalise` : Période personnalisée (nécessite dateDebut et dateFin)

### Modes de paiement
- `Espèces`
- `Mobile Money`
- `Carte bancaire`
- `Virement`
- `Chèque`

### Types de rapport
- `complet` : Rapport complet
- `factures` : Factures uniquement
- `reservations` : Réservations uniquement
- `ventes` : Ventes uniquement
- `paiements` : Paiements uniquement

## 🔐 Authentification

Toutes les APIs nécessitent une authentification JWT. Incluez le token dans l'en-tête :

```http
Authorization: Bearer votre_token_jwt
```

## 📝 Codes de réponse

- `200` : Succès
- `400` : Erreur de validation
- `401` : Non authentifié
- `403` : Non autorisé
- `404` : Ressource non trouvée
- `500` : Erreur serveur

## 💡 Exemples d'utilisation

### Récupérer les factures d'une boutique
```javascript
const response = await apiFetch('/facture/boutique/1');
const factures = response.data;
```

### Créer un paiement
```javascript
const paiement = await apiFetch('/paiement/facture', {
  method: 'POST',
  body: JSON.stringify({
    factureId: 1,
    montant: 25000,
    modePaiement: 'Espèces'
  })
});
```

### Générer un rapport
```javascript
const rapport = await apiFetch('/rapport/financier', {
  method: 'POST',
  body: JSON.stringify({
    periode: 'mois',
    typeRapport: 'complet'
  })
});
```

---

**Développé avec ❤️ par l'équipe Ateliya**