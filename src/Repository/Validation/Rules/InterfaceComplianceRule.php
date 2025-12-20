<?php

namespace App\Repository\Validation\Rules;

use App\Repository\Interface\StandardRepositoryInterface;
use App\Repository\Validation\RepositoryValidationRule;
use App\Repository\Validation\ValidationResult;
use ReflectionClass;

class InterfaceComplianceRule extends RepositoryValidationRule
{
    public function __construct()
    {
        parent::__construct(
            'interface_compliance',
            'Validates that repositories implement StandardRepositoryInterface',
            90
        );
    }

    public function validate(ReflectionClass $repositoryClass): ValidationResult
    {
        $errors = [];
        $warnings = [];

        // Check if repository implements StandardRepositoryInterface
        if (!$this->implementsInterface($repositoryClass, StandardRepositoryInterface::class)) {
            $errors[] = sprintf(
                'Repository %s does not implement %s',
                $repositoryClass->getName(),
                StandardRepositoryInterface::class
            );
        } else {
            // Check if all required methods are implemented
            $requiredMethods = $this->getRequiredMethods();
            $implementedMethods = $this->getImplementedMethods($repositoryClass);

            foreach ($requiredMethods as $methodName => $methodSignature) {
                if (!isset($implementedMethods[$methodName])) {
                    $errors[] = sprintf(
                        'Repository %s is missing required method: %s',
                        $repositoryClass->getName(),
                        $methodSignature
                    );
                } else {
                    // Validate method signature
                    $method = $repositoryClass->getMethod($methodName);
                    $validationResult = $this->validateMethodSignature($method, $methodSignature);
                    
                    if (!$validationResult->isValid()) {
                        $errors = array_merge($errors, $validationResult->getErrors());
                        $warnings = array_merge($warnings, $validationResult->getWarnings());
                    }
                }
            }
        }

        return $this->createResult(empty($errors), $errors, $warnings);
    }

    private function getRequiredMethods(): array
    {
        return [
            'save' => 'save(object $entity, bool $flush = true): void',
            'remove' => 'remove(object $entity, bool $flush = true): void',
            'paginate' => 'paginate(int $page = 1, int $limit = 20, array $criteria = []): PaginationResult',
            'getEntityClass' => 'getEntityClass(): string',
            'findByWithOptions' => 'findByWithOptions(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array',
            'findWithFilters' => 'findWithFilters(array $filters = []): array',
            'countWithFilters' => 'countWithFilters(array $filters = []): int'
        ];
    }

    private function getImplementedMethods(ReflectionClass $class): array
    {
        $methods = [];
        foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $methods[$method->getName()] = $method;
        }
        return $methods;
    }

    private function validateMethodSignature(\ReflectionMethod $method, string $expectedSignature): ValidationResult
    {
        $errors = [];
        $warnings = [];

        // Basic validation - more sophisticated signature checking could be added
        if (!$method->isPublic()) {
            $errors[] = sprintf(
                'Method %s::%s should be public',
                $method->getDeclaringClass()->getName(),
                $method->getName()
            );
        }

        // Check return type for specific methods
        switch ($method->getName()) {
            case 'save':
            case 'remove':
                if ($this->hasReturnType($method) && $this->getReturnTypeString($method) !== 'void') {
                    $warnings[] = sprintf(
                        'Method %s::%s should return void',
                        $method->getDeclaringClass()->getName(),
                        $method->getName()
                    );
                }
                break;
                
            case 'getEntityClass':
                if ($this->hasReturnType($method) && $this->getReturnTypeString($method) !== 'string') {
                    $errors[] = sprintf(
                        'Method %s::%s should return string',
                        $method->getDeclaringClass()->getName(),
                        $method->getName()
                    );
                }
                break;
                
            case 'countWithFilters':
                if ($this->hasReturnType($method) && $this->getReturnTypeString($method) !== 'int') {
                    $errors[] = sprintf(
                        'Method %s::%s should return int',
                        $method->getDeclaringClass()->getName(),
                        $method->getName()
                    );
                }
                break;
        }

        return new ValidationResult(empty($errors), $errors, $warnings);
    }

    public function appliesTo(ReflectionClass $repositoryClass): bool
    {
        // Apply to all classes that end with 'Repository'
        return str_ends_with($repositoryClass->getName(), 'Repository');
    }
}