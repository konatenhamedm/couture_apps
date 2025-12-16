<?php

namespace App\Service;

/**
 * Service de validation pour vérifier la conformité des contrôleurs après transformation
 * Vérifie que tous les contrôleurs utilisent findInEnvironment($id) correctement
 */
class ValidationService
{
    private ControllerAnalysisService $analysisService;
    
    public function __construct(ControllerAnalysisService $analysisService)
    {
        $this->analysisService = $analysisService;
    }
    
    /**
     * Retourne le chemin des contrôleurs (délégué au service d'analyse)
     */
    public function getControllersPath(): string
    {
        return $this->analysisService->getControllersPath();
    }
    
    /**
     * Valide tous les contrôleurs API pour s'assurer qu'ils sont conformes
     */
    public function validateAllControllers(): array
    {
        $analysisResults = $this->analysisService->analyzeAllControllers();
        
        $validationResults = [
            'isCompliant' => empty($analysisResults),
            'totalControllers' => 0,
            'compliantControllers' => 0,
            'nonCompliantControllers' => count($analysisResults),
            'issues' => []
        ];
        
        // Compter le total des contrôleurs
        $finder = new \Symfony\Component\Finder\Finder();
        $finder->files()->in($this->getControllersPath())->name('*.php');
        $validationResults['totalControllers'] = $finder->count();
        $validationResults['compliantControllers'] = $validationResults['totalControllers'] - $validationResults['nonCompliantControllers'];
        
        // Détailler les problèmes
        foreach ($analysisResults as $controllerName => $result) {
            $controllerIssues = [];
            
            foreach ($result['analysis']['nonCompliantMethods'] as $method) {
                $controllerIssues[] = [
                    'type' => 'non_compliant_pattern',
                    'pattern' => $method['pattern'],
                    'patternType' => $method['type'],
                    'line' => $method['line'],
                    'message' => "Found non-compliant pattern '{$method['type']}' at line {$method['line']}"
                ];
            }
            
            if (!empty($controllerIssues)) {
                $validationResults['issues'][$controllerName] = $controllerIssues;
            }
        }
        
        return $validationResults;
    }
    
    /**
     * Valide un contrôleur spécifique
     */
    public function validateController(string $filePath): array
    {
        $analysis = $this->analysisService->analyzeController($filePath);
        
        $isCompliant = empty($analysis['nonCompliantMethods']);
        
        return [
            'isCompliant' => $isCompliant,
            'filePath' => $filePath,
            'issues' => $analysis['nonCompliantMethods'],
            'routesWithId' => $analysis['routesWithId'],
            'repositoryUsage' => $analysis['repositoryUsage']
        ];
    }
    
    /**
     * Vérifie que toutes les routes avec {id} utilisent findInEnvironment
     */
    public function validateEnvironmentMethodUsage(string $content): array
    {
        $issues = [];
        
        // Vérifier qu'il n'y a plus de find($id)
        if (preg_match('/\$\w+Repository->find\(\$id\)/', $content)) {
            $issues[] = [
                'type' => 'forbidden_pattern',
                'pattern' => 'find($id)',
                'message' => 'Found forbidden pattern: $repository->find($id)'
            ];
        }
        
        // Vérifier qu'il n'y a plus de findOneBy(['id' => $id])
        if (preg_match('/\$\w+Repository->findOneBy\(\s*\[\s*[\'"]id[\'"]\s*=>\s*\$id\s*\]\s*\)/', $content)) {
            $issues[] = [
                'type' => 'forbidden_pattern',
                'pattern' => 'findOneBy([\'id\' => $id])',
                'message' => 'Found forbidden pattern: $repository->findOneBy([\'id\' => $id])'
            ];
        }
        
        // Vérifier la présence de findInEnvironment pour les routes avec {id}
        $routesWithId = preg_match_all('/#\[Route\([\'"]([^\'"]*){\s*id\s*}/', $content);
        $findInEnvironmentUsage = preg_match_all('/\$\w+(?:Repository)?->findInEnvironment\(\$id\)/', $content);
        
        if ($routesWithId > 0 && $findInEnvironmentUsage === 0) {
            $issues[] = [
                'type' => 'missing_environment_method',
                'message' => 'Controller has routes with {id} parameter but no findInEnvironment usage found'
            ];
        }
        
        return [
            'isValid' => empty($issues),
            'issues' => $issues,
            'routesWithIdCount' => $routesWithId,
            'findInEnvironmentCount' => $findInEnvironmentUsage
        ];
    }
    
    /**
     * Génère un rapport de conformité complet
     */
    public function generateComplianceReport(): array
    {
        $validationResults = $this->validateAllControllers();
        
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'summary' => [
                'totalControllers' => $validationResults['totalControllers'],
                'compliantControllers' => $validationResults['compliantControllers'],
                'nonCompliantControllers' => $validationResults['nonCompliantControllers'],
                'compliancePercentage' => $validationResults['totalControllers'] > 0 
                    ? round(($validationResults['compliantControllers'] / $validationResults['totalControllers']) * 100, 2)
                    : 0
            ],
            'status' => $validationResults['isCompliant'] ? 'COMPLIANT' : 'NON_COMPLIANT',
            'issues' => $validationResults['issues']
        ];
        
        return $report;
    }
    
    /**
     * Vérifie la cohérence de l'usage de findInEnvironment dans tous les contrôleurs
     */
    public function validateConsistency(): array
    {
        $finder = new \Symfony\Component\Finder\Finder();
        $finder->files()->in($this->getControllersPath())->name('*.php');
        
        $consistencyIssues = [];
        $environmentMethodUsage = [];
        
        foreach ($finder as $file) {
            $content = file_get_contents($file->getRealPath());
            $validation = $this->validateEnvironmentMethodUsage($content);
            
            $environmentMethodUsage[$file->getBasename('.php')] = [
                'routesWithId' => $validation['routesWithIdCount'],
                'findInEnvironmentUsage' => $validation['findInEnvironmentCount'],
                'isConsistent' => $validation['routesWithIdCount'] === 0 || $validation['findInEnvironmentCount'] > 0
            ];
            
            if (!$validation['isValid']) {
                $consistencyIssues[$file->getBasename('.php')] = $validation['issues'];
            }
        }
        
        return [
            'isConsistent' => empty($consistencyIssues),
            'usage' => $environmentMethodUsage,
            'issues' => $consistencyIssues
        ];
    }
}