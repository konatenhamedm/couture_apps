<?php

namespace App\Repository\Validation;

use App\Repository\Validation\Rules\InterfaceComplianceRule;
use App\Repository\Validation\Rules\NamingConventionRule;
use App\Repository\Validation\Rules\ReturnTypeRule;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

class RepositoryValidator
{
    private array $rules = [];
    private string $repositoryNamespace;

    public function __construct(string $repositoryNamespace = 'App\\Repository')
    {
        $this->repositoryNamespace = $repositoryNamespace;
        $this->initializeDefaultRules();
    }

    private function initializeDefaultRules(): void
    {
        $this->addRule(new InterfaceComplianceRule());
        $this->addRule(new NamingConventionRule());
        $this->addRule(new ReturnTypeRule());
    }

    public function addRule(RepositoryValidationRule $rule): void
    {
        $this->rules[] = $rule;
        
        // Sort rules by priority (higher priority first)
        usort($this->rules, function (RepositoryValidationRule $a, RepositoryValidationRule $b) {
            return $b->getPriority() <=> $a->getPriority();
        });
    }

    public function removeRule(string $ruleName): void
    {
        $this->rules = array_filter($this->rules, function (RepositoryValidationRule $rule) use ($ruleName) {
            return $rule->getName() !== $ruleName;
        });
    }

    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * Validate a single repository class
     */
    public function validateRepository(string $repositoryClassName): ValidationResult
    {
        try {
            $reflection = new ReflectionClass($repositoryClassName);
            return $this->validateRepositoryClass($reflection);
        } catch (\ReflectionException $e) {
            return new ValidationResult(false, [
                sprintf('Could not load repository class %s: %s', $repositoryClassName, $e->getMessage())
            ]);
        }
    }

    /**
     * Validate a repository class using reflection
     */
    public function validateRepositoryClass(ReflectionClass $repositoryClass): ValidationResult
    {
        $overallResult = new ValidationResult();

        foreach ($this->rules as $rule) {
            if (!$rule->appliesTo($repositoryClass)) {
                continue;
            }

            $ruleResult = $rule->validate($repositoryClass);
            $overallResult = $overallResult->merge($ruleResult);
        }

        return $overallResult;
    }

    /**
     * Validate all repositories in the project
     */
    public function validateAllRepositories(string $repositoryPath = null): array
    {
        $repositoryPath = $repositoryPath ?? $this->getDefaultRepositoryPath();
        $results = [];

        foreach ($this->findRepositoryClasses($repositoryPath) as $className) {
            $results[$className] = $this->validateRepository($className);
        }

        return $results;
    }

    /**
     * Get validation summary for all repositories
     */
    public function getValidationSummary(array $validationResults): array
    {
        $summary = [
            'total_repositories' => count($validationResults),
            'valid_repositories' => 0,
            'repositories_with_errors' => 0,
            'repositories_with_warnings' => 0,
            'total_errors' => 0,
            'total_warnings' => 0,
            'error_details' => [],
            'warning_details' => []
        ];

        foreach ($validationResults as $className => $result) {
            if ($result->isValid() && !$result->hasWarnings()) {
                $summary['valid_repositories']++;
            }

            if ($result->hasErrors()) {
                $summary['repositories_with_errors']++;
                $summary['total_errors'] += $result->getErrorCount();
                $summary['error_details'][$className] = $result->getErrors();
            }

            if ($result->hasWarnings()) {
                $summary['repositories_with_warnings']++;
                $summary['total_warnings'] += $result->getWarningCount();
                $summary['warning_details'][$className] = $result->getWarnings();
            }
        }

        return $summary;
    }

    /**
     * Find all repository classes in the given path
     */
    private function findRepositoryClasses(string $repositoryPath): array
    {
        $classes = [];

        if (!is_dir($repositoryPath)) {
            return $classes;
        }

        $finder = new Finder();
        $finder->files()->in($repositoryPath)->name('*Repository.php');

        foreach ($finder as $file) {
            $className = $this->getClassNameFromFile($file->getRealPath(), $repositoryPath);
            if ($className && class_exists($className)) {
                $classes[] = $className;
            }
        }

        return $classes;
    }

    /**
     * Extract class name from file path
     */
    private function getClassNameFromFile(string $filePath, string $basePath): ?string
    {
        $relativePath = str_replace($basePath, '', $filePath);
        $relativePath = ltrim($relativePath, '/\\');
        $relativePath = str_replace(['/', '\\'], '\\', $relativePath);
        $relativePath = str_replace('.php', '', $relativePath);

        return $this->repositoryNamespace . '\\' . $relativePath;
    }

    /**
     * Get default repository path
     */
    private function getDefaultRepositoryPath(): string
    {
        return __DIR__ . '/../../Repository';
    }

    /**
     * Check if a repository is compliant with all rules
     */
    public function isRepositoryCompliant(string $repositoryClassName): bool
    {
        $result = $this->validateRepository($repositoryClassName);
        return $result->isValid();
    }

    /**
     * Get repositories that need migration
     */
    public function getRepositoriesNeedingMigration(string $repositoryPath = null): array
    {
        $results = $this->validateAllRepositories($repositoryPath);
        $needingMigration = [];

        foreach ($results as $className => $result) {
            if (!$result->isValid()) {
                $needingMigration[$className] = $result;
            }
        }

        return $needingMigration;
    }

    /**
     * Generate validation report
     */
    public function generateReport(array $validationResults): string
    {
        $summary = $this->getValidationSummary($validationResults);
        
        $report = "Repository Validation Report\n";
        $report .= "===========================\n\n";
        
        $report .= sprintf("Total Repositories: %d\n", $summary['total_repositories']);
        $report .= sprintf("Valid Repositories: %d\n", $summary['valid_repositories']);
        $report .= sprintf("Repositories with Errors: %d\n", $summary['repositories_with_errors']);
        $report .= sprintf("Repositories with Warnings: %d\n", $summary['repositories_with_warnings']);
        $report .= sprintf("Total Errors: %d\n", $summary['total_errors']);
        $report .= sprintf("Total Warnings: %d\n\n", $summary['total_warnings']);

        if (!empty($summary['error_details'])) {
            $report .= "ERRORS:\n";
            $report .= "-------\n";
            foreach ($summary['error_details'] as $className => $errors) {
                $report .= sprintf("\n%s:\n", $className);
                foreach ($errors as $error) {
                    $report .= sprintf("  - %s\n", $error);
                }
            }
            $report .= "\n";
        }

        if (!empty($summary['warning_details'])) {
            $report .= "WARNINGS:\n";
            $report .= "---------\n";
            foreach ($summary['warning_details'] as $className => $warnings) {
                $report .= sprintf("\n%s:\n", $className);
                foreach ($warnings as $warning) {
                    $report .= sprintf("  - %s\n", $warning);
                }
            }
        }

        return $report;
    }
}