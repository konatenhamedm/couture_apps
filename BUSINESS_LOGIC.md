# 🧵 Logique Métier Ateliya - Système de Réservations et Paiements

## 📋 Vue d'ensemble du système

Ateliya gère **3 types de transactions principales** :

### 1. 🎯 **Réservations de Vêtements** (`PaiementReservation`)
- Client réserve des vêtements avec **acompte**
- Stock **bloqué** jusqu'au retrait
- **Paiements échelonnés** possibles
- **Date de retrait** programmée

### 2. 🛒 **Ventes Directes Boutique** (`PaiementBoutique`)
- Vente **immédiate** en boutique
- Stock **réduit** instantanément
- Paiement **complet** à la vente
- **Simple** ou **multiple** produits

### 3. 📄 **Factures Clients** (`PaiementFacture`)
- Facturation **services/produits**
- Paiements **acomptes/soldes**
- **Suivi** reste à payer
- **Relances** automatiques

## 🔄 Workflows Détaillés

### 📅 Workflow Réservation

```
1. SÉLECTION VÊTEMENTS
   ├─ Client choisit modèles
   ├─ Vérification stock disponible
   └─ Calcul montant total

2. CRÉATION RÉSERVATION
   ├─ Saisie acompte (30-50% recommandé)
   ├─ Date retrait programmée
   ├─ Validation données
   └─ Stock BLOQUÉ (quantité réduite)

3. PAIEMENT ACOMPTE
   ├─ Création PaiementReservation
   ├─ Mise à jour CaisseBoutique
   ├─ Notification client/admin
   └─ Référence générée

4. PAIEMENTS COMPLÉMENTAIRES (optionnel)
   ├─ Paiements échelonnés
   ├─ Mise à jour reste à payer
   └─ Suivi solde

5. RETRAIT FINAL
   ├─ Vérification solde = 0
   ├─ Remise vêtements
   └─ Clôture réservation
```

### 🛒 Workflow Vente Boutique

```
1. SÉLECTION PRODUITS
   ├─ Choix modèles disponibles
   ├─ Vérification stock temps réel
   └─ Calcul total

2. VALIDATION VENTE
   ├─ Contrôle stock suffisant
   ├─ Vérification appartenance boutique
   └─ Validation montant

3. TRANSACTION ATOMIQUE
   ├─ Création PaiementBoutique
   ├─ Réduction stock ModeleBoutique
   ├─ Réduction quantité globale Modele
   ├─ Mise à jour CaisseBoutique
   └─ Création lignes détaillées

4. FINALISATION
   ├─ Génération référence
   ├─ Notifications
   └─ Historique vente
```

### 📄 Workflow Facture

```
1. CRÉATION FACTURE
   ├─ Sélection client
   ├─ Ajout services/produits
   ├─ Calcul montant total
   └─ Génération PDF

2. PAIEMENTS ÉCHELONNÉS
   ├─ Acompte initial (optionnel)
   ├─ Paiements intermédiaires
   ├─ Mise à jour reste à payer
   └─ Mise à jour CaisseSuccursale

3. SUIVI & RELANCES
   ├─ Monitoring échéances
   ├─ Relances automatiques
   └─ Notifications admin
```

## 🗄️ Structure Base de Données

### Entités Principales

#### **Reservation**
```php
- id: int
- montant: float (total réservation)
- avance: float (acomptes versés)
- reste: float (reste à payer)
- dateRetrait: DateTime
- client: Client
- boutique: Boutique
- ligneReservations: LigneReservation[]
- paiements: PaiementReservation[]
```

#### **PaiementReservation** (hérite de Paiement)
```php
- reservation: Reservation
- montant: float
- reference: string
- type: "paiementReservation"
```

#### **PaiementBoutique** (hérite de Paiement)
```php
- boutique: Boutique
- client: Client (optionnel)
- quantite: int (total articles)
- lignes: PaiementBoutiqueLigne[]
- type: "paiementBoutique"
```

#### **PaiementFacture** (hérite de Paiement)
```php
- facture: Facture
- montant: float
- type: "paiementFacture"
```

### Relations Clés

