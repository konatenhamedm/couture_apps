<?php

namespace App\Tests\Unit\Repository\Validation;

use App\Repository\Validation\ValidationResult;
use PHPUnit\Framework\TestCase;

class ValidationResultTest extends TestCase
{
    public function testBasicCreation(): void
    {
        $result = new ValidationResult();
        
        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
        $this->assertEmpty($result->getWarnings());
        $this->assertEmpty($result->getContext());
        $this->assertFalse($result->hasErrors());
        $this->assertFalse($result->hasWarnings());
    }

    public function testCreationWithErrors(): void
    {
        $errors = ['Error 1', 'Error 2'];
        $result = new ValidationResult(false, $errors);
        
        $this->assertFalse($result->isValid());
        $this->assertEquals($errors, $result->getErrors());
        $this->assertTrue($result->hasErrors());
        $this->assertEquals(2, $result->getErrorCount());
    }

    public function testCreationWithWarnings(): void
    {
        $warnings = ['Warning 1', 'Warning 2'];
        $result = new ValidationResult(true, [], $warnings);
        
        $this->assertTrue($result->isValid());
        $this->assertEquals($warnings, $result->getWarnings());
        $this->assertTrue($result->hasWarnings());
        $this->assertEquals(2, $result->getWarningCount());
    }

    public function testAddError(): void
    {
        $result = new ValidationResult();
        $result->addError('New error', ['context' => 'value']);
        
        $this->assertFalse($result->isValid());
        $this->assertEquals(['New error'], $result->getErrors());
        $this->assertTrue($result->hasErrors());
        $this->assertEquals(1, $result->getErrorCount());
        
        $context = $result->getContext();
        $this->assertEquals(['context' => 'value'], $context[0]);
    }

    public function testAddWarning(): void
    {
        $result = new ValidationResult();
        $result->addWarning('New warning', ['context' => 'value']);
        
        $this->assertTrue($result->isValid()); // Still valid with warnings
        $this->assertEquals(['New warning'], $result->getWarnings());
        $this->assertTrue($result->hasWarnings());
        $this->assertEquals(1, $result->getWarningCount());
    }

    public function testMerge(): void
    {
        $result1 = new ValidationResult(true, [], ['Warning 1']);
        $result2 = new ValidationResult(false, ['Error 1'], ['Warning 2']);
        
        $merged = $result1->merge($result2);
        
        $this->assertFalse($merged->isValid()); // Invalid if any result is invalid
        $this->assertEquals(['Error 1'], $merged->getErrors());
        $this->assertEquals(['Warning 1', 'Warning 2'], $merged->getWarnings());
    }

    public function testGetSummary(): void
    {
        $result = new ValidationResult(false, ['Error 1'], ['Warning 1'], ['context' => 'test']);
        
        $summary = $result->getSummary();
        
        $this->assertArrayHasKey('valid', $summary);
        $this->assertArrayHasKey('error_count', $summary);
        $this->assertArrayHasKey('warning_count', $summary);
        $this->assertArrayHasKey('errors', $summary);
        $this->assertArrayHasKey('warnings', $summary);
        
        $this->assertFalse($summary['valid']);
        $this->assertEquals(1, $summary['error_count']);
        $this->assertEquals(1, $summary['warning_count']);
        $this->assertEquals(['Error 1'], $summary['errors']);
        $this->assertEquals(['Warning 1'], $summary['warnings']);
    }

    public function testMultipleErrorsAndWarnings(): void
    {
        $result = new ValidationResult();
        
        $result->addError('Error 1');
        $result->addError('Error 2');
        $result->addWarning('Warning 1');
        $result->addWarning('Warning 2');
        
        $this->assertFalse($result->isValid());
        $this->assertEquals(2, $result->getErrorCount());
        $this->assertEquals(2, $result->getWarningCount());
        $this->assertEquals(['Error 1', 'Error 2'], $result->getErrors());
        $this->assertEquals(['Warning 1', 'Warning 2'], $result->getWarnings());
    }
}