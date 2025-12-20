<?php

namespace App\Repository\Exception;

/**
 * Exception thrown when backward compatibility is broken during migration
 * 
 * This exception is thrown when repository migration would break
 * existing code that depends on the current repository interface.
 */
class BackwardCompatibilityException extends MigrationException
{
    private array $breakingChanges = [];
    private array $affectedMethods = [];

    public function __construct(
        string $message = '',
        array $breakingChanges = [],
        array $affectedMethods = [],
        string $migrationStep = '',
        array $migrationData = [],
        int $code = 0,
        ?\Throwable $previous = null,
        string $repositoryClass = '',
        string $methodName = '',
        array $context = []
    ) {
        parent::__construct($message, $migrationStep, $migrationData, $code, $previous, $repositoryClass, $methodName, $context);
        
        $this->breakingChanges = $breakingChanges;
        $this->affectedMethods = $affectedMethods;
    }

    public function getBreakingChanges(): array
    {
        return $this->breakingChanges;
    }

    public function getAffectedMethods(): array
    {
        return $this->affectedMethods;
    }

    /**
     * Create exception for method signature changes
     */
    public static function methodSignatureChanged(
        string $repositoryClass,
        string $methodName,
        array $oldSignature,
        array $newSignature
    ): static {
        $message = "Method signature change would break backward compatibility: {$repositoryClass}::{$methodName}";
        
        return new static(
            $message,
            [
                'type' => 'method_signature_change',
                'method' => $methodName,
                'old_signature' => $oldSignature,
                'new_signature' => $newSignature
            ],
            [$methodName],
            'signature_migration',
            ['old' => $oldSignature, 'new' => $newSignature],
            0,
            null,
            $repositoryClass,
            $methodName
        );
    }

    /**
     * Create exception for removed methods
     */
    public static function methodRemoved(
        string $repositoryClass,
        string $methodName,
        array $dependentClasses = []
    ): static {
        $message = "Removing method would break backward compatibility: {$repositoryClass}::{$methodName}";
        
        return new static(
            $message,
            [
                'type' => 'method_removal',
                'method' => $methodName,
                'dependent_classes' => $dependentClasses
            ],
            [$methodName],
            'method_removal',
            ['dependent_classes' => $dependentClasses],
            0,
            null,
            $repositoryClass,
            $methodName
        );
    }

    /**
     * Create exception for return type changes
     */
    public static function returnTypeChanged(
        string $repositoryClass,
        string $methodName,
        string $oldType,
        string $newType
    ): static {
        $message = "Return type change would break backward compatibility: {$repositoryClass}::{$methodName} ({$oldType} -> {$newType})";
        
        return new static(
            $message,
            [
                'type' => 'return_type_change',
                'method' => $methodName,
                'old_type' => $oldType,
                'new_type' => $newType
            ],
            [$methodName],
            'return_type_migration',
            ['old_type' => $oldType, 'new_type' => $newType],
            0,
            null,
            $repositoryClass,
            $methodName
        );
    }
}