```
Boutique 1---* ModeleBoutique (stock par boutique)
ModeleBoutique *---1 Modele (référence globale)
Reservation 1---* LigneReservation
LigneReservation *---1 ModeleBoutique
PaiementBoutique 1---* PaiementBoutiqueLigne
PaiementBoutiqueLigne *---1 ModeleBoutique
```

## 💰 Gestion des Caisses

### **CaisseBoutique**
- Alimentée par : `PaiementReservation` + `PaiementBoutique`
- Utilisée pour : Réservations et ventes boutique
- Mise à jour : Automatique à chaque transaction

### **CaisseSuccursale**
- Alimentée par : `PaiementFacture`
- Utilisée pour : Paiements factures clients
- Mise à jour : Automatique à chaque paiement facture

## 📊 Gestion des Stocks

### **Stock Réservation**
```php
// LORS DE LA RÉSERVATION
$modeleBoutique->setQuantite($stock - $quantiteReservee); // Stock bloqué
$modele->setQuantiteGlobale($global - $quantiteReservee); // Cohérence globale
```

### **Stock Vente**
```php
// LORS DE LA VENTE
$modeleBoutique->setQuantite($stock - $quantiteVendue); // Stock réduit
$modele->setQuantiteGlobale($global - $quantiteVendue); // Cohérence globale
```

### **Règles de Gestion**
- ✅ **Réservation** = Stock bloqué (pas disponible pour vente)
- ✅ **Vente** = Stock définitivement réduit
- ✅ **Annulation réservation** = Stock libéré
- ✅ **Contrôles** avant toute transaction

## 🔐 Sécurité & Validations

### **Validations Réservation**
```php
// Vérification stock disponible
if ($modeleBoutique->getQuantite() < $quantiteDemandee) {
    throw new Exception("Stock insuffisant");
}

// Cohérence montants
if ($avance + $reste !== $montantTotal) {
    throw new Exception("Incohérence montants");
}

// Date retrait future
if ($dateRetrait < new DateTime()) {
    throw new Exception("Date retrait invalide");
}
```

### **Validations Vente**
```php
// Stock suffisant
if ($modeleBoutique->getQuantite() < $quantite) {
    throw new Exception("Stock insuffisant");
}

// Appartenance boutique
if ($modeleBoutique->getBoutique() !== $boutique) {
    throw new Exception("Modèle non disponible dans cette boutique");
}
```

### **Transactions Atomiques**
```php
$entityManager->beginTransaction();
try {
    // Toutes les opérations
    $entityManager->flush();
    $entityManager->commit();
} catch (Exception $e) {
    $entityManager->rollback();
    throw $e;
}
```

## 📈 Métriques Business

### **KPIs Réservations**
- Nombre réservations actives
- Montant total acomptes
- Taux de retrait (réservations honorées)
- Délai moyen retrait
- Réservations en retard

### **KPIs Ventes**
- CA ventes directes
- Nombre transactions
- Panier moyen
- Rotation stock
- Produits les plus vendus

### **KPIs Financiers**
- Revenus par type (réservations/ventes/factures)
- Évolution caisses
- Créances clients
- Taux de recouvrement

## 🚨 Alertes & Notifications

### **Alertes Automatiques**
- Stock critique (< seuil)
- Réservations à retirer aujourd'hui
- Paiements en retard
- Caisses déséquilibrées

### **Notifications**
- Email confirmation réservation
- SMS rappel retrait
- Notification admin nouvelle vente
- Alerte stock épuisé

## 🔄 Intégrations API

### **Endpoints Principaux**
```
POST /api/reservation/create          # Nouvelle réservation
POST /api/reservation/paiement/{id}   # Paiement sur réservation
POST /api/paiement/boutique/{id}      # Vente simple
POST /api/paiement/boutique/multiple/{id} # Vente multiple
POST /api/paiement/facture/{id}       # Paiement facture
```

### **Réponses Standardisées**
```json
{
  "status": "success|error",
  "message": "Description",
  "data": { /* Objet créé/modifié */ }
}
```

Cette logique métier assure la **cohérence**, la **traçabilité** et la **fiabilité** de toutes les transactions dans Ateliya.