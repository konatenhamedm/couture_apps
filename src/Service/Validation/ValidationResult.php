<?php

namespace App\Service\Validation;

/**
 * Résultat d'une validation d'entité
 */
class ValidationResult
{
    private bool $isValid;
    private array $errors;
    private array $warnings;
    private ?object $correctedEntity;

    public function __construct(
        bool $isValid = true,
        array $errors = [],
        array $warnings = [],
        ?object $correctedEntity = null
    ) {
        $this->isValid = $isValid;
        $this->errors = $errors;
        $this->warnings = $warnings;
        $this->correctedEntity = $correctedEntity;
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function getCorrectedEntity(): ?object
    {
        return $this->correctedEntity;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    public function addError(string $error): self
    {
        $this->errors[] = $error;
        $this->isValid = false;
        return $this;
    }

    public function addWarning(string $warning): self
    {
        $this->warnings[] = $warning;
        return $this;
    }

    public function setCorrectedEntity(object $entity): self
    {
        $this->correctedEntity = $entity;
        return $this;
    }

    /**
     * Combine ce résultat avec un autre
     */
    public function merge(ValidationResult $other): self
    {
        $this->errors = array_merge($this->errors, $other->getErrors());
        $this->warnings = array_merge($this->warnings, $other->getWarnings());
        
        if (!$other->isValid()) {
            $this->isValid = false;
        }

        return $this;
    }

    /**
     * Retourne un message d'erreur formaté
     */
    public function getFormattedErrors(): string
    {
        if (empty($this->errors)) {
            return '';
        }

        return implode('; ', $this->errors);
    }

    /**
     * Retourne un message d'avertissement formaté
     */
    public function getFormattedWarnings(): string
    {
        if (empty($this->warnings)) {
            return '';
        }

        return implode('; ', $this->warnings);
    }
}