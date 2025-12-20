# Design Document

## Overview

This design addresses the DQL syntax error in the statistics dashboard by replacing invalid `DATE()` function calls with proper date range comparisons. The solution standardizes date filtering methods across all payment repositories while maintaining backward compatibility and ensuring optimal performance.

The core issue is that Doctrine DQL does not support the MySQL `DATE()` function. Instead of using `DATE(field) = DATE(:param)`, we need to use proper date range comparisons like `field >= :start AND field <= :end`.

## Architecture

### Current Architecture Issues
- Repository methods use `DATE()` function in DQL queries
- Inconsistent date filtering approaches across repositories
- Direct dependency on MySQL-specific functions
- Potential for SQL injection through string concatenation

### Proposed Architecture
```
┌─────────────────────┐    ┌──────────────────────┐    ┌─────────────────────┐
│  Statistics Service │───▶│  Standardized Repos  │───▶│   Database Layer    │
└─────────────────────┘    └──────────────────────┘    └─────────────────────┘
                                      │
                                      ▼
                           ┌──────────────────────┐
                           │  Date Range Builder  │
                           └──────────────────────┘
```

### Key Components
1. **Date Range Builder**: Utility for converting single dates to proper date ranges
2. **Standardized Repository Methods**: Consistent method signatures across all payment repositories
3. **Parameter Binding**: Secure parameter handling for date values
4. **Query Optimization**: Efficient DQL queries using proper date comparisons

## Components and Interfaces

### DateRangeBuilder Utility
```php
class DateRangeBuilder
{
    public static function dayRange(DateTime $date): array
    {
        $start = clone $date;
        $start->setTime(0, 0, 0);
        
        $end = clone $date;
        $end->setTime(23, 59, 59);
        
        return [$start, $end];
    }
    
    public static function formatForDQL(DateTime $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}
```

### Repository Interface Standardization
```php
interface PaymentRepositoryInterface
{
    public function sumByEntrepriseAndPeriod($entreprise, DateTime $dateDebut, DateTime $dateFin): float;
    public function sumByEntrepriseAndDay($entreprise, DateTime $date): float;
    public function countByEntrepriseAndDay($entreprise, DateTime $date): int;
    public function sumByBoutiqueAndPeriod($boutique, DateTime $dateDebut, DateTime $dateFin): float;
    public function sumByBoutiqueAndDay($boutique, DateTime $date): float;
    public function countByBoutiqueAndDay($boutique, DateTime $date): int;
}
```

### Updated Repository Methods
Each payment repository will implement standardized methods:

#### PaiementBoutiqueRepository
- `sumByEntrepriseAndPeriod()`: Sum payments for enterprise within date range
- `sumByEntrepriseAndDay()`: Sum payments for enterprise on specific day
- `countByEntrepriseAndDay()`: Count payments for enterprise on specific day
- `sumByBoutiqueAndPeriod()`: Sum payments for boutique within date range
- `sumByBoutiqueAndDay()`: Sum payments for boutique on specific day
- `countByBoutiqueAndDay()`: Count payments for boutique on specific day

#### PaiementReservationRepository
- Same method signatures as PaiementBoutiqueRepository
- Joins through reservation entity to access enterprise/boutique

#### PaiementFactureRepository
- Same method signatures adapted for facture entity relationships
- Joins through facture entity to access enterprise information

## Data Models

### Date Range Handling
```php
// Current problematic approach
->andWhere('DATE(pb.createdAt) = DATE(:date)')

// New standardized approach
->andWhere('pb.createdAt >= :dateStart')
->andWhere('pb.createdAt <= :dateEnd')
```

### Parameter Binding Strategy
```php
// Secure parameter binding
$qb->setParameter('dateStart', $dateStart->format('Y-m-d H:i:s'))
   ->setParameter('dateEnd', $dateEnd->format('Y-m-d H:i:s'));
```

### Query Structure Template
```php
public function sumByEntrepriseAndDay($entreprise, DateTime $date): float
{
    [$dateStart, $dateEnd] = DateRangeBuilder::dayRange($date);
    
    return $this->createQueryBuilder('p')
        ->select('COALESCE(SUM(p.montant), 0)')
        ->leftJoin('p.relatedEntity', 'r')
        ->where('r.entreprise = :entreprise')
        ->andWhere('p.createdAt >= :dateStart')
        ->andWhere('p.createdAt <= :dateEnd')
        ->setParameter('entreprise', $entreprise)
        ->setParameter('dateStart', DateRangeBuilder::formatForDQL($dateStart))
        ->setParameter('dateEnd', DateRangeBuilder::formatForDQL($dateEnd))
        ->getQuery()
        ->getSingleScalarResult() ?? 0.0;
}
```

