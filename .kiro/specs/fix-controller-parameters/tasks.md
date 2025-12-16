# Implementation Plan - Fix Controller Parameters

- [ ] 1. Set up project structure and core interfaces
  - Create directory structure for scanner, fixer, and validator components
  - Define interfaces for ControllerScannerInterface, ControllerFixerInterface, and ControllerValidatorInterface
  - Set up PHPUnit testing framework with Eris for property-based testing
  - _Requirements: 1.1, 2.1, 3.1_

- [ ] 1.1 Write property test for scanner interface
  - **Property 5: Comprehensive scanning**
  - **Validates: Requirements 3.1, 3.2, 3.3, 3.4**

- [ ] 2. Implement Scanner Component
- [ ] 2.1 Create ControllerScanner class with basic file parsing
  - Implement file reading and PHP token parsing
  - Create method to extract method signatures from PHP files
  - Add functionality to identify class usage in method bodies
  - _Requirements: 3.1, 3.2, 3.3_

- [ ] 2.2 Write property test for request parameter detection
  - **Property 1: Request parameter consistency**
  - **Validates: Requirements 1.1**

- [ ] 2.3 Implement findMissingRequestParameters method
  - Use regex and token parsing to find methods using $request
  - Check method signatures for Request parameter
  - Generate detailed issue reports with line numbers
  - _Requirements: 1.1, 3.1_

- [ ] 2.4 Write property test for import detection
  - **Property 2: Import completeness**
  - **Validates: Requirements 1.2, 2.1, 2.2, 2.3**

- [ ] 2.5 Implement findMissingImports method
  - Parse use statements at file beginning
  - Identify class usage throughout the file
  - Detect missing import statements for used classes
  - _Requirements: 1.2, 2.1, 2.2, 2.3_

- [ ] 2.6 Write unit tests for Scanner component
  - Test parsing of various PHP file structures
  - Test detection of different types of issues
  - Test edge cases with complex method signatures
  - _Requirements: 3.1, 3.2, 3.3_

- [ ] 3. Implement Fixer Component
- [ ] 3.1 Create ControllerFixer class with string manipulation
  - Implement safe string replacement for method signatures
  - Add functionality to insert imports at correct positions
  - Ensure proper code formatting preservation
  - _Requirements: 1.1, 1.2, 2.4_

- [ ] 3.2 Write property test for method signature fixing
  - **Property 8: Method body preservation**
  - **Validates: Requirements 4.2**

- [ ] 3.3 Implement fixMethodSignature method
  - Parse method signature and add missing Request parameter
  - Preserve existing parameters and their order
  - Maintain proper PHP syntax and formatting
  - _Requirements: 1.1, 4.1, 4.2_

- [ ] 3.4 Write property test for import preservation
  - **Property 4: Import preservation**
  - **Validates: Requirements 2.4**

- [ ] 3.5 Implement addMissingImports method
  - Insert new use statements in alphabetical order
  - Avoid duplicate imports
  - Preserve existing import structure
  - _Requirements: 1.2, 2.1, 2.2, 2.3, 2.4_

- [ ] 3.6 Write property test for route preservation
  - **Property 7: Route preservation**
  - **Validates: Requirements 4.1**

- [ ] 3.7 Write unit tests for Fixer component
  - Test method signature modifications
  - Test import insertion logic
  - Test preservation of existing code
  - _Requirements: 1.1, 1.2, 4.1, 4.2_

- [ ] 4. Implement Validator Component
- [ ] 4.1 Create ControllerValidator class with syntax checking
  - Implement PHP syntax validation using php -l
  - Add import validation logic
  - Create method signature validation
  - _Requirements: 1.5, 2.5, 3.5_

- [ ] 4.2 Write property test for fix validation
  - **Property 6: Fix validation**
  - **Validates: Requirements 3.5, 1.5, 2.5**

- [ ] 4.3 Implement validateSyntax method
  - Execute PHP syntax check on modified files
  - Parse syntax error output for detailed reporting
  - Return validation results with error details
  - _Requirements: 1.5, 3.5_

- [ ] 4.4 Implement validateImports and validateMethodSignatures methods
  - Check that all used classes have corresponding imports
  - Verify method signatures are syntactically correct
  - Validate parameter types and names
  - _Requirements: 2.5, 1.5_

- [ ] 4.5 Write unit tests for Validator component
  - Test syntax validation with valid and invalid PHP
  - Test import validation logic
  - Test method signature validation
  - _Requirements: 1.5, 2.5, 3.5_

- [ ] 5. Create Main Controller Fixer Service
- [ ] 5.1 Implement ControllerFixerService orchestrator
  - Combine Scanner, Fixer, and Validator components
  - Create workflow for processing multiple controller files
  - Add backup and rollback functionality
  - _Requirements: 1.1, 1.2, 1.3, 1.5_

- [ ] 5.2 Write property test for dependency injection consistency
  - **Property 3: Dependency injection consistency**
  - **Validates: Requirements 1.3**

- [ ] 5.3 Add batch processing capabilities
  - Process all controllers in src/Controller/Apis directory
  - Generate comprehensive fix reports
  - Handle errors gracefully with detailed logging
  - _Requirements: 3.4, 3.5_

- [ ] 5.4 Write integration tests for complete workflow
  - Test end-to-end processing of controller files
  - Test error handling and rollback scenarios
  - Test batch processing functionality
  - _Requirements: 1.1, 1.2, 1.3, 1.5_

- [ ] 6. Fix Identified Controller Issues
- [x] 6.1 Apply fixes to ApiTypeMesureController
  - Add Request $request parameter to create() method
  - Add Request $request parameter to update() method  
  - Add Request $request parameter to deleteAll() method
  - Verify all imports are present
  - _Requirements: 1.1, 1.2_

- [x] 6.2 Apply fixes to ApiFactureController
  - Add Request $request parameter to create() method
  - Add Request $request parameter to update() method
  - Add Request $request parameter to deleteAll() method
  - Fix entity class references in method calls
  - _Requirements: 1.1, 1.2_

- [x] 6.3 Apply fixes to ApiModuleAbonnementController
  - Add Request $request parameter to create() method
  - Add Request $request parameter to update() method
  - Add Request $request parameter to deleteAll() method
  - Add missing service injections
  - _Requirements: 1.1, 1.2, 1.3_

- [x] 6.4 Apply fixes to ApiSurccursaleController
  - Add Request $request parameter to create() method
  - Add Request $request parameter to update() method
  - Add Request $request parameter to deleteAll() method
  - Verify dependency injections
  - _Requirements: 1.1, 1.2, 1.3_

- [x] 6.5 Verify all other controllers for similar issues
  - Scan remaining controllers in src/Controller/Apis
  - Apply fixes to any additional controllers with issues
  - Ensure consistent parameter patterns across all controllers
  - _Requirements: 1.1, 1.2, 1.3_

- [x] 7. Final Checkpoint - Ensure all tests pass and system works
  - Ensure all tests pass, ask the user if questions arise
  - Verify automatic database switching system still works
  - Test API endpoints to confirm functionality is preserved
  - Run complete validation on all fixed controllers