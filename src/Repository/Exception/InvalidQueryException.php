<?php

namespace App\Repository\Exception;

/**
 * Exception thrown when a query is malformed or invalid
 * 
 * This exception is thrown when DQL queries are syntactically
 * incorrect or contain invalid references.
 */
class InvalidQueryException extends RepositoryException
{
    private string $dql = '';
    private array $queryErrors = [];

    public function __construct(
        string $message = '',
        string $dql = '',
        array $queryErrors = [],
        int $code = 0,
        ?\Throwable $previous = null,
        string $repositoryClass = '',
        string $methodName = '',
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $repositoryClass, $methodName, $context);
        
        $this->dql = $dql;
        $this->queryErrors = $queryErrors;
    }

    public function getDql(): string
    {
        return $this->dql;
    }

    public function getQueryErrors(): array
    {
        return $this->queryErrors;
    }

    /**
     * Create exception for syntax errors
     */
    public static function syntaxError(
        string $dql,
        string $error,
        string $repositoryClass,
        string $methodName
    ): static {
        $message = "Query syntax error: {$error}";
        
        return new static(
            $message,
            $dql,
            ['syntax_error' => $error],
            0,
            null,
            $repositoryClass,
            $methodName,
            ['type' => 'syntax_error']
        );
    }

    /**
     * Create exception for invalid entity references
     */
    public static function invalidEntityReference(
        string $dql,
        string $entityName,
        string $repositoryClass,
        string $methodName
    ): static {
        $message = "Invalid entity reference: {$entityName}";
        
        return new static(
            $message,
            $dql,
            ['invalid_entity' => $entityName],
            0,
            null,
            $repositoryClass,
            $methodName,
            ['type' => 'invalid_entity_reference']
        );
    }

    /**
     * Create exception for invalid field references
     */
    public static function invalidFieldReference(
        string $dql,
        string $fieldName,
        string $entityName,
        string $repositoryClass,
        string $methodName
    ): static {
        $message = "Invalid field reference: {$fieldName} in entity {$entityName}";
        
        return new static(
            $message,
            $dql,
            ['invalid_field' => $fieldName, 'entity' => $entityName],
            0,
            null,
            $repositoryClass,
            $methodName,
            ['type' => 'invalid_field_reference']
        );
    }
}