<?php

namespace App\Service;

use Symfony\Component\Finder\Finder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Service d'analyse des contrôleurs API pour détecter les patterns non-conformes
 * Identifie les méthodes utilisant find($id) ou findOneBy(['id' => $id]) au lieu de findInEnvironment($id)
 */
class ControllerAnalysisService
{
    private string $controllersPath;
    
    public function __construct(ParameterBagInterface $parameterBag)
    {
        $projectDir = $parameterBag->get('kernel.project_dir');
        $this->controllersPath = $projectDir . '/src/Controller/Apis';
    }
    
    /**
     * Retourne le chemin des contrôleurs
     */
    public function getControllersPath(): string
    {
        return $this->controllersPath;
    }
    
    /**
     * Analyse tous les contrôleurs API et retourne un rapport des patterns détectés
     */
    public function analyzeAllControllers(): array
    {
        $finder = new Finder();
        $finder->files()->in($this->controllersPath)->name('*.php');
        
        $results = [];
        
        foreach ($finder as $file) {
            $controllerPath = $file->getRealPath();
            $controllerName = $file->getBasename('.php');
            
            $analysis = $this->analyzeController($controllerPath);
            
            if (!empty($analysis['nonCompliantMethods'])) {
                $results[$controllerName] = [
                    'path' => $controllerPath,
                    'analysis' => $analysis
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Analyse un contrôleur spécifique pour détecter les patterns non-conformes
     */
    public function analyzeController(string $filePath): array
    {
        $content = file_get_contents($filePath);
        
        return [
            'nonCompliantMethods' => $this->detectNonCompliantMethods($content),
            'routesWithId' => $this->detectRoutesWithIdParameter($content),
            'repositoryUsage' => $this->detectRepositoryUsage($content)
        ];
    }
    
    /**
     * Détecte les méthodes utilisant des patterns non-conformes
     */
    private function detectNonCompliantMethods(string $content): array
    {
        $nonCompliantMethods = [];
        
        // Pattern pour find($id)
        $findPattern = '/\$\w+Repository->find\(\$id\)/';
        if (preg_match_all($findPattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $nonCompliantMethods[] = [
                    'pattern' => $match[0],
                    'type' => 'find($id)',
                    'position' => $match[1],
                    'line' => substr_count(substr($content, 0, $match[1]), "\n") + 1
                ];
            }
        }
        
        // Pattern pour findOneBy(['id' => $id])
        $findOneByPattern = '/\$\w+Repository->findOneBy\(\s*\[\s*[\'"]id[\'"]\s*=>\s*\$id\s*\]\s*\)/';
        if (preg_match_all($findOneByPattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $nonCompliantMethods[] = [
                    'pattern' => $match[0],
                    'type' => 'findOneBy([\'id\' => $id])',
                    'position' => $match[1],
                    'line' => substr_count(substr($content, 0, $match[1]), "\n") + 1
                ];
            }
        }
        
        // Pattern pour injection automatique d'entités avec routes {id}
        $routesWithId = $this->detectRoutesWithIdParameter($content);
        $lines = explode("\n", $content);
        
        foreach ($routesWithId as $route) {
            // Chercher la méthode correspondante après cette route
            $routeLine = $route['line'] - 1; // Convertir en index 0-based
            
            // Chercher la méthode dans les 50 lignes suivantes (pour couvrir les annotations OpenAPI)
            for ($i = $routeLine; $i < min(count($lines), $routeLine + 50); $i++) {
                if (preg_match('/^\s*public function (\w+)\([^)]*\?(\w+) \$(\w+)[^)]*\): Response/', $lines[$i], $methodMatches)) {
                    $nonCompliantMethods[] = [
                        'pattern' => trim($lines[$i]),
                        'type' => 'auto_injection_entity',
                        'entityType' => $methodMatches[2],
                        'methodName' => $methodMatches[1],
                        'entityVariable' => $methodMatches[3],
                        'route' => $route['route'],
                        'position' => 0,
                        'line' => $i + 1
                    ];
                    break;
                }
            }
        }
        
        return $nonCompliantMethods;
    }
    
    /**
     * Détecte les routes avec paramètre {id}
     */
    private function detectRoutesWithIdParameter(string $content): array
    {
        $routes = [];
        
        // Pattern pour les routes avec {id}
        $routePattern = '/#\[Route\([\'"]([^\'"]*){\s*id\s*}([^\'"]*)[\'"]/';
        if (preg_match_all($routePattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $index => $match) {
                $routes[] = [
                    'route' => $matches[1][$index][0] . '{id}' . $matches[2][$index][0],
                    'line' => substr_count(substr($content, 0, $match[1]), "\n") + 1
                ];
            }
        }
        
        return $routes;
    }
    
    /**
     * Détecte l'usage des repositories dans le contrôleur
     */
    private function detectRepositoryUsage(string $content): array
    {
        $repositoryUsage = [];
        
        // Détecte les injections de repository
        $repositoryPattern = '/(\w+Repository)\s+\$(\w+)/';
        if (preg_match_all($repositoryPattern, $content, $matches)) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                $repositoryUsage[] = [
                    'repositoryClass' => $matches[1][$i],
                    'variableName' => $matches[2][$i]
                ];
            }
        }
        
        return $repositoryUsage;
    }
    
    /**
     * Génère un rapport détaillé de l'analyse
     */
    public function generateReport(array $analysisResults): array
    {
        $totalControllers = count($analysisResults);
        $totalNonCompliantMethods = 0;
        $patternCounts = [
            'find($id)' => 0,
            'findOneBy([\'id\' => $id])' => 0
        ];
        
        foreach ($analysisResults as $controllerName => $result) {
            $nonCompliantMethods = $result['analysis']['nonCompliantMethods'];
            $totalNonCompliantMethods += count($nonCompliantMethods);
            
            foreach ($nonCompliantMethods as $method) {
                if (isset($patternCounts[$method['type']])) {
                    $patternCounts[$method['type']]++;
                }
            }
        }
        
        return [
            'summary' => [
                'totalControllersAnalyzed' => $totalControllers,
                'totalNonCompliantMethods' => $totalNonCompliantMethods,
                'patternCounts' => $patternCounts
            ],
            'details' => $analysisResults
        ];
    }
}