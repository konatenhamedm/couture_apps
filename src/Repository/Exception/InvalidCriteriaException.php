<?php

namespace App\Repository\Exception;

/**
 * Exception thrown when invalid criteria are provided to repository methods
 * 
 * This exception is thrown when search criteria, filters, or query parameters
 * are malformed or contain invalid values.
 */
class InvalidCriteriaException extends RepositoryException
{
    private array $invalidCriteria = [];
    private array $validCriteria = [];

    public function __construct(
        string $message = '',
        array $invalidCriteria = [],
        array $validCriteria = [],
        int $code = 0,
        ?\Throwable $previous = null,
        string $repositoryClass = '',
        string $methodName = '',
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $repositoryClass, $methodName, $context);
        
        $this->invalidCriteria = $invalidCriteria;
        $this->validCriteria = $validCriteria;
    }

    public function getInvalidCriteria(): array
    {
        return $this->invalidCriteria;
    }

    public function getValidCriteria(): array
    {
        return $this->validCriteria;
    }

    /**
     * Create exception for invalid field names
     */
    public static function invalidFields(
        array $invalidFields,
        array $validFields,
        string $repositoryClass,
        string $methodName
    ): static {
        $message = "Invalid field names: " . implode(', ', $invalidFields);
        
        return new static(
            $message,
            $invalidFields,
            $validFields,
            0,
            null,
            $repositoryClass,
            $methodName,
            ['type' => 'invalid_fields']
        );
    }

    /**
     * Create exception for invalid operators
     */
    public static function invalidOperator(
        string $operator,
        array $validOperators,
        string $repositoryClass,
        string $methodName
    ): static {
        $message = "Invalid operator '{$operator}'. Valid operators: " . implode(', ', $validOperators);
        
        return new static(
            $message,
            [$operator],
            $validOperators,
            0,
            null,
            $repositoryClass,
            $methodName,
            ['type' => 'invalid_operator']
        );
    }

    /**
     * Create exception for invalid parameter values
     */
    public static function invalidParameterValue(
        string $parameter,
        mixed $value,
        string $expectedType,
        string $repositoryClass,
        string $methodName
    ): static {
        $actualType = gettype($value);
        $message = "Invalid value for parameter '{$parameter}': expected {$expectedType}, got {$actualType}";
        
        return new static(
            $message,
            [$parameter => $value],
            [$parameter => $expectedType],
            0,
            null,
            $repositoryClass,
            $methodName,
            ['type' => 'invalid_parameter_value']
        );
    }
}