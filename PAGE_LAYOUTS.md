# 🎨 Layouts des Pages - Ateliya

## 📋 Structure Standard des Pages de Liste

### **Header de Page**
```
┌─────────────────────────────────────────────────────────────┐
│ 📄 Titre Page                    [🔍 Recherche] [⚙️ Filtres] │
│ ────────────────────────────────────────────────────────── │
│ [➕ Nouveau] [📤 Export] [📥 Import*] [🗑️ Supprimer*]      │
└─────────────────────────────────────────────────────────────┘
```

### **Actions Disponibles par Page**

#### **👥 /clients**
- **Boutons** : `[➕ Nouveau Client]` `[📤 Export CSV]` `[📥 Import CSV]`*
- **Recherche** : Nom, téléphone, email
- **Filtres** : Boutique, succursale, date création
- **Actions ligne** : Voir, Modifier, Mesures, Historique

#### **📅 /reservations**
- **Boutons** : `[➕ Nouvelle Réservation]` `[📤 Export PDF]`
- **Recherche** : Client, référence
- **Filtres** : Statut, boutique, date retrait, montant
- **Actions ligne** : Voir, Modifier, Paiement, Annuler

#### **💰 /finances/factures**
- **Boutons** : `[➕ Nouvelle Facture]` `[📤 Export PDF]`
- **Recherche** : Client, référence, montant
- **Filtres** : Statut paiement, succursale, période
- **Actions ligne** : Voir, Modifier, Paiement, PDF

#### **🛒 /finances/ventes**
- **Boutons** : `[➕ Nouvelle Vente]` `[📤 Export Excel]`
- **Recherche** : Client, référence, produit
- **Filtres** : Boutique, période, montant
- **Actions ligne** : Voir, Détails, Reçu

#### **📦 /stocks/inventaire**
- **Boutons** : `[➕ Nouveau Modèle]` `[📤 Export Stock]` `[📥 Import Stock]`*
- **Recherche** : Nom modèle, référence
- **Filtres** : Boutique, catégorie, stock critique
- **Actions ligne** : Voir, Modifier, Mouvement

#### **📏 /mesures/categories**
- **Boutons** : `[➕ Nouvelle Catégorie]` `[📤 Export]`
- **Recherche** : Nom catégorie
- **Filtres** : Actif/Inactif
- **Actions ligne** : Voir, Modifier, Types

#### **🏪 /boutique/modeles**
- **Boutons** : `[➕ Nouveau Modèle]` `[📤 Export Catalogue]`
- **Recherche** : Nom, référence, prix
- **Filtres** : Disponibilité, prix, catégorie
- **Actions ligne** : Voir, Modifier, Stock

#### **👤 /boutique/employes**
- **Boutons** : `[➕ Nouvel Employé]` `[📤 Export Liste]`
- **Recherche** : Nom, email, rôle
- **Filtres** : Rôle, statut, boutique
- **Actions ligne** : Voir, Modifier, Permissions

*Disponible selon les rôles

## 🎯 Modales et Actions Rapides

### **Modale Nouveau Client**
```
┌─────────────────────────────────────┐
│ ➕ Nouveau Client                   │
│ ─────────────────────────────────── │
│ Nom*: [________________]            │
│ Prénom*: [________________]         │
│ Téléphone*: [________________]      │
│ Email: [________________]           │
│ Adresse: [________________]         │
│                                     │
│ [Annuler] [Créer et Prendre Mesures]│
└─────────────────────────────────────┘
```

### **Modale Nouvelle Réservation**
```
┌─────────────────────────────────────┐
│ 📅 Nouvelle Réservation             │
│ ─────────────────────────────────── │
│ Client*: [Sélectionner ▼]           │
│ Vêtements*: [Ajouter +]             │
│ │ - Robe rouge (2x) - 30,000 FCFA  │
│ │ - Pantalon (1x) - 15,000 FCFA    │
│ Total: 45,000 FCFA                  │
│ Acompte*: [20,000] FCFA             │
│ Date retrait*: [📅 15/02/2025]      │
│                                     │
│ [Annuler] [Créer Réservation]       │
└─────────────────────────────────────┘
```

