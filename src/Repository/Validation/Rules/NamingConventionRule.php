<?php

namespace App\Repository\Validation\Rules;

use App\Repository\Validation\RepositoryValidationRule;
use App\Repository\Validation\ValidationResult;
use ReflectionClass;

class NamingConventionRule extends RepositoryValidationRule
{
    private array $allowedMethodPrefixes = [
        'find', 'get', 'create', 'save', 'remove', 'delete', 'update',
        'count', 'exists', 'paginate', 'search', 'filter', 'has', 'is'
    ];

    private array $deprecatedPrefixes = [
        'add' // Should use 'save' instead
    ];

    public function __construct()
    {
        parent::__construct(
            'naming_convention',
            'Validates repository method naming conventions',
            80
        );
    }

    public function validate(ReflectionClass $repositoryClass): ValidationResult
    {
        $errors = [];
        $warnings = [];

        // Validate class name
        if (!str_ends_with($repositoryClass->getName(), 'Repository')) {
            $errors[] = sprintf(
                'Repository class %s should end with "Repository"',
                $repositoryClass->getName()
            );
        }

        // Validate method names
        foreach ($this->getPublicMethods($repositoryClass) as $method) {
            $methodName = $method->getName();
            
            // Skip magic methods and inherited methods from parent classes
            if ($this->shouldSkipMethod($methodName, $method)) {
                continue;
            }

            // Check for deprecated prefixes
            foreach ($this->deprecatedPrefixes as $deprecatedPrefix) {
                if (str_starts_with($methodName, $deprecatedPrefix)) {
                    $warnings[] = sprintf(
                        'Method %s::%s uses deprecated prefix "%s". Consider using "save" instead.',
                        $repositoryClass->getName(),
                        $methodName,
                        $deprecatedPrefix
                    );
                }
            }

            // Check if method follows naming conventions
            if (!$this->isValidMethodName($methodName)) {
                $warnings[] = sprintf(
                    'Method %s::%s does not follow standard naming conventions. Expected prefixes: %s',
                    $repositoryClass->getName(),
                    $methodName,
                    implode(', ', $this->allowedMethodPrefixes)
                );
            }

            // Check for specific naming patterns
            $this->validateSpecificPatterns($repositoryClass, $method, $warnings);
        }

        return $this->createResult(empty($errors), $errors, $warnings);
    }

    private function shouldSkipMethod(string $methodName, \ReflectionMethod $method): bool
    {
        // Skip magic methods
        if (str_starts_with($methodName, '__')) {
            return true;
        }

        // Skip methods inherited from Doctrine's ServiceEntityRepository
        $doctrineBaseMethods = [
            'find', 'findAll', 'findBy', 'findOneBy', 'count',
            'createQueryBuilder', 'createResultSetMappingBuilder',
            'clear', 'getEntityManager', 'getClassName'
        ];

        if (in_array($methodName, $doctrineBaseMethods)) {
            return true;
        }

        // Skip methods from our trait/interface that are already validated elsewhere
        $standardMethods = [
            'save', 'remove', 'paginate', 'getEntityClass',
            'findByWithOptions', 'findWithFilters', 'countWithFilters'
        ];

        if (in_array($methodName, $standardMethods)) {
            return true;
        }

        return false;
    }

    private function isValidMethodName(string $methodName): bool
    {
        foreach ($this->allowedMethodPrefixes as $prefix) {
            if (str_starts_with($methodName, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function validateSpecificPatterns(ReflectionClass $class, \ReflectionMethod $method, array &$warnings): void
    {
        $methodName = $method->getName();

        // Check for findBy* methods that should return arrays
        if (str_starts_with($methodName, 'findBy') && $methodName !== 'findBy') {
            if ($this->hasReturnType($method)) {
                $returnType = $this->getReturnTypeString($method);
                if ($returnType && !str_contains($returnType, 'array') && !str_contains($returnType, '[]')) {
                    $warnings[] = sprintf(
                        'Method %s::%s should return an array or array-like type',
                        $class->getName(),
                        $methodName
                    );
                }
            }
        }

        // Check for findOneBy* methods that should return single entities or null
        if (str_starts_with($methodName, 'findOneBy') && $methodName !== 'findOneBy') {
            if ($this->hasReturnType($method)) {
                $returnType = $this->getReturnTypeString($method);
                if ($returnType && str_contains($returnType, 'array')) {
                    $warnings[] = sprintf(
                        'Method %s::%s should return a single entity or null, not an array',
                        $class->getName(),
                        $methodName
                    );
                }
            }
        }

        // Check for count* methods that should return int
        if (str_starts_with($methodName, 'count')) {
            if ($this->hasReturnType($method)) {
                $returnType = $this->getReturnTypeString($method);
                if ($returnType && $returnType !== 'int') {
                    $warnings[] = sprintf(
                        'Method %s::%s should return int',
                        $class->getName(),
                        $methodName
                    );
                }
            }
        }

        // Check for exists/has methods that should return bool
        if (str_starts_with($methodName, 'exists') || str_starts_with($methodName, 'has') || str_starts_with($methodName, 'is')) {
            if ($this->hasReturnType($method)) {
                $returnType = $this->getReturnTypeString($method);
                if ($returnType && $returnType !== 'bool') {
                    $warnings[] = sprintf(
                        'Method %s::%s should return bool',
                        $class->getName(),
                        $methodName
                    );
                }
            }
        }
    }

    public function getSeverity(): string
    {
        return 'warning'; // Naming conventions are warnings, not errors
    }
}