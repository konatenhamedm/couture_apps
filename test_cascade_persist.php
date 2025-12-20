<?php

require_once 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use App\Entity\Client;
use App\Entity\Entreprise;

$dotenv = new Dotenv();
$dotenv->load('.env');

// Bootstrap Symfony
$kernel = new \App\Kernel($_ENV['APP_ENV'], false);
$kernel->boot();
$container = $kernel->getContainer();

$entityManager = $container->get('doctrine.orm.entity_manager');

echo "=== Test de cascade persist avec entreprise gérée ===\n";

try {
    // Créer ou récupérer une entreprise existante
    $entrepriseRepo = $entityManager->getRepository(Entreprise::class);
    $entreprise = $entrepriseRepo->findOneBy([]);
    
    if (!$entreprise) {
        echo "❌ Aucune entreprise trouvée. Créez d'abord une entreprise.\n";
        exit(1);
    }
    
    echo "Entreprise trouvée: ID " . $entreprise->getId() . "\n";
    
    // Vérifier si l'entreprise est gérée
    $isManaged = $entityManager->contains($entreprise);
    echo "Entreprise gérée par EM: " . ($isManaged ? 'OUI' : 'NON') . "\n";
    
    if (!$isManaged) {
        echo "Merger l'entreprise...\n";
        $entreprise = $entityManager->merge($entreprise);
        echo "Entreprise après merge gérée: " . ($entityManager->contains($entreprise) ? 'OUI' : 'NON') . "\n";
    }
    
    // Créer un client avec l'entreprise gérée
    $client = new Client();
    $client->setNom("Test Cascade");
    $client->setPrenom("Client");
    $client->setNumero("555666777");
    $client->setEntreprise($entreprise);
    
    echo "Client isActive: " . ($client->isActive() ? 'true' : 'false') . "\n";
    
    // Tenter de persister
    $entityManager->persist($client);
    $entityManager->flush();
    
    echo "✅ Client créé avec succès avec entreprise ! ID: " . $client->getId() . "\n";
    
} catch (\Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    
    if (strpos($e->getMessage(), 'cascade persist') !== false) {
        echo "❌ Problème de cascade persist encore présent\n";
    } elseif (strpos($e->getMessage(), 'Proxies') !== false) {
        echo "❌ Problème d'entité détachée encore présent\n";
    } else {
        echo "❌ Autre erreur\n";
    }
}

echo "\nTest terminé.\n";