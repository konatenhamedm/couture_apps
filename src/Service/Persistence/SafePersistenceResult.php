<?php

namespace App\Service\Persistence;

/**
 * Résultat d'une opération de persistance sécurisée
 */
class SafePersistenceResult
{
    private bool $success = false;
    private array $errors = [];
    private array $warnings = [];
    private array $info = [];
    private string $message = '';
    private ?object $entity = null;

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success): self
    {
        $this->success = $success;
        return $this;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function addError(string $error): self
    {
        $this->errors[] = $error;
        return $this;
    }

    public function addErrors(array $errors): self
    {
        $this->errors = array_merge($this->errors, $errors);
        return $this;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function addWarning(string $warning): self
    {
        $this->warnings[] = $warning;
        return $this;
    }

    public function addWarnings(array $warnings): self
    {
        $this->warnings = array_merge($this->warnings, $warnings);
        return $this;
    }

    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    public function getInfo(): array
    {
        return $this->info;
    }

    public function addInfo(string $info): self
    {
        $this->info[] = $info;
        return $this;
    }

    public function addInfos(array $infos): self
    {
        $this->info = array_merge($this->info, $infos);
        return $this;
    }

    public function hasInfo(): bool
    {
        return !empty($this->info);
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function getEntity(): ?object
    {
        return $this->entity;
    }

    public function setEntity(?object $entity): self
    {
        $this->entity = $entity;
        return $this;
    }

    /**
     * Retourne tous les messages formatés
     */
    public function getFormattedMessages(): string
    {
        $messages = [];
        
        if (!empty($this->message)) {
            $messages[] = $this->message;
        }
        
        if (!empty($this->errors)) {
            $messages[] = "Erreurs: " . implode(', ', $this->errors);
        }
        
        if (!empty($this->warnings)) {
            $messages[] = "Avertissements: " . implode(', ', $this->warnings);
        }
        
        if (!empty($this->info)) {
            $messages[] = "Informations: " . implode(', ', $this->info);
        }
        
        return implode(' | ', $messages);
    }

    /**
     * Retourne un tableau pour la sérialisation JSON
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'info' => $this->info,
            'entity_id' => $this->entity && method_exists($this->entity, 'getId') ? $this->entity->getId() : null
        ];
    }
}