<?php

namespace App\Tests\Controller\ApiClient\Utils;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Utility class for cleaning up test data and resources
 */
class TestCleanupUtility
{
    private EntityManagerInterface $entityManager;
    private array $createdFiles = [];
    private array $createdDirectories = [];
    
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    
    /**
     * Clean up all test data and resources
     */
    public function cleanupAll(): void
    {
        $this->cleanupDatabase();
        $this->cleanupFiles();
        $this->cleanupDirectories();
    }
    
    /**
     * Clean up database (rollback transactions)
     */
    public function cleanupDatabase(): void
    {
        if ($this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->rollback();
        }
        
        // Clear entity manager to prevent memory leaks
        $this->entityManager->clear();
    }
    
    /**
     * Clean up created files
     */
    public function cleanupFiles(): void
    {
        foreach ($this->createdFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        $this->createdFiles = [];
    }
    
    /**
     * Clean up created directories
     */
    public function cleanupDirectories(): void
    {
        foreach ($this->createdDirectories as $directory) {
            if (is_dir($directory)) {
                $this->removeDirectory($directory);
            }
        }
        $this->createdDirectories = [];
    }
    
    /**
     * Register a file for cleanup
     */
    public function registerFile(string $filePath): void
    {
        $this->createdFiles[] = $filePath;
    }
    
    /**
     * Register a directory for cleanup
     */
    public function registerDirectory(string $directoryPath): void
    {
        $this->createdDirectories[] = $directoryPath;
    }
    
    /**
     * Remove directory recursively
     */
    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        
        $files = array_diff(scandir($directory), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $directory . DIRECTORY_SEPARATOR . $file;
            
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($directory);
    }
    
    /**
     * Clean up specific entity types
     */
    public function cleanupEntities(array $entityClasses): void
    {
        foreach ($entityClasses as $entityClass) {
            $repository = $this->entityManager->getRepository($entityClass);
            $entities = $repository->findAll();
            
            foreach ($entities as $entity) {
                $this->entityManager->remove($entity);
            }
        }
        
        $this->entityManager->flush();
    }
    
    /**
     * Reset auto-increment counters (for consistent test IDs)
     */
    public function resetAutoIncrement(array $tableNames): void
    {
        $connection = $this->entityManager->getConnection();
        
        foreach ($tableNames as $tableName) {
            try {
                // For MySQL
                $connection->executeStatement("ALTER TABLE {$tableName} AUTO_INCREMENT = 1");
            } catch (\Exception $e) {
                try {
                    // For SQLite
                    $connection->executeStatement("DELETE FROM sqlite_sequence WHERE name = '{$tableName}'");
                } catch (\Exception $e2) {
                    // Ignore if table doesn't exist or other DB type
                }
            }
        }
    }
    
    /**
     * Create temporary directory for tests
     */
    public function createTempDirectory(string $prefix = 'test_'): string
    {
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $prefix . uniqid();
        
        if (!mkdir($tempDir, 0777, true)) {
            throw new \RuntimeException("Failed to create temporary directory: {$tempDir}");
        }
        
        $this->registerDirectory($tempDir);
        
        return $tempDir;
    }
    
    /**
     * Create temporary file for tests
     */
    public function createTempFile(string $content = '', string $prefix = 'test_', string $suffix = '.tmp'): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), $prefix) . $suffix;
        
        if (file_put_contents($tempFile, $content) === false) {
            throw new \RuntimeException("Failed to create temporary file: {$tempFile}");
        }
        
        $this->registerFile($tempFile);
        
        return $tempFile;
    }
    
    /**
     * Get memory usage information
     */
    public function getMemoryUsage(): array
    {
        return [
            'current' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'current_formatted' => $this->formatBytes(memory_get_usage(true)),
            'peak_formatted' => $this->formatBytes(memory_get_peak_usage(true))
        ];
    }
    
    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
    
    /**
     * Log cleanup statistics
     */
    public function logCleanupStats(): void
    {
        $stats = [
            'files_cleaned' => count($this->createdFiles),
            'directories_cleaned' => count($this->createdDirectories),
            'memory_usage' => $this->getMemoryUsage()
        ];
        
        error_log('Test cleanup stats: ' . json_encode($stats));
    }
    
    /**
     * Verify cleanup was successful
     */
    public function verifyCleanup(): bool
    {
        // Check if any registered files still exist
        foreach ($this->createdFiles as $file) {
            if (file_exists($file)) {
                return false;
            }
        }
        
        // Check if any registered directories still exist
        foreach ($this->createdDirectories as $directory) {
            if (is_dir($directory)) {
                return false;
            }
        }
        
        // Check if database transaction is still active
        if ($this->entityManager->getConnection()->isTransactionActive()) {
            return false;
        }
        
        return true;
    }
}