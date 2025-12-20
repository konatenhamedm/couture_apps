<?php

namespace App\Repository\Exception;

/**
 * Base repository exception
 * 
 * All repository-related exceptions should extend this class
 * to provide consistent error handling across the application.
 */
class RepositoryException extends \Exception
{
    protected string $repositoryClass = '';
    protected string $methodName = '';
    protected array $context = [];

    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        string $repositoryClass = '',
        string $methodName = '',
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        
        $this->repositoryClass = $repositoryClass;
        $this->methodName = $methodName;
        $this->context = $context;
    }

    public function getRepositoryClass(): string
    {
        return $this->repositoryClass;
    }

    public function getMethodName(): string
    {
        return $this->methodName;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get a formatted error message with context
     */
    public function getFormattedMessage(): string
    {
        $message = $this->getMessage();
        
        if ($this->repositoryClass) {
            $message .= " [Repository: {$this->repositoryClass}]";
        }
        
        if ($this->methodName) {
            $message .= " [Method: {$this->methodName}]";
        }
        
        if (!empty($this->context)) {
            $contextStr = json_encode($this->context, JSON_UNESCAPED_UNICODE);
            $message .= " [Context: {$contextStr}]";
        }
        
        return $message;
    }

    /**
     * Create exception with repository context
     */
    public static function withContext(
        string $message,
        string $repositoryClass,
        string $methodName,
        array $context = [],
        ?\Throwable $previous = null
    ): static {
        return new static($message, 0, $previous, $repositoryClass, $methodName, $context);
    }
}