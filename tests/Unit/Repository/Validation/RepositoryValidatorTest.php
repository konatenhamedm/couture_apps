<?php

namespace App\Tests\Unit\Repository\Validation;

use App\Repository\Validation\RepositoryValidator;
use App\Repository\Validation\RepositoryValidationRule;
use App\Repository\Validation\ValidationResult;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class RepositoryValidatorTest extends TestCase
{
    private RepositoryValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new RepositoryValidator();
    }

    public function testInitializationWithDefaultRules(): void
    {
        $rules = $this->validator->getRules();
        
        $this->assertCount(3, $rules); // InterfaceComplianceRule, NamingConventionRule, ReturnTypeRule
        
        $ruleNames = array_map(fn($rule) => $rule->getName(), $rules);
        $this->assertContains('interface_compliance', $ruleNames);
        $this->assertContains('naming_convention', $ruleNames);
        $this->assertContains('return_type_consistency', $ruleNames);
    }

    public function testAddRule(): void
    {
        $mockRule = $this->createMockRule('test_rule', 50);
        
        $this->validator->addRule($mockRule);
        $rules = $this->validator->getRules();
        
        $this->assertCount(4, $rules); // 3 default + 1 added
        $this->assertContains($mockRule, $rules);
    }

    public function testRemoveRule(): void
    {
        $this->validator->removeRule('naming_convention');
        $rules = $this->validator->getRules();
        
        $this->assertCount(2, $rules); // 3 default - 1 removed
        
        $ruleNames = array_map(fn($rule) => $rule->getName(), $rules);
        $this->assertNotContains('naming_convention', $ruleNames);
    }

    public function testRulePriorityOrdering(): void
    {
        $highPriorityRule = $this->createMockRule('high_priority', 200);
        $lowPriorityRule = $this->createMockRule('low_priority', 10);
        
        $this->validator->addRule($highPriorityRule);
        $this->validator->addRule($lowPriorityRule);
        
        $rules = $this->validator->getRules();
        
        // High priority rule should be first
        $this->assertEquals('high_priority', $rules[0]->getName());
        
        // Low priority rule should be last
        $this->assertEquals('low_priority', $rules[count($rules) - 1]->getName());
    }

    public function testValidateRepositoryWithNonExistentClass(): void
    {
        $result = $this->validator->validateRepository('NonExistentRepository');
        
        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrors());
        $this->assertStringContainsString('Could not load repository class', $result->getErrors()[0]);
    }

    public function testValidateRepositoryClass(): void
    {
        // Create a mock repository class
        $mockClass = $this->createMock(ReflectionClass::class);
        $mockClass->method('getName')->willReturn('MockRepository');
        
        // Create a mock rule that returns a validation result
        $mockRule = $this->createMockRule('test_rule', 100);
        $mockRule->method('appliesTo')->willReturn(true);
        $mockRule->method('validate')->willReturn(new ValidationResult(true));
        
        // Replace default rules with our mock rule
        $validator = new RepositoryValidator();
        $validator->removeRule('interface_compliance');
        $validator->removeRule('naming_convention');
        $validator->removeRule('return_type_consistency');
        $validator->addRule($mockRule);
        
        $result = $validator->validateRepositoryClass($mockClass);
        
        $this->assertTrue($result->isValid());
    }

    public function testGetValidationSummary(): void
    {
        $validationResults = [
            'ValidRepository' => new ValidationResult(true),
            'InvalidRepository' => new ValidationResult(false, ['Error 1'], ['Warning 1']),
            'WarningRepository' => new ValidationResult(true, [], ['Warning 2'])
        ];
        
        $summary = $this->validator->getValidationSummary($validationResults);
        
        $this->assertEquals(3, $summary['total_repositories']);
        $this->assertEquals(1, $summary['valid_repositories']); // Only ValidRepository has no errors/warnings
        $this->assertEquals(1, $summary['repositories_with_errors']);
        $this->assertEquals(2, $summary['repositories_with_warnings']);
        $this->assertEquals(1, $summary['total_errors']);
        $this->assertEquals(2, $summary['total_warnings']);
        
        $this->assertArrayHasKey('InvalidRepository', $summary['error_details']);
        $this->assertArrayHasKey('InvalidRepository', $summary['warning_details']);
        $this->assertArrayHasKey('WarningRepository', $summary['warning_details']);
    }

    public function testIsRepositoryCompliant(): void
    {
        // This test would require a real repository class or more complex mocking
        // For now, we'll test the method exists and handles non-existent classes
        $result = $this->validator->isRepositoryCompliant('NonExistentRepository');
        $this->assertFalse($result);
    }

    public function testGenerateReport(): void
    {
        $validationResults = [
            'ValidRepository' => new ValidationResult(true),
            'InvalidRepository' => new ValidationResult(false, ['Error 1'], ['Warning 1'])
        ];
        
        $report = $this->validator->generateReport($validationResults);
        
        $this->assertStringContainsString('Repository Validation Report', $report);
        $this->assertStringContainsString('Total Repositories: 2', $report);
        $this->assertStringContainsString('Valid Repositories: 1', $report);
        $this->assertStringContainsString('ERRORS:', $report);
        $this->assertStringContainsString('InvalidRepository:', $report);
        $this->assertStringContainsString('Error 1', $report);
        $this->assertStringContainsString('WARNINGS:', $report);
        $this->assertStringContainsString('Warning 1', $report);
    }

    private function createMockRule(string $name, int $priority): RepositoryValidationRule
    {
        $mockRule = $this->createMock(RepositoryValidationRule::class);
        $mockRule->method('getName')->willReturn($name);
        $mockRule->method('getPriority')->willReturn($priority);
        $mockRule->method('getDescription')->willReturn("Mock rule: $name");
        $mockRule->method('appliesTo')->willReturn(true);
        $mockRule->method('validate')->willReturn(new ValidationResult(true));
        
        return $mockRule;
    }
}