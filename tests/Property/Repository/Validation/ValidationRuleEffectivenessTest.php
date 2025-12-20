<?php

namespace App\Tests\Property\Repository\Validation;

use App\Repository\Validation\RepositoryValidator;
use App\Repository\Validation\Rules\InterfaceComplianceRule;
use App\Repository\Validation\Rules\NamingConventionRule;
use App\Repository\Validation\Rules\ReturnTypeRule;
use Eris\Generator;
use Eris\TestTrait;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Property 10: Validation Rule Effectiveness
 * 
 * This test validates that validation rules consistently identify violations
 * and that the validation system produces deterministic results.
 */
class ValidationRuleEffectivenessTest extends TestCase
{
    use TestTrait;

    public function testValidationRulesProduceDeterministicResults(): void
    {
        $this->forAll(
            Generator\choose(1, 100), // Random seed for test variation
            Generator\elements([
                'TestRepository',
                'UserRepository', 
                'InvalidRepo', // Should fail naming convention
                'ProductRepository'
            ])
        )->then(function (int $seed, string $className) {
            $validator = new RepositoryValidator();
            
            // Run validation multiple times with same input
            $result1 = $this->validateMockRepository($validator, $className, $seed);
            $result2 = $this->validateMockRepository($validator, $className, $seed);
            
            // Results should be identical (deterministic)
            $this->assertEquals($result1->isValid(), $result2->isValid());
            $this->assertEquals($result1->getErrors(), $result2->getErrors());
            $this->assertEquals($result1->getWarnings(), $result2->getWarnings());
            $this->assertEquals($result1->getErrorCount(), $result2->getErrorCount());
            $this->assertEquals($result1->getWarningCount(), $result2->getWarningCount());
        });
    }

    public function testValidationRulesIdentifyKnownViolations(): void
    {
        $this->forAll(
            Generator\elements([
                ['className' => 'InvalidRepo', 'expectedViolation' => 'naming'],
                ['className' => 'TestRepository', 'expectedViolation' => 'interface'],
                ['className' => 'UserRepository', 'expectedViolation' => 'interface']
            ])
        )->then(function (array $testCase) {
            $validator = new RepositoryValidator();
            $result = $this->validateMockRepository($validator, $testCase['className']);
            
            // Should identify violations for non-compliant repositories
            if ($testCase['expectedViolation'] === 'naming' && !str_ends_with($testCase['className'], 'Repository')) {
                $this->assertTrue($result->hasErrors() || $result->hasWarnings());
            }
            
            // Interface compliance violations should be detected
            if ($testCase['expectedViolation'] === 'interface') {
                // Mock repositories won't implement our interface, so should have violations
                $this->assertTrue($result->hasErrors() || $result->hasWarnings());
            }
        });
    }

    public function testValidationRuleConsistencyAcrossRuleSets(): void
    {
        $this->forAll(
            Generator\elements(['UserRepository', 'ProductRepository', 'OrderRepository']),
            Generator\choose(1, 3) // Number of rules to test
        )->then(function (string $className, int $ruleCount) {
            // Test with different combinations of rules
            $allRules = [
                new InterfaceComplianceRule(),
                new NamingConventionRule(), 
                new ReturnTypeRule()
            ];
            
            $selectedRules = array_slice($allRules, 0, $ruleCount);
            
            $validator1 = new RepositoryValidator();
            $validator2 = new RepositoryValidator();
            
            // Clear default rules and add selected rules to both validators
            foreach (['interface_compliance', 'naming_convention', 'return_type_consistency'] as $ruleName) {
                $validator1->removeRule($ruleName);
                $validator2->removeRule($ruleName);
            }
            
            foreach ($selectedRules as $rule) {
                $validator1->addRule($rule);
                $validator2->addRule($rule);
            }
            
            $result1 = $this->validateMockRepository($validator1, $className);
            $result2 = $this->validateMockRepository($validator2, $className);
            
            // Same rules should produce same results
            $this->assertEquals($result1->isValid(), $result2->isValid());
            $this->assertEquals($result1->getErrorCount(), $result2->getErrorCount());
        });
    }

