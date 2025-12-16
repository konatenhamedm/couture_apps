<?php

namespace App\Tests\Service;

use App\Service\CodeTransformationService;
use PHPUnit\Framework\TestCase;

/**
 * Tests de propriétés pour CodeTransformationService
 * **Feature: controller-environment-standardization, Property 3: Code Transformation Correctness**
 * **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5**
 */
class CodeTransformationServiceTest extends TestCase
{
    private CodeTransformationService $transformationService;
    
    protected function setUp(): void
    {
        $this->transformationService = new CodeTransformationService();
    }
    
    /**
     * Property Test: Code Transformation Correctness
     * For any code transformation, replacing Repository_Standard_Method with Environment_Method 
     * should preserve all existing error handling, null checks, variable names, and flow structure
     */
    public function testCodeTransformationCorrectness(): void
    {
        // Générer 100 cas de test différents
        for ($i = 0; $i < 100; $i++) {
            $testCase = $this->generateTestCode();
            
            $result = $this->transformationService->transformFileContent($testCase['originalContent']);
            
            // Vérifier que les transformations ont été appliquées si nécessaire
            if ($testCase['shouldTransform']) {
                $this->assertTrue(
                    $result['hasChanges'],
                    "Expected transformations but none were applied in iteration $i"
                );
                
                $this->assertGreaterThan(
                    0,
                    count($result['transformations']),
                    "Expected transformation records in iteration $i"
                );
            } else {
                $this->assertFalse(
                    $result['hasChanges'],
                    "Unexpected transformations applied in iteration $i"
                );
            }
            
            // Vérifier la préservation de la structure
            $structureValidation = $this->transformationService->validateStructurePreservation(
                $testCase['originalContent'],
                $result['transformedContent']
            );
            
            $this->assertTrue(
                $structureValidation['isValid'],
                "Structure not preserved in iteration $i: " . implode(', ', $structureValidation['issues'])
            );
            
            // Vérifier la syntaxe PHP si un fichier temporaire est créé
            if ($result['hasChanges']) {
                $tempFile = sys_get_temp_dir() . '/test_transform_' . uniqid() . '.php';
                file_put_contents($tempFile, $result['transformedContent']);
                
                $syntaxValidation = $this->transformationService->validateSyntax($tempFile);
                $this->assertTrue(
                    $syntaxValidation['isValid'],
                    "Invalid PHP syntax after transformation in iteration $i: " . $syntaxValidation['output']
                );
                
                unlink($tempFile);
            }
        }
    }
    
    /**
     * Génère du code de test avec différents patterns
     */
    private function generateTestCode(): array
    {
        $patterns = [
            'find' => '$repository->find($id)',
            'findOneBy' => '$repository->findOneBy([\'id\' => $id])',
            'compliant' => '$repository->findInEnvironment($id)'
        ];
        
        $errorHandlingPatterns = [
            'try_catch' => 'try { %s; } catch (\Exception $exception) { return $this->response([]); }',
            'if_null' => '%s; if ($entity) { return $this->response($entity); } else { return $this->response(null); }',
            'simple' => '%s;'
        ];
        
        // Choisir aléatoirement un pattern et un style de gestion d'erreur
        $patternKeys = array_keys($patterns);
        $selectedPatternKey = $patternKeys[array_rand($patternKeys)];
        $selectedPattern = $patterns[$selectedPatternKey];
        
        $errorKeys = array_keys($errorHandlingPatterns);
        $selectedErrorKey = $errorKeys[array_rand($errorKeys)];
        $selectedErrorPattern = $errorHandlingPatterns[$selectedErrorKey];
        
        // Générer le code
        $codeBlock = sprintf($selectedErrorPattern, '$entity = ' . $selectedPattern);
        
        $content = "<?php\n\nnamespace App\\Controller\\Apis;\n\nclass TestController\n{\n    public function test()\n    {\n        " . $codeBlock . "\n    }\n}\n";
        
        return [
            'originalContent' => $content,
            'shouldTransform' => in_array($selectedPatternKey, ['find', 'findOneBy']),
            'patternType' => $selectedPatternKey,
            'errorHandlingType' => $selectedErrorKey
        ];
    }
    
