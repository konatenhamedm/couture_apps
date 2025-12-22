# Nouvelle Fonctionnalité : Filtres Avancés pour indexAllByBoutique

## Résumé

J'ai créé une nouvelle méthode POST pour `indexAllByBoutique` qui permet d'utiliser des filtres avancés similaires à ceux du dashboard des statistiques.

## Nouvelle Route

**POST** `/api/reservation/entreprise/by/boutique/{id}/advanced`

## Fonctionnalités Ajoutées

### 1. Filtres de Date Avancés (comme dans les statistiques)

```json
{
  "filtre": "mois",
  "valeur": "2025-01"
}
```

**Types de filtres disponibles :**
- `jour` : Filtre par jour spécifique (valeur: "2025-01-30")
- `mois` : Filtre par mois (valeur: "2025-01")
- `annee` : Filtre par année (valeur: "2025")
- `periode` : Période personnalisée avec dateDebut et dateFin

### 2. Filtres Supplémentaires

```json
{
  "status": "en_attente,confirmee",
  "clientId": 5,
  "montantMin": 10000,
  "montantMax": 100000,
  "orderBy": "createdAt",
  "orderDirection": "DESC"
}
```

### 3. Réponse Enrichie

La réponse inclut maintenant :
- Informations sur la boutique
- Période de recherche
- Filtres appliqués
- Statistiques calculées (total réservations, montants)
- Données paginées

## Exemples d'Utilisation

### Exemple 1 : Réservations du mois en cours
```bash
curl -X POST /api/reservation/entreprise/by/boutique/1/advanced \
  -H "Content-Type: application/json" \
  -d '{
    "filtre": "mois",
    "valeur": "2025-01"
  }'
```

### Exemple 2 : Réservations confirmées avec montant minimum
```bash
curl -X POST /api/reservation/entreprise/by/boutique/1/advanced \
  -H "Content-Type: application/json" \
  -d '{
    "filtre": "mois",
    "valeur": "2025-01",
    "status": "confirmee",
    "montantMin": 50000,
    "orderBy": "montant",
    "orderDirection": "DESC"
  }'
```

### Exemple 3 : Période personnalisée pour un client spécifique
```bash
curl -X POST /api/reservation/entreprise/by/boutique/1/advanced \
  -H "Content-Type: application/json" \
  -d '{
    "dateDebut": "2025-01-01",
    "dateFin": "2025-01-31",
    "clientId": 5,
    "status": "en_attente,confirmee"
  }'
```

## Structure de Réponse

```json
{
  "success": true,
  "data": {
    "boutique_id": 1,
    "boutique_nom": "Boutique Centre-ville",
    "periode": {
      "debut": "2025-01-01",
      "fin": "2025-01-31",
      "nbJours": 31
    },
    "filtres_appliques": {
      "status": ["en_attente", "confirmee"],
      "clientId": 5,
      "montantMin": 10000,
      "montantMax": null,
      "orderBy": "createdAt",
      "orderDirection": "DESC"
    },
    "statistiques": {
      "total_reservations": 24,
      "montant_total": 1200000,
      "montant_avances": 480000,
      "montant_reste": 720000
    },
    "reservations": [
      // Données paginées des réservations
    ]
  }
}
```

## Avantages

1. **Cohérence** : Utilise le même système de filtres que les statistiques
2. **Flexibilité** : Multiples options de filtrage combinables
3. **Performance** : Filtrage au niveau de la base de données
4. **Statistiques** : Calculs automatiques des totaux
5. **Rétrocompatibilité** : L'ancienne méthode GET reste disponible

## Fichiers Modifiés

1. **src/Controller/Apis/ApiReservationController.php**
   - Ajout de `indexAllByBoutiqueAdvanced()`
   - Ajout de `parseAdvancedFilters()`
   - Ajout de `calculateReservationStats()`

2. **src/Repository/ReservationRepository.php**
   - Ajout de `findByBoutiqueWithAdvancedFilters()`

## Tests Recommandés

1. Tester chaque type de filtre de date
2. Tester les combinaisons de filtres
3. Tester les cas limites (dates invalides, montants négatifs)
4. Tester la pagination avec les filtres
5. Tester les statistiques calculées

## Prochaines Étapes

Cette fonctionnalité est prête à être utilisée. Elle peut être étendue facilement pour ajouter d'autres filtres si nécessaire (par exemple, filtrage par type de paiement, par statut de livraison, etc.).