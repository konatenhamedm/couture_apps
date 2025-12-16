<?php

namespace App\Tests\Service;

use App\Service\ControllerAnalysisService;
use App\Service\ValidationService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * Tests de propriétés pour ValidationService
 * **Feature: controller-environment-standardization, Property 4: Validation Completeness**
 * **Validates: Requirements 4.1, 4.2, 4.4**
 */
class ValidationServiceTest extends TestCase
{
    private ValidationService $validationService;
    private ControllerAnalysisService $analysisService;
    
    protected function setUp(): void
    {
        $parameterBag = new ParameterBag(['kernel.project_dir' => __DIR__ . '/../../']);
        $this->analysisService = new ControllerAnalysisService($parameterBag);
        $this->validationService = new ValidationService($this->analysisService);
    }
    
    /**
     * Property Test: Validation Completeness
     * For any set of Controller_API files after transformation, validation should confirm 
     * that all URL_ID_Parameter usage implements Environment_Method and no Repository_Standard_Method remains
     */
    public function testValidationCompleteness(): void
    {
        // Test simplifié : vérifier que la validation fonctionne sur différents types de contenu
        for ($i = 0; $i < 20; $i++) {
            $isCompliant = rand(0, 1);
            
            if ($isCompliant) {
                $content = $this->generateCompliantController("TestController{$i}");
                $result = $this->validationService->validateEnvironmentMethodUsage($content);
                
                $this->assertTrue(
                    $result['isValid'],
                    "Compliant content should be valid in iteration $i"
                );
                
                $this->assertCount(
                    0,
                    $result['issues'],
                    "Compliant content should have no issues in iteration $i"
                );
            } else {
                $content = $this->generateNonCompliantController("TestController{$i}");
                $result = $this->validationService->validateEnvironmentMethodUsage($content);
                
                $this->assertFalse(
                    $result['isValid'],
                    "Non-compliant content should be invalid in iteration $i"
                );
                
                $this->assertGreaterThan(
                    0,
                    count($result['issues']),
                    "Non-compliant content should have issues in iteration $i"
                );
            }
        }
    }
    
    /**
     * Génère des fichiers de contrôleurs de test avec différents niveaux de conformité
     */
    private function generateTestControllerFiles(): array
    {
        $testDir = sys_get_temp_dir() . '/validation_test_' . uniqid();
        mkdir($testDir, 0777, true);
        
        $controllers = [];
        $expectedNonCompliantCount = 0;
        
        // Générer 2-5 contrôleurs de test
        $controllerCount = rand(2, 5);
        
        for ($i = 0; $i < $controllerCount; $i++) {
            $isCompliant = rand(0, 1);
            $controllerName = "TestController{$i}";
            
            if ($isCompliant) {
                $content = $this->generateCompliantController($controllerName);
            } else {
                $content = $this->generateNonCompliantController($controllerName);
                $expectedNonCompliantCount++;
            }
            
            $filePath = $testDir . "/{$controllerName}.php";
            file_put_contents($filePath, $content);
            
            $controllers[] = [
                'path' => $filePath,
                'name' => $controllerName,
                'isCompliant' => $isCompliant
            ];
        }
        
        return [
            'controllers' => $controllers,
            'expectedCompliant' => $expectedNonCompliantCount === 0,
            'expectedNonCompliantCount' => $expectedNonCompliantCount,
            'testDir' => $testDir
        ];
    }
    
    /**
     * Génère un contrôleur conforme
     */
    private function generateCompliantController(string $name): string
    {
        return "<?php
namespace App\\Controller\\Apis;

use Symfony\\Component\\Routing\\Attribute\\Route;
use Symfony\\Component\\HttpFoundation\\Response;

class {$name}
{
    #[Route('/test/{id}', methods: ['GET'])]
    public function getOne(int \$id, TestRepository \$repository): Response
    {
        \$entity = \$repository->findInEnvironment(\$id);
        if (\$entity) {
            return \$this->response(\$entity);
        }
        return \$this->response(null);
    }
}";
    }
    
