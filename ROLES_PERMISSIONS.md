# 🔐 Gestion des Rôles et Permissions - Ateliya

## 👥 Types d'Utilisateurs

### **SADM** - Super Administrateur
- **Libellé** : Super Administrateur
- **Portée** : Entreprise complète
- **Accès** : Toutes fonctionnalités sans restriction

#### Permissions Spécifiques
- ✅ Gestion multi-boutiques/succursales
- ✅ Configuration système globale
- ✅ Gestion des abonnements
- ✅ Export données complètes
- ✅ Paramètres API & intégrations
- ✅ Templates notifications
- ✅ Import clients en masse
- ✅ Gestion fournisseurs

---

### **ADB** - Gérant Boutique
- **Libellé** : Gérant boutique
- **Portée** : Sa boutique uniquement
- **Accès** : Gestion complète boutique

#### Permissions Spécifiques
- ✅ Réservations vêtements
- ✅ Ventes boutique (simple/multiple)
- ✅ Gestion stock boutique
- ✅ Catalogue vêtements
- ✅ Caisses boutique
- ✅ Clients et mesures
- ✅ Employés boutique
- ❌ Factures clients (pas de succursale)
- ❌ Gestion succursales
- ❌ Abonnements système

---

### **ADS** - Gérant Succursale
- **Libellé** : Gérant succursale
- **Portée** : Sa succursale uniquement
- **Accès** : Gestion succursale + clients

#### Permissions Spécifiques
- ✅ Factures clients
- ✅ Paiements factures
- ✅ Caisses succursale
- ✅ Clients et mesures
- ✅ Réservations (consultation)
- ❌ Ventes boutique
- ❌ Gestion stocks
- ❌ Catalogue vêtements
- ❌ Gestion boutiques

---

### **ADSB** - Gérant Succursale et Boutique
- **Libellé** : Gérant succursale et boutique
- **Portée** : Sa succursale + sa boutique
- **Accès** : Combinaison ADS + ADB

#### Permissions Spécifiques
- ✅ **Succursale** : Factures, paiements factures, caisses
- ✅ **Boutique** : Réservations, ventes, stocks, catalogue
- ✅ Clients et mesures (complet)
- ✅ Employés boutique
- ✅ Toutes caisses (boutique + succursale)
- ❌ Gestion multi-établissements
- ❌ Abonnements système

## 🎯 Matrice des Permissions

| Fonctionnalité | SADM | ADB | ADS | ADSB |
|----------------|------|-----|-----|------|
| **Dashboard** | ✅ | ✅ | ✅ | ✅ |
| **Clients** | ✅ | ✅ | ✅ | ✅ |
| - Import clients | ✅ | ❌ | ❌ | ❌ |
| **Mesures** | ✅ | ✅ | ✅ | ✅ |
| - Config catégories/types | ✅ | ✅ | ❌ | ✅ |
| **Réservations** | ✅ | ✅ | 👁️ | ✅ |
| - Créer réservation | ✅ | ✅ | ❌ | ✅ |
| - Paiements acomptes | ✅ | ✅ | ❌ | ✅ |
| **Finances** | ✅ | 📊 | ✅ | ✅ |
| - Factures | ✅ | ❌ | ✅ | ✅ |
| - Ventes boutique | ✅ | ✅ | ❌ | ✅ |
| - Abonnements | ✅ | ❌ | ❌ | ❌ |
| **Stocks** | ✅ | ✅ | ❌ | ✅ |
| - Fournisseurs | ✅ | ❌ | ❌ | ❌ |
| **Boutique** | ✅ | ✅ | ❌ | ✅ |
| - Succursales | ✅ | ❌ | ❌ | ❌ |
| - Employés | ✅ | ✅ | ❌ | ✅ |
| **Statistiques** | ✅ | ✅ | ✅ | ✅ |
| - Performance globale | ✅ | ❌ | ❌ | ❌ |
| - Export données | ✅ | ❌ | ❌ | ❌ |
| **Notifications** | ✅ | ✅ | ✅ | ✅ |
| - Templates email | ✅ | ❌ | ❌ | ❌ |
| **Paramètres** | ✅ | ✅ | ✅ | ✅ |
| - API & Intégrations | ✅ | ❌ | ❌ | ❌ |
| - Sauvegarde | ✅ | ❌ | ❌ | ❌ |

