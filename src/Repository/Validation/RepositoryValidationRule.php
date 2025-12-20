<?php

namespace App\Repository\Validation;

use ReflectionClass;

abstract class RepositoryValidationRule
{
    protected string $name;
    protected string $description;
    protected int $priority;

    public function __construct(string $name, string $description, int $priority = 100)
    {
        $this->name = $name;
        $this->description = $description;
        $this->priority = $priority;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Validate a repository class
     */
    abstract public function validate(ReflectionClass $repositoryClass): ValidationResult;

    /**
     * Check if this rule applies to the given repository class
     */
    public function appliesTo(ReflectionClass $repositoryClass): bool
    {
        return true; // By default, all rules apply to all repositories
    }

    /**
     * Get the severity level of violations for this rule
     */
    public function getSeverity(): string
    {
        return 'error'; // 'error', 'warning', 'info'
    }

    /**
     * Create a validation result with proper context
     */
    protected function createResult(bool $isValid, array $errors = [], array $warnings = []): ValidationResult
    {
        return new ValidationResult($isValid, $errors, $warnings, [
            'rule' => $this->name,
            'description' => $this->description,
            'severity' => $this->getSeverity()
        ]);
    }

    /**
     * Helper method to check if a class implements an interface
     */
    protected function implementsInterface(ReflectionClass $class, string $interfaceName): bool
    {
        return $class->implementsInterface($interfaceName);
    }

    /**
     * Helper method to check if a class extends another class
     */
    protected function extendsClass(ReflectionClass $class, string $className): bool
    {
        return $class->isSubclassOf($className);
    }

    /**
     * Helper method to get all public methods of a class
     */
    protected function getPublicMethods(ReflectionClass $class): array
    {
        return $class->getMethods(\ReflectionMethod::IS_PUBLIC);
    }

    /**
     * Helper method to check method naming conventions
     */
    protected function validateMethodName(string $methodName, array $allowedPrefixes = []): bool
    {
        if (empty($allowedPrefixes)) {
            return true;
        }

        foreach ($allowedPrefixes as $prefix) {
            if (str_starts_with($methodName, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Helper method to validate return type annotations
     */
    protected function hasReturnType(\ReflectionMethod $method): bool
    {
        return $method->hasReturnType();
    }

    /**
     * Helper method to get return type as string
     */
    protected function getReturnTypeString(\ReflectionMethod $method): ?string
    {
        $returnType = $method->getReturnType();
        
        if ($returnType === null) {
            return null;
        }

        return $returnType->__toString();
    }
}