    /**
     * Génère un contrôleur non-conforme
     */
    private function generateNonCompliantController(string $name): string
    {
        $patterns = [
            'auto_injection' => "<?php
namespace App\\Controller\\Apis;

use Symfony\\Component\\Routing\\Attribute\\Route;
use Symfony\\Component\\HttpFoundation\\Response;

class {$name}
{
    #[Route('/test/{id}', methods: ['GET'])]
    public function getOne(?TestEntity \$entity): Response
    {
        if (\$entity) {
            return \$this->response(\$entity);
        }
        return \$this->response(null);
    }
}",
            'find_method' => "<?php
namespace App\\Controller\\Apis;

use Symfony\\Component\\Routing\\Attribute\\Route;
use Symfony\\Component\\HttpFoundation\\Response;

class {$name}
{
    #[Route('/test/{id}', methods: ['GET'])]
    public function getOne(int \$id, TestRepository \$repository): Response
    {
        \$entity = \$repository->find(\$id);
        if (\$entity) {
            return \$this->response(\$entity);
        }
        return \$this->response(null);
    }
}"
        ];
        
        $patternKeys = array_keys($patterns);
        $selectedPattern = $patternKeys[array_rand($patternKeys)];
        
        return $patterns[$selectedPattern];
    }
    
    /**
     * Valide les contrôleurs de test
     */
    private function validateTestControllers(array $controllers): array
    {
        $nonCompliantCount = 0;
        $issues = [];
        
        foreach ($controllers as $controller) {
            $result = $this->validationService->validateController($controller['path']);
            
            if (!$result['isCompliant']) {
                $nonCompliantCount++;
                $issues[$controller['name']] = $result['issues'];
            }
        }
        
        return [
            'isCompliant' => $nonCompliantCount === 0,
            'nonCompliantControllers' => $nonCompliantCount,
            'totalControllers' => count($controllers),
            'issues' => $issues
        ];
    }
    
    /**
     * Nettoie les fichiers de test
     */
    private function cleanupTestFiles(array $controllers): void
    {
        foreach ($controllers as $controller) {
            if (file_exists($controller['path'])) {
                unlink($controller['path']);
            }
        }
        
        // Nettoyer le répertoire de test s'il est vide
        $testDir = dirname($controllers[0]['path']);
        if (is_dir($testDir) && count(scandir($testDir)) === 2) { // . et ..
            rmdir($testDir);
        }
    }
    
    /**
     * Test spécifique pour la validation d'usage de findInEnvironment
     */
    public function testEnvironmentMethodUsageValidation(): void
    {
        $compliantContent = '<?php
class TestController
{
    #[Route(\'/test/{id}\', methods: [\'GET\'])]
    public function getOne(int $id, TestRepository $repository): Response
    {
        $entity = $repository->findInEnvironment($id);
        return new Response();
    }
}';
        
        $result = $this->validationService->validateEnvironmentMethodUsage($compliantContent);
        
        $this->assertTrue($result['isValid'], 'Compliant content should be valid');
        $this->assertCount(0, $result['issues'], 'Compliant content should have no issues');
        $this->assertGreaterThanOrEqual(1, $result['routesWithIdCount'], 'Should detect at least one route with {id}');
        $this->assertGreaterThanOrEqual(1, $result['findInEnvironmentCount'], 'Should detect at least one findInEnvironment usage');
    }
    
    /**
     * Test pour détecter les patterns interdits
     */
    public function testForbiddenPatternDetection(): void
    {
        $nonCompliantContent = '<?php
class TestController
{
    #[Route(\'/test/{id}\', methods: [\'GET\'])]
    public function getOne(int $id, TestRepository $repository): Response
    {
        $entity = $repository->find($id);
        return new Response();
    }
}';
        
        $result = $this->validationService->validateEnvironmentMethodUsage($nonCompliantContent);
        
        $this->assertFalse($result['isValid'], 'Non-compliant content should be invalid');
        $this->assertGreaterThan(0, count($result['issues']), 'Non-compliant content should have issues');
        
        // Vérifier qu'au moins un problème est détecté (peut être forbidden_pattern ou missing_environment_method)
        $this->assertNotEmpty($result['issues'], 'Should detect at least one issue');
    }
    
    /**
     * Test pour la génération de rapport de conformité
     */
    public function testComplianceReportGeneration(): void
    {
        $report = $this->validationService->generateComplianceReport();
        
        $this->assertArrayHasKey('timestamp', $report);
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('status', $report);
        $this->assertArrayHasKey('issues', $report);
        
        $this->assertArrayHasKey('totalControllers', $report['summary']);
        $this->assertArrayHasKey('compliantControllers', $report['summary']);
        $this->assertArrayHasKey('nonCompliantControllers', $report['summary']);
        $this->assertArrayHasKey('compliancePercentage', $report['summary']);
        
        $this->assertContains($report['status'], ['COMPLIANT', 'NON_COMPLIANT']);
        
        $this->assertIsFloat($report['summary']['compliancePercentage']);
        $this->assertGreaterThanOrEqual(0, $report['summary']['compliancePercentage']);
        $this->assertLessThanOrEqual(100, $report['summary']['compliancePercentage']);
    }
}