### **Modale Nouvelle Vente**
```
┌─────────────────────────────────────┐
│ 🛒 Nouvelle Vente                   │
│ ─────────────────────────────────── │
│ Client: [Optionnel ▼]               │
│ Produits*: [Ajouter +]              │
│ │ - Chemise bleue (1x) - 25,000    │
│ │ - Accessoire (2x) - 10,000       │
│ Total: 35,000 FCFA                  │
│ Paiement: [Espèces ▼]               │
│                                     │
│ [Annuler] [Finaliser Vente]         │
└─────────────────────────────────────┘
```

## 📊 Tableaux de Données

### **Structure Standard**
```
┌─────────────────────────────────────────────────────────────┐
│ ☑️ | ID | Nom | Détails | Montant | Date | Statut | Actions │
│ ─────────────────────────────────────────────────────────── │
│ ☑️ | 001| Jean Kouassi | +225 07... | 45,000 | 15/01 | 🟢 | ⚙️│
│ ☑️ | 002| Marie Kone   | +225 05... | 30,000 | 14/01 | 🟡 | ⚙️│
│ ☑️ | 003| Paul Diallo  | +225 01... | 60,000 | 13/01 | 🔴 | ⚙️│
└─────────────────────────────────────────────────────────────┘
│ Affichage 1-10 sur 156 | [◀️ Précédent] [1][2][3] [Suivant ▶️]│
└─────────────────────────────────────────────────────────────┘
```

### **Menu Actions (⚙️)**
```
┌─────────────────┐
│ 👁️ Voir détails │
│ ✏️ Modifier     │
│ 💰 Paiement     │
│ 📄 PDF/Reçu     │
│ ─────────────── │
│ 🗑️ Supprimer    │
└─────────────────┘
```

## 🎨 Codes Couleurs et Statuts

### **Statuts Réservations**
- 🟢 **Confirmée** - Acompte versé, en attente retrait
- 🟡 **Partielle** - Paiements en cours
- 🔵 **Prête** - Solde payé, prête au retrait
- 🔴 **En retard** - Date retrait dépassée
- ⚫ **Annulée** - Réservation annulée

### **Statuts Factures**
- 🟢 **Payée** - Solde = 0
- 🟡 **Partielle** - Acomptes versés
- 🔴 **Impayée** - Aucun paiement
- ⚫ **Annulée** - Facture annulée

### **Statuts Stock**
- 🟢 **Disponible** - Stock > seuil
- 🟡 **Stock faible** - Stock proche du seuil
- 🔴 **Rupture** - Stock = 0
- 🔵 **Réservé** - Stock bloqué par réservations

## 📱 Responsive Design

### **Desktop (>1200px)**
```
┌─────────────────────────────────────────────────────────────┐
│ [Sidebar] │ Header + Actions                                │
│           │ ─────────────────────────────────────────────── │
│ Menu      │ Tableau complet (toutes colonnes)              │
│ complet   │                                                 │
│           │                                                 │
└─────────────────────────────────────────────────────────────┘
```

### **Tablet (768px-1200px)**
```
┌─────────────────────────────────────────────────────────────┐
│ [☰] Header + Actions principales                            │
│ ─────────────────────────────────────────────────────────── │
│ Tableau adaptatif (colonnes essentielles)                  │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### **Mobile (<768px)**
```
┌─────────────────────────────────────┐
│ [☰] Titre [🔍] [➕]                  │
│ ─────────────────────────────────── │
│ 📱 Cards verticales                 │
│ ┌─────────────────────────────────┐ │
│ │ Jean Kouassi                    │ │
│ │ +225 07 12 34 56 78             │ │
│ │ 45,000 FCFA - 15/01/25 🟢       │ │
│ │ [Voir] [Modifier] [⚙️]           │ │
│ └─────────────────────────────────┘ │
└─────────────────────────────────────┘
```

## 🔍 Fonctionnalités Avancées

### **Recherche Intelligente**
- **Recherche globale** dans header
- **Filtres avancés** par colonne
- **Sauvegarde filtres** par utilisateur
- **Recherche temps réel** (debounce 300ms)

### **Export Données**
- **PDF** - Factures, reçus, rapports
- **Excel/CSV** - Listes, statistiques
- **Filtres appliqués** aux exports
- **Formats personnalisables**

### **Actions en Masse**
- **Sélection multiple** avec checkboxes
- **Actions groupées** (supprimer, exporter, modifier statut)
- **Confirmation** pour actions critiques
- **Progress bar** pour opérations longues

Cette structure garantit une **expérience utilisateur cohérente** et **intuitive** sur toutes les pages de l'application.