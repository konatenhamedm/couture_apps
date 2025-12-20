<?php

namespace App\Repository\Validation;

class ValidationResult
{
    private bool $isValid;
    private array $errors;
    private array $warnings;
    private array $context;

    public function __construct(
        bool $isValid = true,
        array $errors = [],
        array $warnings = [],
        array $context = []
    ) {
        $this->isValid = $isValid;
        $this->errors = $errors;
        $this->warnings = $warnings;
        $this->context = $context;
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    public function addError(string $error, array $context = []): void
    {
        $this->errors[] = $error;
        $this->isValid = false;
        
        if (!empty($context)) {
            $this->context[count($this->errors) - 1] = $context;
        }
    }

    public function addWarning(string $warning, array $context = []): void
    {
        $this->warnings[] = $warning;
        
        if (!empty($context)) {
            $this->context['warnings'][count($this->warnings) - 1] = $context;
        }
    }

    public function merge(ValidationResult $other): self
    {
        $merged = new self(
            $this->isValid && $other->isValid,
            array_merge($this->errors, $other->getErrors()),
            array_merge($this->warnings, $other->getWarnings()),
            array_merge($this->context, $other->getContext())
        );

        return $merged;
    }

    public function getErrorCount(): int
    {
        return count($this->errors);
    }

    public function getWarningCount(): int
    {
        return count($this->warnings);
    }

    public function getSummary(): array
    {
        return [
            'valid' => $this->isValid,
            'error_count' => $this->getErrorCount(),
            'warning_count' => $this->getWarningCount(),
            'errors' => $this->errors,
            'warnings' => $this->warnings
        ];
    }
}