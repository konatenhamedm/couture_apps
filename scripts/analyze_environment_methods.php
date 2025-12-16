<?php

/**
 * Script d'analyse pour identifier les méthodes non-environnement dans les contrôleurs API
 * Fait partie de la tâche 1: Set up analysis and validation infrastructure
 */

$controllerDir = __DIR__ . '/../src/Controller/Apis/';
$results = [];

// Méthodes à rechercher (non-environnement)
$nonEnvironmentMethods = [
    'findAll()',
    'findBy(',
    'findOneBy(',
    '->find('
];

// Méthodes d'environnement (correctes)
$environmentMethods = [
    'findAllInEnvironment()',
    'findByInEnvironment(',
    'findOneByInEnvironment(',
    'findInEnvironment('
];

function analyzeFile($filePath) {
    global $nonEnvironmentMethods, $environmentMethods;
    
    $content = file_get_contents($filePath);
    $lines = explode("\n", $content);
    $issues = [];
    
    foreach ($lines as $lineNumber => $line) {
        foreach ($nonEnvironmentMethods as $method) {
            if (strpos($line, $method) !== false && strpos($line, '//') === false) {
                // Vérifier que ce n'est pas dans un commentaire
                $issues[] = [
                    'line' => $lineNumber + 1,
                    'method' => $method,
                    'content' => trim($line)
                ];
            }
        }
    }
    
    return $issues;
}

// Analyser tous les contrôleurs API
$controllers = glob($controllerDir . '*.php');

echo "=== ANALYSE DES MÉTHODES D'ENVIRONNEMENT ===\n\n";

foreach ($controllers as $controller) {
    $controllerName = basename($controller);
    $issues = analyzeFile($controller);
    
    if (!empty($issues)) {
        echo "❌ $controllerName - " . count($issues) . " problème(s) trouvé(s):\n";
        foreach ($issues as $issue) {
            echo "   Ligne {$issue['line']}: {$issue['method']} - {$issue['content']}\n";
        }
        echo "\n";
        $results[$controllerName] = $issues;
    } else {
        echo "✅ $controllerName - Aucun problème détecté\n";
    }
}

echo "\n=== RÉSUMÉ ===\n";
$totalIssues = array_sum(array_map('count', $results));
echo "Total des contrôleurs analysés: " . count($controllers) . "\n";
echo "Contrôleurs avec problèmes: " . count($results) . "\n";
echo "Total des problèmes: $totalIssues\n";

if ($totalIssues > 0) {
    echo "\n=== CONTRÔLEURS À MIGRER ===\n";
    foreach ($results as $controller => $issues) {
        echo "- $controller (" . count($issues) . " problèmes)\n";
    }
}

echo "\n=== MIGRATION PROGRESS ===\n";
$migratedCount = count($controllers) - count($results);
$progressPercent = round(($migratedCount / count($controllers)) * 100, 1);
echo "Progression: $migratedCount/" . count($controllers) . " contrôleurs migrés ($progressPercent%)\n";

// Sauvegarder le rapport
$reportData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'total_controllers' => count($controllers),
    'migrated_controllers' => $migratedCount,
    'progress_percent' => $progressPercent,
    'issues' => $results
];

file_put_contents(__DIR__ . '/migration_report.json', json_encode($reportData, JSON_PRETTY_PRINT));
echo "\nRapport sauvegardé dans: scripts/migration_report.json\n";