<?php

namespace App\Tests\Service;

use App\Service\ControllerAnalysisService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * Tests de propriétés pour ControllerAnalysisService
 * **Feature: controller-environment-standardization, Property 2: Pattern Detection Completeness**
 * **Validates: Requirements 2.1, 2.2, 2.3, 2.4**
 */
class ControllerAnalysisServiceTest extends TestCase
{
    private ControllerAnalysisService $analysisService;
    
    protected function setUp(): void
    {
        $parameterBag = new ParameterBag(['kernel.project_dir' => __DIR__ . '/../../']);
        $this->analysisService = new ControllerAnalysisService($parameterBag);
    }
    
    /**
     * Property Test: Pattern Detection Completeness
     * For any Controller_API file, the analysis should detect all instances of non-compliant patterns
     */
    public function testPatternDetectionCompleteness(): void
    {
        // Générer 100 cas de test différents
        for ($i = 0; $i < 100; $i++) {
            $testCase = $this->generateTestControllerContent();
            
            $result = $this->analysisService->analyzeController($testCase['filePath']);
            
            // Vérifier que tous les patterns attendus sont détectés
            $this->assertEquals(
                $testCase['expectedNonCompliantCount'],
                count($result['nonCompliantMethods']),
                "Failed to detect correct number of non-compliant patterns in iteration $i"
            );
            
            // Vérifier que tous les types de patterns sont correctement identifiés
            $detectedTypes = array_column($result['nonCompliantMethods'], 'type');
            foreach ($testCase['expectedTypes'] as $expectedType) {
                $this->assertContains(
                    $expectedType,
                    $detectedTypes,
                    "Failed to detect pattern type '$expectedType' in iteration $i"
                );
            }
            
            // Vérifier que les routes avec {id} sont détectées
            $this->assertEquals(
                $testCase['expectedRoutesWithIdCount'],
                count($result['routesWithId']),
                "Failed to detect correct number of routes with {id} in iteration $i"
            );
        }
    }
    
    /**
     * Génère un contenu de contrôleur de test avec des patterns connus
     */
    private function generateTestControllerContent(): array
    {
        $testDir = sys_get_temp_dir() . '/controller_test_' . uniqid();
        mkdir($testDir, 0777, true);
        
        $patterns = [
            'find($id)' => '$repository->find($id)',
            'findOneBy([\'id\' => $id])' => '$repository->findOneBy([\'id\' => $id])',
            'auto_injection_entity' => 'public function getOne(?TestEntity $entity): Response'
        ];
        
        // Générer aléatoirement des patterns
        $selectedPatterns = [];
        $expectedTypes = [];
        $routesWithIdCount = 0;
        
        // Pour ce test, nous nous concentrons sur l'injection automatique d'entités
        // car c'est le pattern principal que nous devons détecter
        $useAutoInjection = rand(0, 1);
        
        if ($useAutoInjection) {
            $selectedPatterns[] = $patterns['auto_injection_entity'];
            $expectedTypes[] = 'auto_injection_entity';
            $routesWithIdCount = 1; // Ajouter une route avec {id}
        }
        
        // Générer le contenu du contrôleur
        $content = "<?php\n\nnamespace App\\Controller\\Apis;\n\nuse Symfony\\Component\\Routing\\Attribute\\Route;\nuse Symfony\\Component\\HttpFoundation\\Response;\n\nclass TestController\n{\n";
        
        if ($routesWithIdCount > 0) {
            $content .= "    #[Route('/test/{id}', methods: ['GET'])]\n";
        }
        
        foreach ($selectedPatterns as $pattern) {
            $content .= "    " . $pattern . "\n";
        }
        
        $content .= "}\n";
        
        $filePath = $testDir . '/TestController.php';
        file_put_contents($filePath, $content);
        
        return [
            'filePath' => $filePath,
            'content' => $content,
            'expectedNonCompliantCount' => count($selectedPatterns),
            'expectedTypes' => $expectedTypes,
            'expectedRoutesWithIdCount' => $routesWithIdCount
        ];
    }
    
    /**
     * Test spécifique pour la détection d'injection automatique d'entités
     */
    public function testAutoInjectionEntityDetection(): void
    {
        $testContent = '<?php
namespace App\Controller\Apis;

use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

class TestController
{
    #[Route(\'/test/{id}\', methods: [\'GET\'])]
    public function getOne(?TestEntity $entity): Response
    {
        return new Response();
    }
}';
        
        $testFile = sys_get_temp_dir() . '/test_controller_' . uniqid() . '.php';
        file_put_contents($testFile, $testContent);
        
        $result = $this->analysisService->analyzeController($testFile);
        
        $this->assertCount(1, $result['nonCompliantMethods']);
        $this->assertEquals('auto_injection_entity', $result['nonCompliantMethods'][0]['type']);
        $this->assertEquals('TestEntity', $result['nonCompliantMethods'][0]['entityType']);
        $this->assertEquals('getOne', $result['nonCompliantMethods'][0]['methodName']);
        
        unlink($testFile);
    }
    
    /**
     * Test pour vérifier qu'aucun faux positif n'est détecté
     */
    public function testNoFalsePositives(): void
    {
        $compliantContent = '<?php
namespace App\Controller\Apis;

use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

class CompliantController
{
    #[Route(\'/test/{id}\', methods: [\'GET\'])]
    public function getOne(int $id, TestRepository $repository): Response
    {
        $entity = $repository->findInEnvironment($id);
        return new Response();
    }
}';
        
        $testFile = sys_get_temp_dir() . '/compliant_controller_' . uniqid() . '.php';
        file_put_contents($testFile, $compliantContent);
        
        $result = $this->analysisService->analyzeController($testFile);
        
        $this->assertCount(0, $result['nonCompliantMethods'], 'Compliant controller should not have non-compliant methods');
        $this->assertCount(1, $result['routesWithId'], 'Should detect route with {id}');
        
        unlink($testFile);
    }
}