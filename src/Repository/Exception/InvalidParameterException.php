<?php

namespace App\Repository\Exception;

/**
 * Exception thrown when invalid parameters are provided to repository methods
 * 
 * This exception is thrown when method parameters are of wrong type,
 * out of range, or otherwise invalid.
 */
class InvalidParameterException extends RepositoryException
{
    private string $parameterName = '';
    private mixed $parameterValue = null;
    private string $expectedType = '';

    public function __construct(
        string $message = '',
        string $parameterName = '',
        mixed $parameterValue = null,
        string $expectedType = '',
        int $code = 0,
        ?\Throwable $previous = null,
        string $repositoryClass = '',
        string $methodName = '',
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $repositoryClass, $methodName, $context);
        
        $this->parameterName = $parameterName;
        $this->parameterValue = $parameterValue;
        $this->expectedType = $expectedType;
    }

    public function getParameterName(): string
    {
        return $this->parameterName;
    }

    public function getParameterValue(): mixed
    {
        return $this->parameterValue;
    }

    public function getExpectedType(): string
    {
        return $this->expectedType;
    }

    /**
     * Create exception for invalid pagination parameters
     */
    public static function invalidPaginationParameter(
        string $parameterName,
        mixed $value,
        string $repositoryClass,
        string $methodName
    ): static {
        $message = "Invalid pagination parameter '{$parameterName}': {$value}";
        
        return new static(
            $message,
            $parameterName,
            $value,
            'positive integer',
            0,
            null,
            $repositoryClass,
            $methodName,
            ['type' => 'pagination']
        );
    }

    /**
     * Create exception for out of range values
     */
    public static function outOfRange(
        string $parameterName,
        mixed $value,
        int $min,
        int $max,
        string $repositoryClass,
        string $methodName
    ): static {
        $message = "Parameter '{$parameterName}' value {$value} is out of range [{$min}, {$max}]";
        
        return new static(
            $message,
            $parameterName,
            $value,
            "integer between {$min} and {$max}",
            0,
            null,
            $repositoryClass,
            $methodName,
            ['type' => 'out_of_range', 'min' => $min, 'max' => $max]
        );
    }

    /**
     * Create exception for null values when not allowed
     */
    public static function nullNotAllowed(
        string $parameterName,
        string $repositoryClass,
        string $methodName
    ): static {
        $message = "Parameter '{$parameterName}' cannot be null";
        
        return new static(
            $message,
            $parameterName,
            null,
            'non-null value',
            0,
            null,
            $repositoryClass,
            $methodName,
            ['type' => 'null_not_allowed']
        );
    }
}