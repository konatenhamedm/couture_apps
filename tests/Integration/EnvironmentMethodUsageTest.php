<?php

namespace App\Tests\Integration;

use App\Service\ControllerAnalysisService;
use App\Service\ValidationService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Finder\Finder;

/**
 * Test d'intégration pour l'usage uniforme de la méthode d'environnement
 * **Feature: controller-environment-standardization, Property 1: Environment Method Usage Uniformity**
 * **Validates: Requirements 1.1, 1.2, 1.3, 1.4, 1.5**
 */
class EnvironmentMethodUsageTest extends TestCase
{
    private ControllerAnalysisService $analysisService;
    private ValidationService $validationService;
    private string $controllersPath;
    
    protected function setUp(): void
    {
        $parameterBag = new ParameterBag(['kernel.project_dir' => __DIR__ . '/../../']);
        $this->analysisService = new ControllerAnalysisService($parameterBag);
        $this->validationService = new ValidationService($this->analysisService);
        $this->controllersPath = __DIR__ . '/../../src/Controller/Apis';
    }
    
    /**
     * Property Test: Environment Method Usage Uniformity
     * For any Controller_API method that receives a URL_ID_Parameter, 
     * the method should use findInEnvironment($id) and not use any Repository_Standard_Method
     */
    public function testEnvironmentMethodUsageUniformity(): void
    {
        $finder = new Finder();
        $finder->files()->in($this->controllersPath)->name('*.php');
        
        $nonCompliantControllers = [];
        $totalControllersWithIdRoutes = 0;
        
        foreach ($finder as $file) {
            $controllerPath = $file->getRealPath();
            $controllerName = $file->getBasename('.php');
            
            $analysis = $this->analysisService->analyzeController($controllerPath);
            
            // Si le contrôleur a des routes avec {id}, il doit être conforme
            if (!empty($analysis['routesWithId'])) {
                $totalControllersWithIdRoutes++;
                
                if (!empty($analysis['nonCompliantMethods'])) {
                    $nonCompliantControllers[] = [
                        'name' => $controllerName,
                        'path' => $controllerPath,
                        'issues' => $analysis['nonCompliantMethods'],
                        'routes' => $analysis['routesWithId']
                    ];
                }
                
                // Vérifier que chaque route avec {id} a une méthode correspondante qui utilise findInEnvironment
                $content = file_get_contents($controllerPath);
                $validation = $this->validationService->validateEnvironmentMethodUsage($content);
                
                $this->assertTrue(
                    $validation['isValid'],
                    "Controller {$controllerName} has routes with {id} but doesn't use findInEnvironment properly. Issues: " . 
                    json_encode($validation['issues'])
                );
            }
        }
        
        // Vérifier qu'aucun contrôleur avec des routes {id} n'est non-conforme
        $this->assertEmpty(
            $nonCompliantControllers,
            "Found non-compliant controllers with {id} routes: " . 
            implode(', ', array_column($nonCompliantControllers, 'name'))
        );
        
        // Vérifier qu'on a testé au moins quelques contrôleurs
        $this->assertGreaterThan(
            0,
            $totalControllersWithIdRoutes,
            "Should have found at least some controllers with {id} routes"
        );
        
        echo "\n✅ Tested {$totalControllersWithIdRoutes} controllers with {id} routes - all compliant!\n";
    }
    
    /**
     * Test pour vérifier qu'aucun pattern interdit n'est utilisé
     */
    public function testNoForbiddenPatternsInControllers(): void
    {
        $finder = new Finder();
        $finder->files()->in($this->controllersPath)->name('*.php');
        
        $controllersWithForbiddenPatterns = [];
        
        foreach ($finder as $file) {
            $controllerPath = $file->getRealPath();
            $controllerName = $file->getBasename('.php');
            $content = file_get_contents($controllerPath);
            
            // Vérifier les patterns interdits
            $forbiddenPatterns = [
                'find($id)' => '/\$\w+(?:Repository)?->find\(\$id\)/',
                'findOneBy([\'id\' => $id])' => '/\$\w+(?:Repository)?->findOneBy\(\s*\[\s*[\'"]id[\'"]\s*=>\s*\$id\s*\]\s*\)/'
            ];
            
            foreach ($forbiddenPatterns as $patternName => $regex) {
                if (preg_match($regex, $content, $matches)) {
                    $controllersWithForbiddenPatterns[] = [
                        'controller' => $controllerName,
                        'pattern' => $patternName,
                        'match' => $matches[0]
                    ];
                }
            }
        }
        
        $this->assertEmpty(
            $controllersWithForbiddenPatterns,
            "Found forbidden patterns in controllers: " . json_encode($controllersWithForbiddenPatterns, JSON_PRETTY_PRINT)
        );
    }
    
    /**
     * Test pour vérifier la cohérence globale
     */
    public function testGlobalConsistency(): void
    {
        $consistencyReport = $this->validationService->validateConsistency();
        
        $this->assertTrue(
            $consistencyReport['isConsistent'],
            "Global consistency check failed. Issues: " . json_encode($consistencyReport['issues'], JSON_PRETTY_PRINT)
        );
        
        // Vérifier que tous les contrôleurs avec des routes {id} utilisent findInEnvironment
        foreach ($consistencyReport['usage'] as $controllerName => $usage) {
            if ($usage['routesWithId'] > 0) {
                $this->assertTrue(
                    $usage['isConsistent'],
                    "Controller {$controllerName} has {id} routes but inconsistent findInEnvironment usage"
                );
            }
        }
    }
    
    /**
     * Test de régression pour s'assurer qu'ApiModuleAbonnementController est maintenant conforme
     */
    public function testApiModuleAbonnementControllerCompliance(): void
    {
        $controllerPath = $this->controllersPath . '/ApiModuleAbonnementController.php';
        $analysis = $this->analysisService->analyzeController($controllerPath);
        
        $this->assertEmpty(
            $analysis['nonCompliantMethods'],
            "ApiModuleAbonnementController should be compliant after transformation"
        );
        
        $this->assertNotEmpty(
            $analysis['routesWithId'],
            "ApiModuleAbonnementController should have routes with {id}"
        );
        
        // Vérifier que le contenu utilise findInEnvironment
        $content = file_get_contents($controllerPath);
        $this->assertStringContainsString(
            'findInEnvironment($id)',
            $content,
            "ApiModuleAbonnementController should use findInEnvironment"
        );
    }
}