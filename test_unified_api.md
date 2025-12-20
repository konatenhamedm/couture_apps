# Test API Statistique Ateliya Unifiée

## Endpoint Unifié
`POST /api/statistique/ateliya/{type}/{id}`

### Paramètres
- `type`: "boutique" ou "succursale"
- `id`: ID de l'entité (boutique ou succursale)

### Payload (optionnel)
```json
{
  "dateDebut": "2025-01-01",
  "dateFin": "2025-01-31",
  "filtre": "mois",
  "valeur": "2025-01"
}
```

## Tests

### Test 1: Boutique
```bash
curl -X POST http://localhost/api/statistique/ateliya/boutique/1 \
  -H "Content-Type: application/json" \
  -d '{
    "filtre": "mois",
    "valeur": "2025-01"
  }'
```

### Test 2: Succursale
```bash
curl -X POST http://localhost/api/statistique/ateliya/succursale/1 \
  -H "Content-Type: application/json" \
  -d '{
    "filtre": "mois", 
    "valeur": "2025-01"
  }'
```

### Test 3: Type invalide (doit retourner erreur 400)
```bash
curl -X POST http://localhost/api/statistique/ateliya/magasin/1 \
  -H "Content-Type: application/json" \
  -d '{}'
```

## Structure de Réponse Unifiée

```json
{
  "success": true,
  "data": {
    "entity_type": "boutique|succursale",
    "entity_id": 1,
    "entity_nom": "Nom de l'entité",
    "periode": {
      "debut": "2025-01-01",
      "fin": "2025-01-31", 
      "nbJours": 31
    },
    "kpis": {
      "chiffreAffaires": 1850000,
      "reservationsActives": 24,
      "clientsActifs": 89,
      "commandesEnCours": 12
    },
    "revenusQuotidiens": [...],
    "revenusParType": [...],
    "activites": [...],
    "dernieresTransactions": [...]
  }
}
```

## Changements Apportés

1. **Endpoint unifié**: `/api/statistique/ateliya/{type}/{id}` remplace les deux endpoints séparés
2. **Réponse générique**: 
   - `entity_type`, `entity_id`, `entity_nom` au lieu de `boutique_id`/`succursale_id`
   - `activites` au lieu de `activitesBoutique`/`activitesSuccursale`
3. **Validation du type**: Seuls "boutique" et "succursale" sont acceptés
4. **Suppression**: Ancien endpoint `/api/statistique/ateliya/succursale/{id}` supprimé

## Migration

Les clients doivent migrer de:
- `POST /api/statistique/ateliya/boutique/{id}` → `POST /api/statistique/ateliya/boutique/{id}`
- `POST /api/statistique/ateliya/succursale/{id}` → `POST /api/statistique/ateliya/succursale/{id}`

Et adapter le parsing de la réponse pour utiliser les nouveaux noms de champs génériques.