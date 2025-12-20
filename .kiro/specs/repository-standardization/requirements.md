# Requirements Document

## Introduction

This specification addresses the DQL syntax error occurring in the statistics dashboard due to improper use of the `DATE()` function in Doctrine repository queries. The system needs standardized date filtering methods that are compatible with Doctrine DQL and provide consistent behavior across all repositories.

## Glossary

- **DQL**: Doctrine Query Language - the object-oriented query language used by Doctrine ORM
- **Repository**: Data access layer classes that encapsulate database queries for specific entities
- **Date_Filter**: A standardized method for filtering entities by date ranges
- **Statistics_Service**: Service class that aggregates data from multiple repositories for dashboard display
- **Payment_Repository**: Repository classes handling payment-related entities (PaiementBoutique, PaiementReservation, PaiementFacture)

## Requirements

### Requirement 1: DQL Compatibility

**User Story:** As a developer, I want all repository queries to use valid DQL syntax, so that the application doesn't throw syntax errors when executing database queries.

#### Acceptance Criteria

1. WHEN a repository method filters by date, THE Repository SHALL use proper date range comparisons instead of the DATE() function
2. WHEN comparing dates in DQL, THE Repository SHALL use >= and <= operators with properly formatted date strings
3. WHEN executing date-based queries, THE System SHALL not throw "[Syntax Error] Expected known function, got 'DATE'" errors
4. THE Repository SHALL format date parameters as 'Y-m-d H:i:s' strings for DQL compatibility
5. WHEN filtering by day, THE Repository SHALL use date ranges from 00:00:00 to 23:59:59 of the specified day

### Requirement 2: Repository Method Standardization

**User Story:** As a developer, I want consistent method signatures across all payment repositories, so that I can easily maintain and extend the statistics functionality.

#### Acceptance Criteria

1. THE PaiementBoutiqueRepository SHALL implement standardized date filtering methods
2. THE PaiementReservationRepository SHALL implement standardized date filtering methods  
3. THE PaiementFactureRepository SHALL implement standardized date filtering methods
4. WHEN filtering by enterprise and period, THE Repository SHALL accept DateTime objects as parameters
5. WHEN filtering by boutique and period, THE Repository SHALL accept entity objects and DateTime parameters
6. THE Repository SHALL provide both sum and count methods for each filtering scenario
7. WHEN filtering by single day, THE Repository SHALL accept a single DateTime parameter

### Requirement 3: Statistics Service Integration

**User Story:** As a system administrator, I want the statistics dashboard to load without errors, so that I can monitor business performance effectively.

#### Acceptance Criteria

1. WHEN the statistics service calls repository methods, THE System SHALL execute queries successfully
2. WHEN calculating dashboard metrics, THE Statistics_Service SHALL receive valid numeric results from repositories
3. THE Statistics_Service SHALL handle date range calculations without causing DQL syntax errors
4. WHEN aggregating payment data, THE System SHALL return accurate sums and counts for the specified periods
5. THE Dashboard SHALL display statistics for daily, monthly, and custom date ranges without errors

### Requirement 4: Date Handling Consistency

**User Story:** As a business user, I want date-based statistics to be accurate and consistent, so that I can make informed business decisions.

#### Acceptance Criteria

1. WHEN filtering by date range, THE System SHALL include all transactions within the specified period
2. WHEN comparing dates, THE System SHALL handle timezone considerations appropriately
3. THE System SHALL treat date boundaries consistently across all repository methods
4. WHEN filtering by day, THE System SHALL include transactions from 00:00:00 to 23:59:59 of that day
5. THE System SHALL preserve date precision when converting between DateTime objects and string formats

### Requirement 5: Error Prevention and Validation

**User Story:** As a developer, I want robust error handling for date operations, so that the system remains stable under various input conditions.

#### Acceptance Criteria

1. WHEN invalid date parameters are provided, THE Repository SHALL handle them gracefully
2. THE Repository SHALL validate DateTime parameters before using them in queries
3. WHEN date formatting fails, THE System SHALL provide meaningful error messages
4. THE Repository SHALL prevent SQL injection through proper parameter binding
5. WHEN null dates are encountered, THE System SHALL handle them without throwing exceptions

### Requirement 6: Performance Optimization

**User Story:** As a system user, I want statistics queries to execute quickly, so that the dashboard loads in a reasonable time.

#### Acceptance Criteria

1. THE Repository SHALL use indexed date columns for filtering operations
2. WHEN executing date range queries, THE System SHALL optimize query performance through proper indexing
3. THE Repository SHALL minimize the number of database queries needed for statistics calculation
4. WHEN aggregating large datasets, THE System SHALL use efficient SQL aggregation functions
5. THE Repository SHALL avoid unnecessary data loading by selecting only required fields

### Requirement 7: Backward Compatibility

**User Story:** As a system maintainer, I want existing functionality to continue working after the repository updates, so that no features are broken during the fix.

#### Acceptance Criteria

1. THE Updated_Repository SHALL maintain the same method signatures as the current implementation
2. WHEN existing code calls repository methods, THE System SHALL return data in the same format
3. THE Statistics_Service SHALL continue to work with updated repository methods without modification
4. THE Dashboard SHALL display the same metrics after the repository updates
5. WHEN calculating statistics, THE System SHALL produce equivalent results to the previous implementation