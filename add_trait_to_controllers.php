<?php

/**
 * Script pour ajouter automatiquement le DatabaseEnvironmentTrait à tous les contrôleurs API
 */

$controllersDir = 'src/Controller/Apis/';
$traitImport = 'use App\Trait\DatabaseEnvironmentTrait;';
$traitUsage = '    use DatabaseEnvironmentTrait;';

// Liste des contrôleurs à traiter
$controllers = [
    'ApiAbonnementController.php',
    'ApiAccueilController.php',
    'ApiBoutiqueController.php',
    'ApiCategorieMesureController.php',
    'ApiCategorieTypeMesureController.php',
    'ApiClientController.php',
    'ApiDatabaseTestController.php',
    'ApiEntrepriseController.php',
    'ApiFactureController.php',
    'ApiFixtureController.php',
    'ApiGestionStockController.php',
    'ApiModeleBoutiqueController.php',
    'ApiModeleController.php',
    'ApiModuleAbonnementController.php',
    'ApiNotificationController.php',
    'ApiOperateurController.php',
    'ApiPaiementController.php',
    'ApiPaysController.php',
    'ApiRapportController.php',
    'ApiReservationController.php',
    'ApiStatistiqueController.php',
    'ApiSurccursaleController.php',
    'ApiTypeMesureController.php',
    'ApiTypeUserController.php',
    'ApiUserController.php',
    'ApiVenteController.php'
];

foreach ($controllers as $controller) {
    $filePath = $controllersDir . $controller;
    
    if (!file_exists($filePath)) {
        echo "❌ Fichier non trouvé: $filePath\n";
        continue;
    }
    
    $content = file_get_contents($filePath);
    
    // Vérifier si le trait est déjà importé
    if (strpos($content, 'use App\Trait\DatabaseEnvironmentTrait;') !== false) {
        echo "✅ $controller - Trait déjà importé\n";
        continue;
    }
    
    // Ajouter l'import du trait après les autres imports
    $pattern = '/(use [^;]+;[\s\n]*)+/';
    if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
        $lastImportEnd = $matches[0][1] + strlen($matches[0][0]);
        $content = substr_replace($content, $traitImport . "\n", $lastImportEnd, 0);
    } else {
        echo "❌ $controller - Impossible de trouver les imports\n";
        continue;
    }
    
    // Ajouter l'usage du trait dans la classe
    $pattern = '/(class\s+\w+\s+extends\s+[^\{]+\{)/';
    if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
        $classStart = $matches[1][1] + strlen($matches[1][0]);
        $content = substr_replace($content, "\n" . $traitUsage . "\n", $classStart, 0);
    } else {
        echo "❌ $controller - Impossible de trouver la déclaration de classe\n";
        continue;
    }
    
    // Sauvegarder le fichier modifié
    file_put_contents($filePath, $content);
    echo "✅ $controller - Trait ajouté avec succès\n";
}

echo "\n🎉 Traitement terminé !\n";