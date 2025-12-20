<?php

namespace App\Repository\Validation\Rules;

use App\Repository\Validation\RepositoryValidationRule;
use App\Repository\Validation\ValidationResult;
use ReflectionClass;

class ReturnTypeRule extends RepositoryValidationRule
{
    public function __construct()
    {
        parent::__construct(
            'return_type_consistency',
            'Validates that repository methods have proper return type declarations',
            70
        );
    }

    public function validate(ReflectionClass $repositoryClass): ValidationResult
    {
        $errors = [];
        $warnings = [];

        foreach ($this->getPublicMethods($repositoryClass) as $method) {
            $methodName = $method->getName();
            
            // Skip magic methods and inherited Doctrine methods
            if ($this->shouldSkipMethod($methodName)) {
                continue;
            }

            // Check if method has return type declaration
            if (!$this->hasReturnType($method)) {
                $warnings[] = sprintf(
                    'Method %s::%s is missing return type declaration',
                    $repositoryClass->getName(),
                    $methodName
                );
                continue;
            }

            // Validate specific return types based on method patterns
            $this->validateReturnTypeConsistency($repositoryClass, $method, $errors, $warnings);
        }

        return $this->createResult(empty($errors), $errors, $warnings);
    }

    private function shouldSkipMethod(string $methodName): bool
    {
        // Skip magic methods
        if (str_starts_with($methodName, '__')) {
            return true;
        }

        // Skip inherited Doctrine methods
        $doctrineBaseMethods = [
            'find', 'findAll', 'findBy', 'findOneBy', 'count',
            'createQueryBuilder', 'createResultSetMappingBuilder',
            'clear', 'getEntityManager', 'getClassName'
        ];

        return in_array($methodName, $doctrineBaseMethods);
    }

    private function validateReturnTypeConsistency(
        ReflectionClass $class,
        \ReflectionMethod $method,
        array &$errors,
        array &$warnings
    ): void {
        $methodName = $method->getName();
        $returnType = $this->getReturnTypeString($method);

        // Validate based on method naming patterns
        switch (true) {
            case str_starts_with($methodName, 'find') && str_ends_with($methodName, 'All'):
                $this->validateArrayReturnType($class, $method, $returnType, $warnings);
                break;

            case str_starts_with($methodName, 'findBy') && $methodName !== 'findBy':
                $this->validateArrayReturnType($class, $method, $returnType, $warnings);
                break;

            case str_starts_with($methodName, 'findOneBy') || str_starts_with($methodName, 'findOne'):
                $this->validateNullableEntityReturnType($class, $method, $returnType, $warnings);
                break;

            case str_starts_with($methodName, 'count'):
                $this->validateIntReturnType($class, $method, $returnType, $errors);
                break;

            case str_starts_with($methodName, 'exists') || str_starts_with($methodName, 'has') || str_starts_with($methodName, 'is'):
                $this->validateBoolReturnType($class, $method, $returnType, $errors);
                break;

            case str_starts_with($methodName, 'save') || str_starts_with($methodName, 'remove') || str_starts_with($methodName, 'delete'):
                $this->validateVoidReturnType($class, $method, $returnType, $warnings);
                break;

            case str_starts_with($methodName, 'create'):
                $this->validateEntityReturnType($class, $method, $returnType, $warnings);
                break;

            case $methodName === 'paginate':
                $this->validatePaginationReturnType($class, $method, $returnType, $errors);
                break;

            case $methodName === 'getEntityClass':
                $this->validateStringReturnType($class, $method, $returnType, $errors);
                break;
        }
    }

    private function validateArrayReturnType(ReflectionClass $class, \ReflectionMethod $method, ?string $returnType, array &$warnings): void
    {
        if ($returnType && !str_contains($returnType, 'array') && !str_contains($returnType, '[]')) {
            $warnings[] = sprintf(
                'Method %s::%s should return an array type, got %s',
                $class->getName(),
                $method->getName(),
                $returnType
            );
        }
    }

    private function validateNullableEntityReturnType(ReflectionClass $class, \ReflectionMethod $method, ?string $returnType, array &$warnings): void
    {
        if ($returnType && str_contains($returnType, 'array')) {
            $warnings[] = sprintf(
                'Method %s::%s should return a single entity or null, not an array',
                $class->getName(),
                $method->getName()
            );
        }
    }

    private function validateIntReturnType(ReflectionClass $class, \ReflectionMethod $method, ?string $returnType, array &$errors): void
    {
        if ($returnType && $returnType !== 'int') {
            $errors[] = sprintf(
                'Method %s::%s should return int, got %s',
                $class->getName(),
                $method->getName(),
                $returnType
            );
        }
    }

    private function validateBoolReturnType(ReflectionClass $class, \ReflectionMethod $method, ?string $returnType, array &$errors): void
    {
        if ($returnType && $returnType !== 'bool') {
            $errors[] = sprintf(
                'Method %s::%s should return bool, got %s',
                $class->getName(),
                $method->getName(),
                $returnType
            );
        }
    }

    private function validateVoidReturnType(ReflectionClass $class, \ReflectionMethod $method, ?string $returnType, array &$warnings): void
    {
        if ($returnType && $returnType !== 'void') {
            $warnings[] = sprintf(
                'Method %s::%s should return void, got %s',
                $class->getName(),
                $method->getName(),
                $returnType
            );
        }
    }

    private function validateEntityReturnType(ReflectionClass $class, \ReflectionMethod $method, ?string $returnType, array &$warnings): void
    {
        if ($returnType && (str_contains($returnType, 'array') || $returnType === 'void')) {
            $warnings[] = sprintf(
                'Method %s::%s should return an entity instance, got %s',
                $class->getName(),
                $method->getName(),
                $returnType
            );
        }
    }

    private function validatePaginationReturnType(ReflectionClass $class, \ReflectionMethod $method, ?string $returnType, array &$errors): void
    {
        if ($returnType && !str_contains($returnType, 'PaginationResult')) {
            $errors[] = sprintf(
                'Method %s::%s should return PaginationResult, got %s',
                $class->getName(),
                $method->getName(),
                $returnType
            );
        }
    }

    private function validateStringReturnType(ReflectionClass $class, \ReflectionMethod $method, ?string $returnType, array &$errors): void
    {
        if ($returnType && $returnType !== 'string') {
            $errors[] = sprintf(
                'Method %s::%s should return string, got %s',
                $class->getName(),
                $method->getName(),
                $returnType
            );
        }
    }

    public function getSeverity(): string
    {
        return 'warning'; // Most return type issues are warnings
    }
}