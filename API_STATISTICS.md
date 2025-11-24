# 📊 API Statistiques - Ateliya

Documentation complète de l'API de statistiques pour la plateforme Ateliya.

## 🎯 Vue d'ensemble

L'API Statistiques fournit des métriques avancées et des analyses de données pour le dashboard administrateur, incluant des graphiques, des comparaisons temporelles et des KPIs métier.

## 🔧 Configuration des Filtres

Tous les endpoints supportent deux types de filtres temporels :

### Filtres par Période Prédéfinie
```json
{
  "periode": "7j"    // 7 derniers jours
  "periode": "30j"   // 30 derniers jours  
  "periode": "3m"    // 3 derniers mois
}
```

### Filtres par Dates Personnalisées
```json
{
  "dateDebut": "2025-01-01",
  "dateFin": "2025-01-31"
}
```

## 📈 Endpoints Disponibles

### 1. Dashboard Principal
**`POST /api/statistique/dashboard`**

Retourne les métriques principales avec comparaison période précédente.

#### Métriques Incluses:
- **Commandes Totales** - Total des factures + réservations
- **Revenus** - Chiffre d'affaires total avec formatage
- **Nouveaux Clients** - Inscriptions clients
- **Total Clients** - Nombre total de clients
- **Nombre Réservations** - Réservations créées
- **Nombre Ventes** - Ventes (factures) créées
- **Nombre Factures** - Factures émises
- **Taux Réservation** - % réservations/commandes
- **Panier Moyen** - Valeur moyenne des commandes
- **Taux Conversion** - % réservations → ventes
- **Clients Actifs** - Clients avec paiements

#### Exemple de Réponse:
```json
{
  "success": true,
  "data": {
    "commandesTotales": {
      "valeur": 150,
      "variation": 12.5
    },
    "revenus": {
      "valeur": 45000,
      "valeurFormatee": "45K",
      "variation": 8.3
    },
    "nombreFactures": {
      "valeur": 85,
      "variation": 15.2
    },
    "panierMoyen": {
      "valeur": 300.00,
      "valeurFormatee": "300",
      "variation": -2.1
    }
  }
}
```

### 2. Évolution des Revenus
**`POST /api/statistique/revenus/evolution`**

Données pour graphique linéaire de l'évolution des revenus.

#### Paramètres Supplémentaires:
```json
{
  "groupBy": "jour|semaine|mois"
}
```

#### Exemple de Réponse:
```json
{
  "success": true,
  "data": {
    "labels": ["01/01", "02/01", "03/01"],
    "data": [1200, 1500, 1800],
    "total": 4500,
    "moyenne": 1500
  }
}
```

### 3. Évolution des Commandes
**`POST /api/statistique/commandes/evolution`**

Données pour graphique linéaire de l'évolution des commandes.

#### Paramètres:
- Mêmes filtres temporels
- `groupBy`: jour/semaine/mois

### 4. Répartition des Revenus par Type
**`POST /api/statistique/revenus/par-type`**

Données pour graphique camembert des types de paiements.

#### Exemple de Réponse:
```json
{
  "success": true,
  "data": {
    "labels": ["Factures", "Réservations", "Boutique", "Abonnements"],
    "data": [25000, 15000, 8000, 2000],
    "colors": ["#3B82F6", "#10B981", "#F59E0B", "#8B5CF6"],
    "total": 50000
  }
}
```

### 5. Top Clients
**`POST /api/statistique/top-clients`**

Liste des meilleurs clients par montant dépensé.

#### Paramètres Supplémentaires:
```json
{
  "limit": 10
}
```

#### Exemple de Réponse:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "nom": "Dupont",
      "prenom": "Marie",
      "totalDepense": 2500,
      "nombrePaiements": 8
    }
  ]
}
```

### 6. Comparatif Périodes
**`POST /api/statistique/comparatif`**

Comparaison détaillée avec la période précédente.

#### Exemple de Réponse:
```json
{
  "success": true,
  "data": {
    "revenus": {
      "actuel": 45000,
      "precedent": 38000,
      "variation": 7000,
      "variationPourcent": 18.4
    },
    "commandes": {
      "actuel": 150,
      "precedent": 130,
      "variation": 20,
      "variationPourcent": 15.4
    }
  }
}
```

## 🎨 Types de Graphiques Supportés

### Graphiques Linéaires
- Évolution des revenus dans le temps
- Évolution des commandes dans le temps
- Tendances avec groupement par jour/semaine/mois

### Graphiques Camembert
- Répartition des revenus par type de paiement
- Distribution avec couleurs personnalisées

### Cartes Métriques
- KPIs avec valeurs actuelles
- Variations en pourcentage
- Formatage intelligent (K, M)

## 🔒 Authentification

Tous les endpoints nécessitent une authentification JWT :

```http
Authorization: Bearer {votre_token_jwt}
```

## 📊 Codes de Réponse

- **200** - Succès
- **400** - Erreur de paramètres
- **401** - Non authentifié
- **500** - Erreur serveur

## 💡 Exemples d'Utilisation

### Dashboard 30 Derniers Jours
```bash
curl -X POST /api/statistique/dashboard \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"periode": "30j"}'
```

### Évolution Revenus par Semaine
```bash
curl -X POST /api/statistique/revenus/evolution \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "periode": "3m",
    "groupBy": "semaine"
  }'
```

### Top 5 Clients Période Personnalisée
```bash
curl -X POST /api/statistique/top-clients \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "dateDebut": "2025-01-01",
    "dateFin": "2025-01-31",
    "limit": 5
  }'
```

## 🚀 Intégration Frontend

### React/Vue.js
```javascript
const getStats = async (periode = '30j') => {
  const response = await fetch('/api/statistique/dashboard', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ periode })
  });
  
  return response.json();
};
```

### Chart.js Integration
```javascript
const chartData = await fetch('/api/statistique/revenus/evolution', {
  method: 'POST',
  body: JSON.stringify({ periode: '30j', groupBy: 'jour' })
});

new Chart(ctx, {
  type: 'line',
  data: {
    labels: chartData.labels,
    datasets: [{
      data: chartData.data,
      borderColor: '#3B82F6'
    }]
  }
});
```

## 🔄 Mise à Jour

Cette documentation est mise à jour avec les dernières fonctionnalités de l'API Statistiques d'Ateliya.

**Version**: 1.0  
**Dernière mise à jour**: Janvier 2025