    /**
     * Test spécifique pour la transformation find($id)
     */
    public function testFindIdTransformation(): void
    {
        $originalContent = '<?php
class TestController
{
    public function test()
    {
        $entity = $repository->find($id);
        if ($entity) {
            return $this->response($entity);
        }
        return $this->response(null);
    }
}';
        
        $result = $this->transformationService->transformFileContent($originalContent);
        
        $this->assertTrue($result['hasChanges']);
        $this->assertCount(1, $result['transformations']);
        $this->assertEquals('find($id) replacement', $result['transformations'][0]['type']);
        $this->assertStringContainsString('findInEnvironment($id)', $result['transformedContent']);
        $this->assertStringNotContainsString('->find($id)', $result['transformedContent']);
    }
    
    /**
     * Test spécifique pour la transformation findOneBy(['id' => $id])
     */
    public function testFindOneByIdTransformation(): void
    {
        $originalContent = '<?php
class TestController
{
    public function test()
    {
        $entity = $repository->findOneBy([\'id\' => $id]);
        if ($entity) {
            return $this->response($entity);
        }
        return $this->response(null);
    }
}';
        
        $result = $this->transformationService->transformFileContent($originalContent);
        
        $this->assertTrue($result['hasChanges']);
        $this->assertCount(1, $result['transformations']);
        $this->assertEquals('findOneBy([\'id\' => $id]) replacement', $result['transformations'][0]['type']);
        $this->assertStringContainsString('findInEnvironment($id)', $result['transformedContent']);
        $this->assertStringNotContainsString('findOneBy', $result['transformedContent']);
    }
    
    /**
     * Test pour vérifier qu'aucune transformation n'est appliquée sur du code déjà conforme
     */
    public function testNoTransformationOnCompliantCode(): void
    {
        $compliantContent = '<?php
class TestController
{
    public function test()
    {
        $entity = $repository->findInEnvironment($id);
        if ($entity) {
            return $this->response($entity);
        }
        return $this->response(null);
    }
}';
        
        $result = $this->transformationService->transformFileContent($compliantContent);
        
        $this->assertFalse($result['hasChanges']);
        $this->assertCount(0, $result['transformations']);
        $this->assertEquals($compliantContent, $result['transformedContent']);
    }
    
    /**
     * Test pour vérifier la préservation des variables et de la structure
     */
    public function testStructurePreservation(): void
    {
        $originalContent = '<?php
class TestController
{
    public function test()
    {
        try {
            $entity = $repository->find($id);
            $result = $this->processEntity($entity);
            
            if ($entity != null) {
                $this->setMessage("Success");
                $this->setStatusCode(200);
                $response = $this->response($entity);
            } else {
                $this->setMessage("Not found");
                $this->setStatusCode(404);
                $response = $this->response(null);
            }
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage($exception->getMessage());
            $response = $this->response([]);
        }
        
        return $response;
    }
}';
        
        $result = $this->transformationService->transformFileContent($originalContent);
        
        $this->assertTrue($result['hasChanges']);
        
        // Vérifier que la structure est préservée
        $structureValidation = $this->transformationService->validateStructurePreservation(
            $originalContent,
            $result['transformedContent']
        );
        
        $this->assertTrue($structureValidation['isValid'], implode(', ', $structureValidation['issues']));
        
        // Vérifier que les variables importantes sont préservées
        $this->assertStringContainsString('$entity', $result['transformedContent']);
        $this->assertStringContainsString('$result', $result['transformedContent']);
        $this->assertStringContainsString('$response', $result['transformedContent']);
        $this->assertStringContainsString('$exception', $result['transformedContent']);
        
        // Vérifier que la logique de gestion d'erreur est préservée
        $this->assertStringContainsString('try {', $result['transformedContent']);
        $this->assertStringContainsString('} catch (\Exception $exception) {', $result['transformedContent']);
        $this->assertStringContainsString('if ($entity != null) {', $result['transformedContent']);
        $this->assertStringContainsString('} else {', $result['transformedContent']);
    }
}