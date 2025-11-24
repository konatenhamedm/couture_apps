# 📊 API Statistiques - Ateliya

Documentation complète de l'API de statistiques pour la plateforme Ateliya.

## 🎯 Vue d'ensemble

L'API Statistiques fournit des métriques avancées et des analyses de données pour le dashboard administrateur, incluant des graphiques, des comparaisons temporelles et des KPIs métier.

## 📱 Aperçu Visuel du Dashboard

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           📊 DASHBOARD ATELIYA                             │
├─────────────────────────────────────────────────────────────────────────────┤
│  📅 Période: 30 derniers jours                    🔄 Mis à jour: maintenant │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐        │
│  │ 💰 REVENUS  │  │ 📦 COMMANDES│  │ 👥 CLIENTS  │  │ 📄 FACTURES │        │
│  │    45K      │  │     150     │  │    1,234    │  │     85      │        │
│  │   ↗️ +8.3%   │  │   ↗️ +12.5%  │  │   ↗️ +15.2%  │  │   ↗️ +18.4%  │        │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘        │
│                                                                             │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐        │
│  │ 📅 RÉSERV.  │  │ 🛒 PANIER   │  │ 📈 TAUX     │  │ ⚡ ACTIFS   │        │
│  │     65      │  │    300€     │  │   43.3%     │  │    892      │        │
│  │   ↗️ +5.7%   │  │   ↘️ -2.1%   │  │   ↗️ +3.2%   │  │   ↗️ +22.1%  │        │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘        │
└─────────────────────────────────────────────────────────────────────────────┘
```

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

#### Visualisation Graphique:
```
📈 Évolution des Revenus (30 derniers jours)

2000€ ┤                                    ╭─╮
1800€ ┤                               ╭────╯ ╰╮
1600€ ┤                          ╭────╯       ╰╮
1400€ ┤                     ╭────╯             ╰╮
1200€ ┤                ╭────╯                   ╰─╮
1000€ ┤           ╭────╯                         ╰╮
 800€ ┤      ╭────╯                               ╰╮
 600€ ┤ ╭────╯                                     ╰─╮
      └─┴────┴────┴────┴────┴────┴────┴────┴────┴────┴─
       01   05   10   15   20   25   30
       Jan  Jan  Jan  Jan  Jan  Jan  Jan

📊 Total: 45K€  📈 Moyenne: 1.5K€/jour  ↗️ +8.3%
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

#### Visualisation Camembert:
```
🥧 Répartition des Revenus par Type

           ╭─────────╮
       ╭───╯         ╰───╮
    ╭─╯     Factures     ╰─╮
   ╱         50%          ╲
  ╱                       ╲
 ╱           🔵            ╲
╱                          ╲
│    🟢 Réserv.    🟡 Bout. │
│      30%          16%     │
╲                          ╱
 ╲         🟣 Abon.       ╱
  ╲          4%          ╱
   ╰─╮                 ╱
     ╰───╮         ╭───╯
         ╰─────────╯

📊 Total: 50K€
🔵 Factures: 25K€ (50%)
🟢 Réservations: 15K€ (30%)
🟡 Boutique: 8K€ (16%)
🟣 Abonnements: 2K€ (4%)
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

#### Visualisation Tableau:
```
🏆 Top 10 Clients (30 derniers jours)

┌─────┬─────────────────────┬─────────────┬─────────────┬─────────┐
│ #   │ Client              │ Dépenses    │ Commandes   │ Moy/Cmd │
├─────┼─────────────────────┼─────────────┼─────────────┼─────────┤
│ 🥇  │ Marie Dupont        │   2,500€    │      8      │   312€  │
│ 🥈  │ Jean Martin         │   2,200€    │      6      │   367€  │
│ 🥉  │ Sophie Bernard      │   1,950€    │      7      │   279€  │
│ 4   │ Pierre Durand       │   1,800€    │      5      │   360€  │
│ 5   │ Claire Moreau       │   1,650€    │      9      │   183€  │
│ 6   │ Michel Leroy        │   1,500€    │      4      │   375€  │
│ 7   │ Anne Petit          │   1,350€    │      6      │   225€  │
│ 8   │ Paul Roux           │   1,200€    │      3      │   400€  │
│ 9   │ Julie Simon         │   1,100€    │      5      │   220€  │
│ 10  │ Marc Blanc          │   1,000€    │      4      │   250€  │
└─────┴─────────────────────┴─────────────┴─────────────┴─────────┘

📊 Total Top 10: 15,250€  📈 Moyenne: 1,525€/client
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

### 📈 Graphiques Linéaires
```
📊 Évolution Temporelle

  Value
    ↑
 2000 ┤     ╭─╮
 1500 ┤   ╭─╯ ╰─╮
 1000 ┤ ╭─╯     ╰─╮
  500 ┤─╯         ╰─
    0 └─────────────────→ Time
      J F M A M J J A S O
```
- Évolution des revenus dans le temps
- Évolution des commandes dans le temps
- Tendances avec groupement par jour/semaine/mois

