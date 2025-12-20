# Implementation Plan: Repository Standardization

## Overview

This implementation plan fixes the DQL DATE() function syntax error by standardizing date filtering methods across all payment repositories. The approach replaces invalid DATE() function calls with proper date range comparisons while maintaining backward compatibility.

## Tasks

- [ ] 1. Create DateRangeBuilder utility class
  - Create utility class for date range operations
  - Implement dayRange() method for converting single dates to full day ranges
  - Implement formatForDQL() method for proper date string formatting
  - Add timezone handling and validation methods
  - _Requirements: 1.4, 1.5, 4.2, 4.5_

- [ ] 1.1 Write property test for DateRangeBuilder
  - **Property 2: Date Parameter Formatting**
  - **Property 3: Day Range Completeness**
  - **Property 10: Date Precision Preservation**
  - **Validates: Requirements 1.4, 1.5, 4.5**

- [ ] 2. Update PaiementBoutiqueRepository methods
  - Replace DATE() function calls with proper date range comparisons
  - Implement standardized method signatures for enterprise and boutique filtering
  - Add proper parameter binding for security
  - Update sumByEntrepriseAndPeriod, sumByEntrepriseAndDay, countByEntrepriseAndDay methods
  - Update sumByBoutiqueAndPeriod, sumByBoutiqueAndDay, countByBoutiqueAndDay methods
  - _Requirements: 1.1, 1.2, 1.3, 2.1, 2.4, 2.5, 2.6, 2.7_

- [ ] 2.1 Write property test for PaiementBoutiqueRepository
  - **Property 1: DQL Syntax Compliance**
  - **Property 4: Repository Method Standardization**
  - **Property 5: Aggregation Method Completeness**
  - **Validates: Requirements 1.1, 1.2, 1.3, 2.1, 2.4, 2.5, 2.6, 2.7**

- [ ] 3. Update PaiementReservationRepository methods
  - Replace DATE() function calls with proper date range comparisons
  - Implement standardized method signatures matching PaiementBoutiqueRepository
  - Handle joins through reservation entity to access enterprise/boutique
  - Add proper error handling and parameter validation
  - _Requirements: 1.1, 1.2, 1.3, 2.2, 2.4, 2.5, 2.6, 2.7_

- [ ] 3.1 Write property test for PaiementReservationRepository
  - **Property 1: DQL Syntax Compliance**
  - **Property 4: Repository Method Standardization**
  - **Property 7: Calculation Accuracy**
  - **Validates: Requirements 1.1, 1.2, 1.3, 2.2, 2.4, 2.5, 2.6, 2.7**

- [ ] 4. Update PaiementFactureRepository methods
  - Replace DATE() function calls with proper date range comparisons
  - Implement standardized method signatures matching other payment repositories
  - Handle joins through facture entity to access enterprise information
  - Add consistent error handling and null value management
  - _Requirements: 1.1, 1.2, 1.3, 2.3, 2.4, 2.5, 2.6, 2.7_

- [ ] 4.1 Write property test for PaiementFactureRepository
  - **Property 1: DQL Syntax Compliance**
  - **Property 4: Repository Method Standardization**
  - **Property 12: Parameter Binding Security**
  - **Validates: Requirements 1.1, 1.2, 1.3, 2.3, 2.4, 2.5, 2.6, 2.7**

- [ ] 5. Checkpoint - Ensure repository tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 6. Add error handling and validation
  - Implement graceful handling of invalid date parameters
  - Add DateTime parameter validation before query execution
  - Implement proper exception handling for null dates
  - Add meaningful error messages for date formatting failures
  - _Requirements: 5.1, 5.2, 5.3, 5.5_

- [ ] 6.1 Write property test for error handling
  - **Property 11: Error Handling Robustness**
  - **Validates: Requirements 5.1, 5.2, 5.5**

- [ ] 6.2 Write unit tests for error scenarios
  - Test invalid date parameter handling
  - Test null date input scenarios
  - Test error message quality
  - _Requirements: 5.1, 5.2, 5.3, 5.5_

- [ ] 7. Optimize query performance
  - Ensure queries use indexed date columns efficiently
  - Minimize database queries for statistics calculations
  - Select only required fields in aggregation queries
  - Add query result caching where appropriate
  - _Requirements: 6.1, 6.3, 6.4, 6.5_

- [ ] 7.1 Write property test for query efficiency
  - **Property 13: Query Efficiency**
  - **Validates: Requirements 6.3, 6.5**

- [ ] 8. Verify statistics service integration
  - Test that StatistiquesService works with updated repositories
  - Ensure dashboard methods execute without DQL syntax errors
  - Validate that numeric results are returned correctly
  - Test date range calculations across all service methods
  - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

- [ ] 8.1 Write property test for service integration
  - **Property 6: Service Integration Success**
  - **Property 7: Calculation Accuracy**
  - **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5**

- [ ] 9. Ensure backward compatibility
  - Verify existing method signatures are maintained
  - Test that return data formats remain consistent
  - Ensure StatistiquesService requires no modifications
  - Validate dashboard displays same metrics as before
  - Compare calculation results with previous implementation
  - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

- [ ] 9.1 Write property test for backward compatibility
  - **Property 14: Backward Compatibility**
  - **Property 15: Result Equivalence**
  - **Validates: Requirements 7.1, 7.2, 7.3, 7.4, 7.5**

- [ ] 10. Add date consistency validation
  - Ensure date range inclusivity across all repositories
  - Implement consistent timezone handling
  - Validate date boundary treatment consistency
  - Test date precision preservation in conversions
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

- [ ] 10.1 Write property test for date consistency
  - **Property 8: Date Range Inclusivity**
  - **Property 9: Timezone Consistency**
  - **Validates: Requirements 4.1, 4.2, 4.3**

- [ ] 11. Final integration testing
  - Test complete statistics dashboard functionality
  - Verify all date filtering scenarios work correctly
  - Test with various date ranges (daily, monthly, custom)
  - Ensure no DQL syntax errors occur in any scenario
  - Performance test with large datasets
  - _Requirements: All requirements validation_

- [ ] 11.1 Write integration tests
  - Test end-to-end dashboard functionality
  - Test various date range scenarios
  - Test performance with large datasets
  - _Requirements: 3.5, 6.2_

- [ ] 12. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- All tasks are required for comprehensive implementation
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation
- Property tests validate universal correctness properties
- Unit tests validate specific examples and edge cases
- The implementation maintains backward compatibility while fixing DQL syntax issues