## Error Handling

### Date Validation
- Validate DateTime parameters before query execution
- Handle null dates gracefully with default values
- Provide meaningful error messages for invalid date ranges

### Query Error Recovery
- Use COALESCE to handle null aggregation results
- Implement fallback values for failed queries
- Log query errors for debugging purposes

### Exception Handling Strategy
```php
try {
    $result = $query->getSingleScalarResult();
    return $result ?? 0.0;
} catch (NoResultException $e) {
    return 0.0;
} catch (NonUniqueResultException $e) {
    throw new RepositoryException('Multiple results found for scalar query');
}
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: DQL Syntax Compliance
*For any* repository method that filters by date, executing the generated DQL query should complete successfully without throwing syntax errors related to unknown functions
**Validates: Requirements 1.1, 1.2, 1.3**

### Property 2: Date Parameter Formatting
*For any* DateTime object passed to repository methods, the formatted date string should match the 'Y-m-d H:i:s' pattern required for DQL compatibility
**Validates: Requirements 1.4**

### Property 3: Day Range Completeness
*For any* date used in day filtering, the generated date range should span exactly from 00:00:00 to 23:59:59 of that specific day
**Validates: Requirements 1.5, 4.4**

### Property 4: Repository Method Standardization
*For any* payment repository class (PaiementBoutique, PaiementReservation, PaiementFacture), all required standardized methods should exist and accept the correct parameter types
**Validates: Requirements 2.1, 2.2, 2.3, 2.4, 2.5, 2.7**

### Property 5: Aggregation Method Completeness
*For any* filtering scenario in payment repositories, both sum and count methods should be available and return appropriate numeric types
**Validates: Requirements 2.6**

### Property 6: Service Integration Success
*For any* statistics service method call, the underlying repository queries should execute successfully and return valid numeric results
**Validates: Requirements 3.1, 3.2, 3.3**

### Property 7: Calculation Accuracy
*For any* date range and enterprise/boutique combination, the aggregated payment data should accurately reflect the sum and count of transactions within the specified period
**Validates: Requirements 3.4, 3.5**

### Property 8: Date Range Inclusivity
*For any* date range filter, all transactions with timestamps within the specified boundaries should be included in the results
**Validates: Requirements 4.1, 4.3**

### Property 9: Timezone Consistency
*For any* date comparison operation, the system should handle timezone considerations consistently across all repository methods
**Validates: Requirements 4.2**

### Property 10: Date Precision Preservation
*For any* DateTime object converted to string format and back, the precision should be preserved without data loss
**Validates: Requirements 4.5**

### Property 11: Error Handling Robustness
*For any* invalid date parameter or null date input, repository methods should handle them gracefully without throwing unhandled exceptions
**Validates: Requirements 5.1, 5.2, 5.5**

### Property 12: Parameter Binding Security
*For any* repository query with date parameters, the parameters should be properly bound rather than concatenated into the query string
**Validates: Requirements 5.4**

### Property 13: Query Efficiency
*For any* statistics calculation operation, the system should minimize the number of database queries and select only required fields
**Validates: Requirements 6.3, 6.5**

### Property 14: Backward Compatibility
*For any* existing method call to updated repositories, the method signature and return data format should remain compatible with the previous implementation
**Validates: Requirements 7.1, 7.2, 7.3**

### Property 15: Result Equivalence
*For any* statistics calculation, the updated repository implementation should produce equivalent results to the previous implementation for the same input data
**Validates: Requirements 7.4, 7.5**

## Testing Strategy

### Unit Testing Approach
- Test each repository method with various date ranges
- Verify correct DQL generation without DATE() function
- Test parameter binding security
- Validate aggregation results accuracy

### Integration Testing
- Test statistics service with updated repositories
- Verify dashboard functionality end-to-end
- Test with real database data
- Performance testing for large datasets

### Property-Based Testing Configuration
- **Minimum 100 iterations per property test** (due to randomization)
- Each property test must reference its design document property
- **Tag format**: Feature: repository-standardization, Property {number}: {property_text}
- **Dual Testing Approach**: Both unit tests and property tests are required
- **Unit tests focus on**: Specific examples, edge cases, error conditions
- **Property tests focus on**: Universal properties across all inputs through randomization