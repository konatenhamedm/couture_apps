<?php

namespace App\Repository\Exception;

/**
 * Exception thrown when entity validation fails
 * 
 * This exception is thrown when an entity doesn't meet
 * the validation requirements before being persisted.
 */
class EntityValidationException extends RepositoryException
{
    private array $validationErrors = [];
    private ?object $entity = null;

    public function __construct(
        string $message = '',
        array $validationErrors = [],
        ?object $entity = null,
        int $code = 0,
        ?\Throwable $previous = null,
        string $repositoryClass = '',
        string $methodName = '',
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $repositoryClass, $methodName, $context);
        
        $this->validationErrors = $validationErrors;
        $this->entity = $entity;
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    public function getEntity(): ?object
    {
        return $this->entity;
    }

    /**
     * Create exception for invalid entity type
     */
    public static function invalidEntityType(
        string $expectedType,
        string $actualType,
        string $repositoryClass,
        string $methodName
    ): static {
        $message = "Expected entity of type {$expectedType}, got {$actualType}";
        
        return new static(
            $message,
            ['expected_type' => $expectedType, 'actual_type' => $actualType],
            null,
            0,
            null,
            $repositoryClass,
            $methodName
        );
    }

    /**
     * Create exception for validation failures
     */
    public static function validationFailed(
        array $errors,
        object $entity,
        string $repositoryClass,
        string $methodName
    ): static {
        $message = "Entity validation failed: " . implode(', ', array_keys($errors));
        
        return new static(
            $message,
            $errors,
            $entity,
            0,
            null,
            $repositoryClass,
            $methodName
        );
    }
}