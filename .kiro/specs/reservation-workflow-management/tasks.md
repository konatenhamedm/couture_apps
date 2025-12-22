# Implementation Plan: Reservation Workflow Management

## Overview

Ce plan d'implémentation détaille les étapes pour développer le système de gestion des réservations avec workflow de confirmation/annulation. L'approche est incrémentale, permettant de valider chaque composant avant de passer au suivant. Le plan privilégie la sécurité des données et la cohérence transactionnelle.

## Tasks

- [x] 1. Mise à jour du modèle de données et migration
  - Ajouter les nouveaux champs à l'entité Reservation (status, confirmedAt, confirmedBy, cancelledAt, cancelledBy, cancellationReason)
  - Créer l'énumération ReservationStatus avec les valeurs (en_attente, confirmee, annulee)
  - Créer l'entité ReservationStatusHistory pour l'audit trail
  - Générer et exécuter la migration Doctrine
  - _Requirements: 1.1, 1.2, 1.5, 8.1, 8.2, 8.3_

- [x] 1.1 Écrire les tests de propriété pour le modèle de données
  - **Property 1: Reservation Status Initialization**
  - **Property 2: Status Field Validation**
  - **Validates: Requirements 1.1, 1.2**

- [x] 2. Modification du service de création de réservation
  - Modifier ReservationController::create() pour ne plus déduire le stock
  - Supprimer les appels à setQuantite() sur ModeleBoutique et Modele
  - Conserver la validation de disponibilité du stock sans modification
  - Maintenir l'enregistrement des paiements d'acompte
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.6_

- [x] 2.1 Écrire les tests de propriété pour la création sans déduction
  - **Property 3: Stock Preservation During Creation**
  - **Property 4: Stock Availability Validation**
  - **Property 5: Payment Independence from Stock**
  - **Validates: Requirements 2.1, 2.2, 2.3, 2.4, 2.6**

- [x] 3. Checkpoint - Vérifier que les réservations se créent sans affecter le stock
  - Ensure all tests pass, ask the user if questions arise.

- [x] 4. Création du service de workflow des réservations
  - Créer ReservationWorkflowService avec les méthodes confirmReservation() et cancelReservation()
  - Implémenter la validation des transitions d'état
  - Intégrer la gestion des transactions Doctrine pour l'atomicité
  - Ajouter la création d'audit trail pour chaque changement d'état
  - _Requirements: 3.1, 3.4, 3.8, 4.1, 4.3, 9.1_

- [x] 4.1 Écrire les tests de propriété pour les transitions d'état
  - **Property 7: Confirmation Status Transition**
  - **Property 9: Status-Based Operation Validation**
  - **Property 11: Audit Trail Completeness**
  - **Validates: Requirements 3.4, 3.8, 3.9, 4.1, 4.3, 4.4, 4.5**

- [x] 5. Implémentation de la logique de confirmation
  - Créer StockValidator pour valider et réserver le stock
  - Implémenter la méthode confirmReservation() avec déduction atomique du stock
  - Ajouter la gestion des erreurs pour stock insuffisant
  - Intégrer le système de notifications pour les confirmations
  - _Requirements: 3.1, 3.2, 3.3, 3.5, 3.6, 3.7, 3.10_

- [x] 5.1 Écrire les tests de propriété pour la confirmation
  - **Property 6: Confirmation Stock Deduction**
  - **Property 8: Confirmation Failure Atomicity**
  - **Property 20: Transaction Atomicity**
  - **Property 22: Aggregate Stock Validation**
  - **Validates: Requirements 3.1, 3.2, 3.3, 3.5, 3.6, 3.7, 9.1, 9.4, 9.5**

- [x] 6. Implémentation de la logique d'annulation
  - Implémenter la méthode cancelReservation() sans modification du stock
  - Ajouter la validation des statuts autorisés pour l'annulation
  - Intégrer le système de notifications pour les annulations
  - Maintenir les enregistrements de paiement pour la comptabilité
  - _Requirements: 4.1, 4.2, 4.4, 4.5, 4.6, 4.7_

- [x] 6.1 Écrire les tests de propriété pour l'annulation
  - **Property 10: Cancellation Stock Preservation**
  - **Property 23: Notification Delivery**
  - **Validates: Requirements 4.2, 4.6, 4.7, 10.1, 10.2**

