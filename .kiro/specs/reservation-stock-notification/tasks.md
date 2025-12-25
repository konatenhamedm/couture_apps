# Implementation Plan: Reservation Stock Notification

## Overview

Ce plan d'implémentation détaille les étapes pour modifier l'API de création de réservation afin de permettre la création avec stocks insuffisants tout en notifiant l'administrateur. L'implémentation se fait en PHP/Symfony en préservant entièrement la logique existante.

## Tasks

- [x] 1. Étendre l'enum ReservationStatus
  - Ajouter la nouvelle valeur "en_attente_stock" à l'enum existant
  - Mettre à jour la documentation de l'enum
  - _Requirements: 1.2, 4.2_

- [x] 1.1 Écrire des tests unitaires pour le nouveau statut
  - Tester la validation du nouveau statut
  - Tester les transitions de statut valides
  - _Requirements: 4.3, 4.5_

- [x] 2. Créer la classe StockDeficit pour les données de rupture
  - [x] 2.1 Implémenter la classe StockDeficit
    - Définir les propriétés (modeleName, quantityRequested, quantityAvailable, deficit)
    - Ajouter les méthodes de calcul et validation
    - _Requirements: 1.3, 1.5_

  - [x] 2.2 Écrire des tests de propriété pour StockDeficit
    - **Property 3: Calcul correct des déficits**
    - **Validates: Requirements 1.5**

- [x] 3. Modifier la logique de création de réservation
  - [x] 3.1 Supprimer le blocage sur stock insuffisant
    - Modifier ApiReservationController::create() pour permettre la création
    - Remplacer le return d'erreur par la détection de rupture
    - _Requirements: 1.1, 1.4_

  - [x] 3.2 Implémenter la détection des stocks insuffisants
    - Ajouter la logique de détection des ruptures par article
    - Calculer les déficits pour chaque article en rupture
    - _Requirements: 1.3, 1.5_

  - [x] 3.3 Assigner le statut approprié selon le stock
    - Logique conditionnelle pour "en_attente" vs "en_attente_stock"
    - Préserver le comportement existant pour stock suffisant
    - _Requirements: 1.2, 4.1, 4.2, 5.1_

  - [x] 3.4 Écrire des tests de propriété pour la création de réservation
    - **Property 1: Réservation toujours créée**
    - **Validates: Requirements 1.1, 1.4**

  - [x] 3.5 Écrire des tests de propriété pour l'assignation de statut
    - **Property 2: Statut correct selon stock**
    - **Validates: Requirements 1.2, 4.1, 4.2**

- [x] 4. Checkpoint - Vérifier que la création fonctionne
  - Tester la création avec stock suffisant (comportement inchangé)
  - Tester la création avec stock insuffisant (nouveau comportement)
  - Vérifier que tous les tests passent

- [ ] 5. Étendre les services de notification
  - [x] 5.1 Créer la méthode sendStockAlertNotification
    - Ajouter la méthode au NotificationService existant
    - Implémenter la logique de notification push spécialisée
    - _Requirements: 2.1, 2.5_

  - [x] 5.2 Créer la méthode sendStockAlertEmail
    - Ajouter la méthode au SendMailService existant
    - Implémenter la logique d'email avec template spécialisé
    - _Requirements: 2.2, 3.1, 3.2, 3.3, 3.4, 3.5, 3.6_

  - [x] 5.3 Écrire des tests de propriété pour les notifications
    - **Property 4: Notifications envoyées pour stock insuffisant**
    - **Validates: Requirements 2.1, 2.2**

  - [x] 5.4 Écrire des tests de propriété pour le contenu des emails
    - **Property 5: Contenu complet des emails d'alerte**
    - **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.6**

