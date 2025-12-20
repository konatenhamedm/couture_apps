<?php

namespace App\Repository\Exception;

/**
 * Exception thrown when query execution fails
 * 
 * This exception wraps Doctrine exceptions and provides
 * additional context about the failed query.
 */
class QueryExecutionException extends RepositoryException
{
    private string $queryType = '';
    private string $dql = '';
    private array $parameters = [];

    public function __construct(
        string $message = '',
        string $queryType = '',
        string $dql = '',
        array $parameters = [],
        int $code = 0,
        ?\Throwable $previous = null,
        string $repositoryClass = '',
        string $methodName = '',
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $repositoryClass, $methodName, $context);
        
        $this->queryType = $queryType;
        $this->dql = $dql;
        $this->parameters = $parameters;
    }

    public function getQueryType(): string
    {
        return $this->queryType;
    }

    public function getDql(): string
    {
        return $this->dql;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Create exception for failed SELECT queries
     */
    public static function selectFailed(
        string $dql,
        array $parameters,
        \Throwable $previous,
        string $repositoryClass,
        string $methodName
    ): static {
        $message = "SELECT query execution failed: " . $previous->getMessage();
        
        return new static(
            $message,
            'SELECT',
            $dql,
            $parameters,
            0,
            $previous,
            $repositoryClass,
            $methodName
        );
    }

    /**
     * Create exception for failed COUNT queries
     */
    public static function countFailed(
        string $dql,
        array $parameters,
        \Throwable $previous,
        string $repositoryClass,
        string $methodName
    ): static {
        $message = "COUNT query execution failed: " . $previous->getMessage();
        
        return new static(
            $message,
            'COUNT',
            $dql,
            $parameters,
            0,
            $previous,
            $repositoryClass,
            $methodName
        );
    }

    /**
     * Create exception for failed UPDATE queries
     */
    public static function updateFailed(
        string $dql,
        array $parameters,
        \Throwable $previous,
        string $repositoryClass,
        string $methodName
    ): static {
        $message = "UPDATE query execution failed: " . $previous->getMessage();
        
        return new static(
            $message,
            'UPDATE',
            $dql,
            $parameters,
            0,
            $previous,
            $repositoryClass,
            $methodName
        );
    }

    /**
     * Create exception for failed DELETE queries
     */
    public static function deleteFailed(
        string $dql,
        array $parameters,
        \Throwable $previous,
        string $repositoryClass,
        string $methodName
    ): static {
        $message = "DELETE query execution failed: " . $previous->getMessage();
        
        return new static(
            $message,
            'DELETE',
            $dql,
            $parameters,
            0,
            $previous,
            $repositoryClass,
            $methodName
        );
    }
}