- [x] 7. Checkpoint - Vérifier les services de workflow
  - Ensure all tests pass, ask the user if questions arise.

- [x] 8. Création des nouveaux endpoints API
  - Ajouter POST /api/reservation/confirm/{id} dans ApiReservationController
  - Ajouter POST /api/reservation/cancel/{id} dans ApiReservationController
  - Implémenter la validation d'authentification et d'autorisation
  - Ajouter la gestion d'erreurs avec codes HTTP appropriés
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 6.1, 6.2, 6.3, 6.4, 6.5, 6.6_

- [x] 8.1 Écrire les tests de propriété pour les endpoints
  - **Property 13: Authentication Enforcement**
  - **Property 14: Invalid ID Error Handling**
  - **Validates: Requirements 5.3, 5.4, 6.3, 6.4**

- [x] 9. Ajout du filtrage par statut
  - Modifier les méthodes index() et indexAll() pour supporter le paramètre status
  - Implémenter la validation des valeurs de statut
  - Ajouter le support de filtres multiples
  - Maintenir la compatibilité avec les appels existants
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

- [x] 9.1 Écrire les tests de propriété pour le filtrage
  - **Property 15: Status Filtering Accuracy**
  - **Property 16: Default Filtering Behavior**
  - **Property 17: Filter Validation**
  - **Validates: Requirements 7.1, 7.2, 7.3, 7.4, 7.5**

- [x] 10. Mise à jour des réponses API
  - Modifier les groupes de sérialisation pour inclure les nouveaux champs
  - Mettre à jour la documentation OpenAPI avec les nouveaux endpoints
  - Ajouter les nouveaux champs dans les réponses JSON existantes
  - Tester la compatibilité avec les clients existants
  - _Requirements: 1.4, 5.7, 6.7_

- [x] 10.1 Écrire les tests unitaires pour les réponses API
  - **Property 12: API Response Consistency**
  - **Validates: Requirements 1.4**

- [ ] 11. Gestion de la concurrence
  - Implémenter le verrouillage optimiste pour les réservations
  - Ajouter la gestion des conflits de confirmation simultanée
  - Tester les scénarios de concurrence avec des utilisateurs multiples
  - Ajouter les logs appropriés pour le debugging
  - _Requirements: 9.3_

- [ ] 11.1 Écrire les tests de propriété pour la concurrence
  - **Property 21: Concurrency Control**
  - **Validates: Requirements 9.3**

- [ ] 12. Migration des données existantes
  - Créer le script de migration pour les réservations existantes
  - Implémenter la logique idempotente pour les exécutions multiples
  - Ajouter les logs d'audit pour toutes les activités de migration
  - Tester la migration sur un jeu de données de test
  - _Requirements: 8.2, 8.3, 8.4, 8.5_

- [ ] 12.1 Écrire les tests de propriété pour la migration
  - **Property 18: Migration Data Consistency**
  - **Property 19: Migration Idempotency**
  - **Validates: Requirements 8.2, 8.3, 8.4**

- [ ] 13. Checkpoint final - Tests d'intégration complets
  - Exécuter tous les tests de propriété (minimum 100 itérations chacun)
  - Valider le workflow complet : création → confirmation → vérification du stock
  - Valider le workflow d'annulation : création → annulation → vérification du stock
  - Tester les scénarios d'erreur et la gestion des exceptions
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 14. Documentation et finalisation
  - Mettre à jour la documentation API avec les nouveaux endpoints
  - Créer des exemples d'utilisation pour les développeurs frontend
  - Documenter les codes d'erreur et leurs significations
  - Préparer les notes de release pour les utilisateurs finaux
  - _Requirements: 5.7, 6.7_

## Notes

- Toutes les tâches sont obligatoires pour une implémentation complète et robuste
- Chaque tâche référence les exigences spécifiques pour la traçabilité
- Les checkpoints permettent une validation incrémentale
- Les tests de propriété valident les propriétés de correction universelles
- Les tests unitaires valident des exemples spécifiques et les cas limites
- L'approche transactionnelle garantit la cohérence des données
- La migration préserve les données existantes tout en ajoutant les nouvelles fonctionnalités