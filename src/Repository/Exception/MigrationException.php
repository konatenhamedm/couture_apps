<?php

namespace App\Repository\Exception;

/**
 * Exception thrown during repository migration processes
 * 
 * This exception is thrown when repository migration or
 * refactoring operations encounter errors.
 */
class MigrationException extends RepositoryException
{
    private string $migrationStep = '';
    private array $migrationData = [];

    public function __construct(
        string $message = '',
        string $migrationStep = '',
        array $migrationData = [],
        int $code = 0,
        ?\Throwable $previous = null,
        string $repositoryClass = '',
        string $methodName = '',
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $repositoryClass, $methodName, $context);
        
        $this->migrationStep = $migrationStep;
        $this->migrationData = $migrationData;
    }

    public function getMigrationStep(): string
    {
        return $this->migrationStep;
    }

    public function getMigrationData(): array
    {
        return $this->migrationData;
    }

    /**
     * Create exception for failed repository analysis
     */
    public static function analysisFailed(
        string $repositoryClass,
        string $reason,
        array $analysisData = []
    ): static {
        $message = "Repository analysis failed for {$repositoryClass}: {$reason}";
        
        return new static(
            $message,
            'analysis',
            $analysisData,
            0,
            null,
            $repositoryClass,
            'analyze'
        );
    }

    /**
     * Create exception for failed code generation
     */
    public static function codeGenerationFailed(
        string $repositoryClass,
        string $reason,
        array $generationData = []
    ): static {
        $message = "Code generation failed for {$repositoryClass}: {$reason}";
        
        return new static(
            $message,
            'code_generation',
            $generationData,
            0,
            null,
            $repositoryClass,
            'generateCode'
        );
    }

    /**
     * Create exception for failed validation
     */
    public static function validationFailed(
        string $repositoryClass,
        array $validationErrors
    ): static {
        $message = "Repository validation failed for {$repositoryClass}";
        
        return new static(
            $message,
            'validation',
            ['errors' => $validationErrors],
            0,
            null,
            $repositoryClass,
            'validate'
        );
    }
}