### 🥧 Graphiques Camembert
```
📊 Répartition Proportionnelle

      ╭─────╮
   ╭─╯   A   ╰─╮
  ╱     50%    ╲
 ╱               ╲
│   D    ╱─╲   B │
│  10%  ╱   ╲ 30%│
 ╲     ╱  C  ╲   ╱
  ╲   ╱  10% ╲ ╱
   ╰─╯       ╰╯
```
- Répartition des revenus par type de paiement
- Distribution avec couleurs personnalisées

### 📊 Cartes Métriques
```
┌─────────────────┐
│ 💰 REVENUS      │
│                 │
│     45,250€     │
│    ↗️ +8.3%      │
│                 │
│ vs mois dernier │
└─────────────────┘
```
- KPIs avec valeurs actuelles
- Variations en pourcentage
- Formatage intelligent (K, M)

### 📋 Tableaux de Données
```
┌─────────────────────────────┐
│ 🏆 CLASSEMENTS & LISTES     │
├─────────────────────────────┤
│ #1  Client A      2,500€    │
│ #2  Client B      2,200€    │
│ #3  Client C      1,950€    │
│ ...                         │
└─────────────────────────────┘
```
- Top clients, produits, services
- Listes ordonnées avec métriques
- Pagination et filtres

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

### React/Vue.js Dashboard Component
```javascript
const StatsDashboard = () => {
  const [stats, setStats] = useState(null);
  const [periode, setPeriode] = useState('30j');

  const getStats = async (periode = '30j') => {
    const response = await fetch('/api/statistique/dashboard', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ periode })
    });
    
    const data = await response.json();
    setStats(data.data);
  };

  return (
    <div className="dashboard">
      <div className="metrics-grid">
        <MetricCard 
          title="Revenus" 
          value={stats?.revenus?.valeurFormatee}
          variation={stats?.revenus?.variation}
          icon="💰"
        />
        <MetricCard 
          title="Commandes" 
          value={stats?.commandesTotales?.valeur}
          variation={stats?.commandesTotales?.variation}
          icon="📦"
        />
      </div>
    </div>
  );
};
```

### Chart.js Integration avec Style
```javascript
// Configuration complète pour graphique revenus
const chartData = await fetch('/api/statistique/revenus/evolution', {
  method: 'POST',
  body: JSON.stringify({ periode: '30j', groupBy: 'jour' })
});

const chart = new Chart(ctx, {
  type: 'line',
  data: {
    labels: chartData.labels,
    datasets: [{
      label: 'Revenus',
      data: chartData.data,
      borderColor: '#3B82F6',
      backgroundColor: 'rgba(59, 130, 246, 0.1)',
      borderWidth: 3,
      fill: true,
      tension: 0.4
    }]
  },
  options: {
    responsive: true,
    plugins: {
      title: {
        display: true,
        text: '📈 Évolution des Revenus'
      },
      legend: {
        display: false
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          callback: value => value.toLocaleString() + '€'
        }
      }
    }
  }
});
```

### CSS Styling pour Dashboard
```css
.dashboard {
  padding: 20px;
  background: #f8fafc;
}

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.metric-card {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  border-left: 4px solid #3B82F6;
}

.metric-value {
  font-size: 2.5rem;
  font-weight: bold;
  color: #1f2937;
}

.metric-variation.positive {
  color: #10b981;
}

.metric-variation.negative {
  color: #ef4444;
}
```

## 📱 Interface Mobile

```
┌─────────────────────┐
│ 📊 Ateliya Stats    │
├─────────────────────┤
│ 📅 30 derniers jours│
├─────────────────────┤
│                     │
│ 💰 Revenus          │
│ 45K€     ↗️ +8.3%   │
│                     │
│ 📦 Commandes        │
│ 150      ↗️ +12.5%  │
│                     │
│ 👥 Clients          │
│ 1,234    ↗️ +15.2%  │
│                     │
│ ┌─────────────────┐ │
│ │ 📈 Voir Graphiques│ │
│ └─────────────────┘ │
│                     │
│ ┌─────────────────┐ │
│ │ 🏆 Top Clients   │ │
│ └─────────────────┘ │
└─────────────────────┘
```

## 🎯 Cas d'Usage Métier

### 👨💼 Manager de Boutique
- **Dashboard quotidien** : Suivi des ventes du jour
- **Analyse hebdomadaire** : Performance de l'équipe
- **Rapport mensuel** : Objectifs et tendances

### 👩💻 Administrateur Système
- **Vue globale** : Performance de toutes les boutiques
- **Comparaisons** : Benchmarking entre succursales
- **Prédictions** : Tendances et projections

### 📊 Analyste Financier
- **Revenus détaillés** : Répartition par type de paiement
- **Clients VIP** : Identification des gros clients
- **ROI** : Retour sur investissement par canal

## 🔄 Mise à Jour

Cette documentation est mise à jour avec les dernières fonctionnalités de l'API Statistiques d'Ateliya.

**Version**: 1.0  
**Dernière mise à jour**: Janvier 2025  
**Prochaine version**: Février 2025 (Statistiques prédictives)