    public function testValidationResultMerging(): void
    {
        $this->forAll(
            Generator\choose(0, 5), // Number of errors in first result
            Generator\choose(0, 5), // Number of warnings in first result
            Generator\choose(0, 5), // Number of errors in second result
            Generator\choose(0, 5)  // Number of warnings in second result
        )->then(function (int $errors1, int $warnings1, int $errors2, int $warnings2) {
            $result1 = $this->createValidationResult($errors1, $warnings1);
            $result2 = $this->createValidationResult($errors2, $warnings2);
            
            $merged = $result1->merge($result2);
            
            // Merged result should contain all errors and warnings
            $this->assertEquals($errors1 + $errors2, $merged->getErrorCount());
            $this->assertEquals($warnings1 + $warnings2, $merged->getWarningCount());
            
            // Merged result is valid only if both original results were valid
            $expectedValid = ($errors1 === 0) && ($errors2 === 0);
            $this->assertEquals($expectedValid, $merged->isValid());
        });
    }

    public function testValidationSummaryAccuracy(): void
    {
        $this->forAll(
            Generator\choose(1, 10), // Number of repositories
            Generator\choose(0, 3),  // Max errors per repository
            Generator\choose(0, 3)   // Max warnings per repository
        )->then(function (int $repoCount, int $maxErrors, int $maxWarnings) {
            $validator = new RepositoryValidator();
            $results = [];
            $expectedTotalErrors = 0;
            $expectedTotalWarnings = 0;
            $expectedValidRepos = 0;
            $expectedErrorRepos = 0;
            $expectedWarningRepos = 0;
            
            for ($i = 0; $i < $repoCount; $i++) {
                $errors = rand(0, $maxErrors);
                $warnings = rand(0, $maxWarnings);
                
                $result = $this->createValidationResult($errors, $warnings);
                $results["Repository$i"] = $result;
                
                $expectedTotalErrors += $errors;
                $expectedTotalWarnings += $warnings;
                
                if ($errors === 0 && $warnings === 0) {
                    $expectedValidRepos++;
                }
                if ($errors > 0) {
                    $expectedErrorRepos++;
                }
                if ($warnings > 0) {
                    $expectedWarningRepos++;
                }
            }
            
            $summary = $validator->getValidationSummary($results);
            
            $this->assertEquals($repoCount, $summary['total_repositories']);
            $this->assertEquals($expectedTotalErrors, $summary['total_errors']);
            $this->assertEquals($expectedTotalWarnings, $summary['total_warnings']);
            $this->assertEquals($expectedValidRepos, $summary['valid_repositories']);
            $this->assertEquals($expectedErrorRepos, $summary['repositories_with_errors']);
            $this->assertEquals($expectedWarningRepos, $summary['repositories_with_warnings']);
        });
    }

    private function validateMockRepository(RepositoryValidator $validator, string $className, int $seed = 1): \App\Repository\Validation\ValidationResult
    {
        // Create a mock ReflectionClass for testing
        $mockClass = $this->createMock(ReflectionClass::class);
        $mockClass->method('getName')->willReturn($className);
        $mockClass->method('implementsInterface')->willReturn(false); // Mock doesn't implement our interface
        $mockClass->method('getMethods')->willReturn([]);
        $mockClass->method('isSubclassOf')->willReturn(false);
        
        return $validator->validateRepositoryClass($mockClass);
    }

    private function createValidationResult(int $errorCount, int $warningCount): \App\Repository\Validation\ValidationResult
    {
        $errors = [];
        $warnings = [];
        
        for ($i = 0; $i < $errorCount; $i++) {
            $errors[] = "Error $i";
        }
        
        for ($i = 0; $i < $warningCount; $i++) {
            $warnings[] = "Warning $i";
        }
        
        return new \App\Repository\Validation\ValidationResult($errorCount === 0, $errors, $warnings);
    }
}