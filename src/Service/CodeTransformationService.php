<?php

namespace App\Service;

/**
 * Service de transformation du code pour remplacer les patterns non-conformes
 * Remplace find($id) et findOneBy(['id' => $id]) par findInEnvironment($id)
 */
class CodeTransformationService
{
    /**
     * Transforme le contenu d'un fichier en remplaçant les patterns non-conformes
     */
    public function transformFileContent(string $content): array
    {
        $originalContent = $content;
        $transformations = [];
        
        // Remplacer find($id) par findInEnvironment($id)
        $findPattern = '/(\$\w+(?:Repository)?)->find\(\$id\)/';
        $content = preg_replace_callback($findPattern, function($matches) use (&$transformations) {
            $transformation = [
                'type' => 'find($id) replacement',
                'original' => $matches[0],
                'replacement' => $matches[1] . '->findInEnvironment($id)'
            ];
            $transformations[] = $transformation;
            return $transformation['replacement'];
        }, $content);
        
        // Remplacer findOneBy(['id' => $id]) par findInEnvironment($id)
        $findOneByPattern = '/(\$\w+(?:Repository)?)->findOneBy\(\s*\[\s*[\'"]id[\'"]\s*=>\s*\$id\s*\]\s*\)/';
        $content = preg_replace_callback($findOneByPattern, function($matches) use (&$transformations) {
            $transformation = [
                'type' => 'findOneBy([\'id\' => $id]) replacement',
                'original' => $matches[0],
                'replacement' => $matches[1] . '->findInEnvironment($id)'
            ];
            $transformations[] = $transformation;
            return $transformation['replacement'];
        }, $content);
        
        // Transformer l'injection automatique d'entités
        $content = $this->transformAutoInjectionMethods($content, $transformations);
        
        return [
            'transformedContent' => $content,
            'transformations' => $transformations,
            'hasChanges' => $content !== $originalContent
        ];
    }
    
    /**
     * Transforme les méthodes utilisant l'injection automatique d'entités
     */
    private function transformAutoInjectionMethods(string $content, array &$transformations): string
    {
        // Pour l'instant, nous ne transformons pas l'injection automatique car c'est plus complexe
        // Nous nous concentrons sur les patterns find($id) et findOneBy(['id' => $id])
        return $content;
    }
    
    /**
     * Transforme un fichier contrôleur spécifique
     */
    public function transformController(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [
                'success' => false,
                'error' => 'File not found: ' . $filePath
            ];
        }
        
        $originalContent = file_get_contents($filePath);
        $result = $this->transformFileContent($originalContent);
        
        if ($result['hasChanges']) {
            // Créer une sauvegarde
            $backupPath = $filePath . '.backup.' . date('Y-m-d-H-i-s');
            file_put_contents($backupPath, $originalContent);
            
            // Écrire le contenu transformé
            file_put_contents($filePath, $result['transformedContent']);
            
            return [
                'success' => true,
                'filePath' => $filePath,
                'backupPath' => $backupPath,
                'transformations' => $result['transformations'],
                'transformationCount' => count($result['transformations'])
            ];
        }
        
        return [
            'success' => true,
            'filePath' => $filePath,
            'transformations' => [],
            'transformationCount' => 0,
            'message' => 'No transformations needed'
        ];
    }
    
    /**
     * Valide que les transformations n'ont pas cassé la syntaxe PHP
     */
    public function validateSyntax(string $filePath): array
    {
        $output = [];
        $returnCode = 0;
        
        // Utiliser php -l pour vérifier la syntaxe
        exec("php -l " . escapeshellarg($filePath) . " 2>&1", $output, $returnCode);
        
        return [
            'isValid' => $returnCode === 0,
            'output' => implode("\n", $output)
        ];
    }
    
    /**
     * Préserve la structure du code en vérifiant que les patterns essentiels sont maintenus
     */
    public function validateStructurePreservation(string $originalContent, string $transformedContent): array
    {
        $issues = [];
        
        // Vérifier que les try-catch sont préservés
        $originalTryCatchCount = preg_match_all('/try\s*{/', $originalContent);
        $transformedTryCatchCount = preg_match_all('/try\s*{/', $transformedContent);
        
        if ($originalTryCatchCount !== $transformedTryCatchCount) {
            $issues[] = 'Try-catch blocks count mismatch';
        }
        
        // Vérifier que les if-else sont préservés
        $originalIfCount = preg_match_all('/if\s*\(/', $originalContent);
        $transformedIfCount = preg_match_all('/if\s*\(/', $transformedContent);
        
        if ($originalIfCount !== $transformedIfCount) {
            $issues[] = 'If statements count mismatch';
        }
        
        // Vérifier que les noms de variables sont préservés
        preg_match_all('/\$(\w+)\s*=/', $originalContent, $originalVars);
        preg_match_all('/\$(\w+)\s*=/', $transformedContent, $transformedVars);
        
        $originalVarNames = array_unique($originalVars[1]);
        $transformedVarNames = array_unique($transformedVars[1]);
        
        $missingVars = array_diff($originalVarNames, $transformedVarNames);
        if (!empty($missingVars)) {
            $issues[] = 'Missing variables: ' . implode(', ', $missingVars);
        }
        
        return [
            'isValid' => empty($issues),
            'issues' => $issues
        ];
    }
}