- [ ] 6. Créer le template d'email pour les alertes de stock
  - [x] 6.1 Créer le template Twig stock_alert_email.html.twig
    - Template avec toutes les informations requises (boutique, client, déficits)
    - Mise en forme claire et professionnelle
    - _Requirements: 2.3, 2.4, 3.1-3.6_

  - [ ] 6.2 Écrire des tests unitaires pour le template
    - Tester le rendu avec différents jeux de données
    - Vérifier la présence de toutes les informations requises
    - _Requirements: 3.1-3.6_

- [ ] 7. Intégrer les notifications dans le contrôleur
  - [x] 7.1 Ajouter l'appel aux notifications de stock
    - Intégrer les appels dans ApiReservationController::create()
    - Logique conditionnelle pour les cas de stock insuffisant
    - _Requirements: 2.1, 2.2, 2.6_

  - [x] 7.2 Implémenter la gestion d'erreurs robuste
    - Try-catch pour les échecs de notification
    - Logging des erreurs sans bloquer la création
    - Système de fallback email → push notification
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

  - [ ] 7.3 Écrire des tests de propriété pour la robustesse
    - **Property 10: Robustesse face aux échecs de notification**
    - **Validates: Requirements 6.1, 6.4**

  - [ ] 7.4 Écrire des tests de propriété pour le logging
    - **Property 11: Logging des erreurs de notification**
    - **Validates: Requirements 6.2, 6.5**

- [ ] 8. Checkpoint - Tester l'intégration complète
  - Tester le flux complet avec stock insuffisant
  - Vérifier l'envoi des notifications (avec mocks)
  - Vérifier la gestion d'erreurs
  - S'assurer que tous les tests passent

- [ ] 9. Préserver la compatibilité existante
  - [ ] 9.1 Vérifier la préservation du comportement existant
    - Tester tous les cas de stock suffisant
    - Vérifier que les notifications standard continuent
    - Valider la gestion des paiements inchangée
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

  - [ ] 9.2 Écrire des tests de propriété pour la compatibilité
    - **Property 8: Préservation comportement existant**
    - **Validates: Requirements 5.1, 5.2, 5.3, 5.4, 5.5**

- [ ] 10. Implémenter les transitions de statut
  - [ ] 10.1 Ajouter la logique de transition "en_attente_stock" → "en_attente"
    - Méthode pour vérifier le stock après ravitaillement
    - Mise à jour automatique du statut si stock suffisant
    - _Requirements: 4.3, 4.4_

  - [ ] 10.2 Écrire des tests de propriété pour les transitions
    - **Property 9: Transitions de statut valides**
    - **Validates: Requirements 4.3, 4.4**

- [ ] 11. Tests d'intégration finaux
  - [ ] 11.1 Écrire des tests d'intégration complets
    - Test du flux complet création → notification → transition
    - Test avec différents scénarios de stock
    - Test de la robustesse face aux erreurs
    - _Requirements: Tous_

  - [ ] 11.2 Écrire des tests de propriété pour les cas multiples
    - **Property 6: Notification push avec résumé**
    - **Validates: Requirements 2.5, 3.7**

  - [ ] 11.3 Écrire des tests de propriété pour les articles multiples
    - **Property 7: Tous articles en rupture listés**
    - **Validates: Requirements 2.6**

  - [ ] 11.4 Écrire des tests de propriété pour le fallback
    - **Property 12: Fallback entre types de notifications**
    - **Validates: Requirements 6.3**

- [ ] 12. Checkpoint final - Validation complète
  - Exécuter tous les tests (unitaires + propriétés + intégration)
  - Vérifier la documentation et les commentaires
  - Valider que tous les requirements sont couverts
  - S'assurer qu'aucune régression n'est introduite

## Notes

- Toutes les tâches sont obligatoires pour une implémentation complète et robuste
- Chaque tâche référence les requirements spécifiques pour la traçabilité
- Les checkpoints permettent une validation incrémentale
- Les tests de propriété valident les propriétés universelles de correction
- Les tests unitaires valident les exemples spécifiques et cas limites