**Légende** :
- ✅ Accès complet
- 👁️ Consultation uniquement
- 📊 Accès limité à ses données
- ❌ Pas d'accès

## 🏢 Logique de Filtrage des Données

### **SADM** - Vue Entreprise
```php
// Voit TOUTES les données de l'entreprise
$reservations = $repository->findBy(['entreprise' => $user->getEntreprise()]);
$paiements = $repository->findBy(['entreprise' => $user->getEntreprise()]);
```

### **ADB** - Vue Boutique
```php
// Voit uniquement SA boutique
$reservations = $repository->findBy(['boutique' => $user->getBoutique()]);
$ventes = $repository->findBy(['boutique' => $user->getBoutique()]);
$stocks = $repository->findBy(['boutique' => $user->getBoutique()]);
```

### **ADS** - Vue Succursale
```php
// Voit uniquement SA succursale
$factures = $repository->findBy(['surccursale' => $user->getSurccursale()]);
$paiements = $repository->findBy(['surccursale' => $user->getSurccursale()]);
$clients = $repository->findBy(['surccursale' => $user->getSurccursale()]);
```

### **ADSB** - Vue Mixte
```php
// Voit SA succursale ET SA boutique
$factures = $repository->findBy(['surccursale' => $user->getSurccursale()]);
$reservations = $repository->findBy(['boutique' => $user->getBoutique()]);
$ventes = $repository->findBy(['boutique' => $user->getBoutique()]);
```

## 🎨 Interface Utilisateur par Rôle

### **Menu SADM** (Complet)
```
🏠 Dashboard
👥 Clients (+ Import)
📏 Mesures (+ Config)
📅 Réservations (Toutes)
💰 Finances (Complètes)
📦 Stocks (+ Fournisseurs)
🏪 Boutique (+ Succursales)
📊 Statistiques (+ Performance)
🔔 Notifications (+ Templates)
⚙️ Paramètres (+ API)
```

### **Menu ADB** (Boutique)
```
🏠 Dashboard
👥 Clients
📏 Mesures (+ Config)
📅 Réservations
💰 Finances (Ventes uniquement)
📦 Stocks
🏪 Boutique (Sans succursales)
📊 Statistiques
🔔 Notifications
⚙️ Paramètres (Basiques)
```

### **Menu ADS** (Succursale)
```
🏠 Dashboard
👥 Clients
📏 Mesures
📅 Réservations (Consultation)
💰 Finances (Factures uniquement)
📊 Statistiques
🔔 Notifications
⚙️ Paramètres (Basiques)
```

### **Menu ADSB** (Mixte)
```
🏠 Dashboard
👥 Clients
📏 Mesures (+ Config)
📅 Réservations
💰 Finances (Factures + Ventes)
📦 Stocks
🏪 Boutique (Sans succursales)
📊 Statistiques
🔔 Notifications
⚙️ Paramètres (Basiques)
```

## 🔒 Contrôles de Sécurité

### **Middleware de Vérification**
```php
// Vérification rôle + portée
if (!$this->security->isGranted($requiredRole, $resource)) {
    throw new AccessDeniedException();
}

// Vérification appartenance ressource
if ($resource->getBoutique() !== $user->getBoutique()) {
    throw new AccessDeniedException();
}
```

### **Annotations de Sécurité**
```php
#[IsGranted('ROLE_ADB')]
#[Security("user.getBoutique() === boutique")]
public function createReservation(Boutique $boutique) {}

#[IsGranted('ROLE_ADS')]
#[Security("user.getSurccursale() === facture.getSurccursale()")]
public function payFacture(Facture $facture) {}
```

Cette structure garantit une **sécurité granulaire** et une **expérience utilisateur adaptée** à chaque type d'utilisateur.