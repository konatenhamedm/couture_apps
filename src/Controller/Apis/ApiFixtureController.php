<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\ModeleBoutique;
use App\Entity\Reservation;
use App\Entity\LigneReservation;
use App\Entity\PaiementReservation;
use App\Entity\EntreStock;
use App\Entity\LigneEntre;
use App\Entity\User;
use App\Repository\ModeleRepository;
use App\Repository\BoutiqueRepository;
use App\Repository\ModeleBoutiqueRepository;
use App\Repository\ClientRepository;
use App\Repository\CaisseBoutiqueRepository;
use App\Service\EntityManagerProvider;
use App\Service\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;


/**
 * Contrôleur pour les fixtures de données de test
 */
#[Route('/api/fixture')]
#[OA\Tag(name: 'fixture', description: 'Génération de données de test pour le développement')]
class ApiFixtureController extends ApiInterface
{


    /**
     * Validate and prepare entity before persistence
     */
    private function validateEntityBeforePersist($entity): bool
    {
        // Validate the entity using Symfony validator
        $errors = $this->validator->validate($entity);
        
        if (count($errors) > 0) {
            return false;
        }

        return true;
    }

    /**
     * Get a managed user entity for persistence
     */
    private function getManagedUser(EntityManagerInterface $entityManager): ?User
    {
        /** @var User|null $currentUser */
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return null;
        }

        // If the user is already managed by the EntityManager, return it
        if ($entityManager->contains($currentUser)) {
            return $currentUser;
        }

        // Otherwise, fetch the user from the database to get a managed instance
        $userId = $currentUser->getId();
        if ($userId) {
            return $entityManager->find(User::class, $userId);
        }

        return null;
    }
    /**
     * Génère des modèles de boutique de test
     */
    #[Route('/modele-boutique', methods: ['POST'])]
    #[OA\Post(
        path: "/api/fixture/modele-boutique",
        summary: "Générer des modèles de boutique de test",
        description: "Crée automatiquement des associations modèle-boutique avec des données de test pour le développement. Associe tous les modèles existants à toutes les boutiques avec des prix et quantités aléatoires.",
        tags: ['fixture']
    )]
    #[OA\Response(
        response: 201,
        description: "Fixtures créées avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "message", type: "string", example: "25 modèles de boutique créés avec succès"),
                new OA\Property(property: "count", type: "integer", example: 25),
                new OA\Property(property: "modeles_boutique", type: "array", items: new OA\Items(type: "object"))
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 500, description: "Erreur lors de la création")]
    public function createModeleBoutiqueFixtures(
        ModeleRepository $modeleRepository,
        BoutiqueRepository $boutiqueRepository,
        ModeleBoutiqueRepository $modeleBoutiqueRepository,
        EntityManagerProvider $entityManagerProvider
    ): Response {
        try {
            $modeles = $modeleRepository->findAllInEnvironment();
            $boutiques = $boutiqueRepository->findAllInEnvironment();
            $createdCount = 0;
            $createdModelesBoutique = [];

            if (empty($modeles) || empty($boutiques)) {
                return $this->createCustomErrorResponse("Aucun modèle ou boutique trouvé pour créer les fixtures", 400);
            }

            $tailles = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
            $prixBase = [8000, 12000, 15000, 18000, 22000, 25000, 30000];

            $entityManager = $entityManagerProvider->getEntityManager();
            $batchSize = 20; // Traiter par batch pour optimiser les performances

            foreach ($modeles as $modele) {
                foreach ($boutiques as $boutique) {
                    // Vérifier si l'association existe déjà
                    $existing = $modeleBoutiqueRepository->findOneByInEnvironment([
                        'modele' => $modele,
                        'boutique' => $boutique
                    ]);

                    if ($existing == null) {
                        try {
                            $modeleBoutique = new ModeleBoutique();
                            $modeleBoutique->setPrix($prixBase[array_rand($prixBase)]);
                            $modeleBoutique->setQuantite(rand(10, 100));
                            $modeleBoutique->setBoutique($boutique);
                            $modeleBoutique->setModele($modele);
                            $modeleBoutique->setIsActive(true);
                            $modeleBoutique->setTaille($tailles[array_rand($tailles)]);
                            
                            // Get managed user for persistence
                            $managedUser = $this->getManagedUser($entityManager);
                            if ($managedUser) {
                                $modeleBoutique->setCreatedBy($managedUser);
                                $modeleBoutique->setUpdatedBy($managedUser);
                            }
                            
                            $modeleBoutique->setCreatedAtValue(new \DateTime());
                            $modeleBoutique->setUpdatedAt(new \DateTime());

                            // Validate entity before persistence
                            if (!$this->validateEntityBeforePersist($modeleBoutique)) {
                                error_log("Validation échouée pour ModeleBoutique");
                                continue;
                            }

                            // Mise à jour de la quantité globale du modèle
                            $modele->setQuantiteGlobale((int)$modele->getQuantiteGlobale() + (int)$modeleBoutique->getQuantite());
                            
                            // Utiliser saveInEnvironment qui prend en compte l'environnement dev/prod
                            // Sauvegarder sans flush pour optimiser les performances
                            $modeleRepository->saveInEnvironment($modele, false);
                            $modeleBoutiqueRepository->saveInEnvironment($modeleBoutique, false);

                            $createdModelesBoutique[] = $modeleBoutique;
                            $createdCount++;
                            
                            // Flush par batch pour optimiser les performances
                            if ($createdCount % $batchSize === 0) {
                                $entityManager->flush();
                                error_log("Batch de $batchSize ModeleBoutique sauvegardés (total: $createdCount)");
                            }
                            
                        } catch (\Exception $e) {
                            // Log the error for debugging
                            error_log("Erreur lors de la création du ModeleBoutique: " . $e->getMessage());
                            error_log("Stack trace: " . $e->getTraceAsString());
                            continue;
                        }
                    }
                }
            }

            // Flush final pour sauvegarder les entités restantes
            if ($createdCount % $batchSize !== 0) {
                $entityManager->flush();
                error_log("Flush final - Total ModeleBoutique créés: $createdCount");
            }

            return $this->responseData([
                'message' => "$createdCount modèles de boutique créés avec succès",
                'count' => $createdCount,
                'modeles_boutique' => $createdModelesBoutique
            ], 'group1', ['Content-Type' => 'application/json']);

        } catch (\Exception $e) {
            error_log("Erreur générale dans createModeleBoutiqueFixtures: " . $e->getMessage());
            return $this->createCustomErrorResponse("Erreur lors de la création des fixtures: " . $e->getMessage(), 500);
        }
    }

    /**
     * Génère des réservations et paiements de test
     */
    #[Route('/reservations', methods: ['POST'])]
    #[OA\Post(
        path: "/api/fixture/reservations",
        summary: "Générer des réservations de test",
        description: "Crée automatiquement des réservations avec leurs paiements pour le développement. Génère des données réalistes avec différents montants d'avance.",
        tags: ['fixture']
    )]
    #[OA\Response(
        response: 201,
        description: "Fixtures créées avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "message", type: "string", example: "10 réservations créées avec succès"),
                new OA\Property(property: "count", type: "integer", example: 10),
                new OA\Property(property: "reservations", type: "array", items: new OA\Items(type: "object"))
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 500, description: "Erreur lors de la création")]
    public function createReservationFixtures(
        ClientRepository $clientRepository,
        BoutiqueRepository $boutiqueRepository,
        ModeleBoutiqueRepository $modeleBoutiqueRepository,
        CaisseBoutiqueRepository $caisseBoutiqueRepository,
        Utils $utils,
        EntityManagerProvider $entityManagerProvider
    ): Response {
        try {
            $clients = $clientRepository->findAllInEnvironment();
            $boutiques = $boutiqueRepository->findAllInEnvironment();
            $createdCount = 0;
            $createdReservations = [];

            if (empty($clients) || empty($boutiques)) {
                return $this->createCustomErrorResponse("Aucun client ou boutique trouvé pour créer les fixtures", 400);
            }

            $entityManager = $entityManagerProvider->getEntityManager();

            // Créer 10 réservations de test
            for ($i = 0; $i < 10; $i++) {
                $client = $clients[array_rand($clients)];
                $boutique = $boutiques[array_rand($boutiques)];
                
                // Récupérer des modèles disponibles pour cette boutique
                $modeleBoutiques = $modeleBoutiqueRepository->findByInEnvironment(['boutique' => $boutique]);
                if (empty($modeleBoutiques)) continue;

                try {
                    // Montants aléatoires
                    $montant = rand(15000, 50000);
                    $avance = rand(5000, $montant);
                    $reste = $montant - $avance;

                    // Date de retrait future
                    $dateRetrait = new \DateTime();
                    $dateRetrait->add(new \DateInterval('P' . rand(7, 30) . 'D'));

                    // Créer la réservation
                    $reservation = new Reservation();
                    $reservation->setAvance($avance);
                    $reservation->setDateRetrait($dateRetrait);
                    $reservation->setClient($client);
                    $reservation->setBoutique($boutique);
                    /** @var User $user */
                    $user = $this->getUser();
                    if ($user && $user->getEntreprise()) {
                        $reservation->setEntreprise($user->getEntreprise());
                    }
                    $reservation->setMontant($montant);
                    $reservation->setReste($reste);
                    $reservation->setCreatedAtValue(new \DateTime());
                    $reservation->setUpdatedAt(new \DateTime());
                    
                    // Get managed user for persistence
                    $managedUser = $this->getManagedUser($entityManager);
                    if ($managedUser) {
                        $reservation->setCreatedBy($managedUser);
                        $reservation->setUpdatedBy($managedUser);
                    }

                    // Validate entity before persistence
                    if (!$this->validateEntityBeforePersist($reservation)) {
                        error_log("Validation échouée pour Reservation");
                        continue;
                    }

                    // Utiliser saveInEnvironment pour la réservation (sans flush)
                    // Note: Nous devons utiliser un repository pour Reservation
                    $entityManager->persist($reservation);

                    // Ajouter 1-3 lignes de réservation
                    $nbLignes = rand(1, 3);
                    for ($j = 0; $j < $nbLignes; $j++) {
                        $modeleBoutique = $modeleBoutiques[array_rand($modeleBoutiques)];
                        if ($modeleBoutique->getQuantite() <= 0) continue;

                        $quantite = rand(1, min(3, $modeleBoutique->getQuantite()));
                        $avanceModele = rand(2000, 8000);

                        $ligne = new LigneReservation();
                        $ligne->setQuantite($quantite);
                        $ligne->setModele($modeleBoutique);
                        $ligne->setAvanceModele($avanceModele);
                        $ligne->setCreatedAtValue(new \DateTime());
                        $ligne->setUpdatedAt(new \DateTime());
                        
                        // Use the same managed user for ligne entities
                        if ($managedUser) {
                            $ligne->setCreatedBy($managedUser);
                            $ligne->setUpdatedBy($managedUser);
                        }

                        $reservation->addLigneReservation($ligne);
                        $entityManager->persist($ligne);

                        // Réduire le stock - utiliser saveInEnvironment
                        $modeleBoutique->setQuantite($modeleBoutique->getQuantite() - $quantite);
                        $modele = $modeleBoutique->getModele();
                        if ($modele && $modele->getQuantiteGlobale() >= $quantite) {
                            $modele->setQuantiteGlobale($modele->getQuantiteGlobale() - $quantite);
                        }
                        
                        // Sauvegarder les modifications de stock (sans flush)
                        $modeleBoutiqueRepository->saveInEnvironment($modeleBoutique, false);
                    }

                    // Créer le paiement si avance > 0
                    if ($avance > 0) {
                        $paiementReservation = new PaiementReservation();
                        $paiementReservation->setReservation($reservation);
                        $paiementReservation->setType('paiementReservation');
                        $paiementReservation->setMontant($avance);
                        $paiementReservation->setReference($utils->generateReference('PMT'));
                        $paiementReservation->setCreatedAtValue(new \DateTime());
                        $paiementReservation->setUpdatedAt(new \DateTime());
                        
                        // Use the same managed user for paiement entities
                        if ($managedUser) {
                            $paiementReservation->setCreatedBy($managedUser);
                            $paiementReservation->setUpdatedBy($managedUser);
                        }

                        $entityManager->persist($paiementReservation);

                        // Mettre à jour la caisse - utiliser saveInEnvironment
                        $caisseBoutique = $caisseBoutiqueRepository->findOneByInEnvironment(['boutique' => $boutique]);
                        if ($caisseBoutique) {
                            $caisseBoutique->setMontant($caisseBoutique->getMontant() + $avance);
                            if ($managedUser) {
                                $caisseBoutique->setUpdatedBy($managedUser);
                            }
                            $caisseBoutique->setUpdatedAt(new \DateTime());
                            
                            // Sauvegarder la caisse (sans flush)
                            $caisseBoutiqueRepository->saveInEnvironment($caisseBoutique, false);
                        }
                    }

                    // Flush final pour cette réservation
                    $entityManager->flush();

                    $createdReservations[] = $reservation;
                    $createdCount++;
                    
                    error_log("Réservation $createdCount créée avec succès");

                } catch (\Exception $e) {
                    error_log("Erreur lors de la création de la réservation: " . $e->getMessage());
                    error_log("Stack trace: " . $e->getTraceAsString());
                    continue;
                }
            }

            return $this->responseData([
                'message' => "$createdCount réservations créées avec succès",
                'count' => $createdCount,
                'reservations' => $createdReservations
            ], 'group1', ['Content-Type' => 'application/json']);

        } catch (\Exception $e) {
            error_log("Erreur générale dans createReservationFixtures: " . $e->getMessage());
            return $this->createCustomErrorResponse("Erreur lors de la création des fixtures: " . $e->getMessage(), 500);
        }
    }

    /**
     * Génère des entrées de stock de test
     */
    #[Route('/entrees-stock', methods: ['POST'])]
    #[OA\Post(
        path: "/api/fixture/entrees-stock",
        summary: "Générer des entrées de stock de test",
        description: "Crée automatiquement des entrées de stock avec leurs lignes pour le développement. Génère des mouvements d'entrée réalistes avec quantités aléatoires.",
        tags: ['fixture']
    )]
    #[OA\Response(
        response: 201,
        description: "Fixtures créées avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "message", type: "string", example: "8 entrées de stock créées avec succès"),
                new OA\Property(property: "count", type: "integer", example: 8),
                new OA\Property(property: "entrees_stock", type: "array", items: new OA\Items(type: "object"))
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 500, description: "Erreur lors de la création")]
    public function createEntreeStockFixtures(
        BoutiqueRepository $boutiqueRepository,
        ModeleBoutiqueRepository $modeleBoutiqueRepository,
        EntityManagerProvider $entityManagerProvider
    ): Response {
        try {
            $boutiques = $boutiqueRepository->findAllInEnvironment();
            $createdCount = 0;
            $createdEntrees = [];

            if (empty($boutiques)) {
                return $this->createCustomErrorResponse("Aucune boutique trouvée pour créer les fixtures", 400);
            }

            $entityManager = $entityManagerProvider->getEntityManager();

            // Créer 8 entrées de stock de test
            for ($i = 0; $i < 8; $i++) {
                $boutique = $boutiques[array_rand($boutiques)];
                
                // Récupérer des modèles disponibles pour cette boutique
                $modeleBoutiques = $modeleBoutiqueRepository->findByInEnvironment(['boutique' => $boutique]);
                if (empty($modeleBoutiques)) continue;

                try {
                    // Créer l'entrée de stock
                    $entreStock = new EntreStock();
                    $entreStock->setBoutique($boutique);
                    $entreStock->setType('Entree');
                    /** @var User $user */
                    $user = $this->getUser();
                    if ($user && $user->getEntreprise()) {
                        $entreStock->setEntreprise($user->getEntreprise());
                    }
                    
                    // Get managed user for persistence
                    $managedUser = $this->getManagedUser($entityManager);
                    if ($managedUser) {
                        $entreStock->setCreatedBy($managedUser);
                        $entreStock->setUpdatedBy($managedUser);
                    }
                    $entreStock->setCreatedAtValue(new \DateTime());
                    $entreStock->setUpdatedAt(new \DateTime());

                    // Validate entity before persistence
                    if (!$this->validateEntityBeforePersist($entreStock)) {
                        error_log("Validation échouée pour EntreStock");
                        continue;
                    }

                    // Persister l'entrée de stock
                    $entityManager->persist($entreStock);

                    // Ajouter 2-5 lignes d'entrée
                    $nbLignes = rand(2, 5);
                    $totalQuantite = 0;
                    
                    for ($j = 0; $j < $nbLignes; $j++) {
                        $modeleBoutique = $modeleBoutiques[array_rand($modeleBoutiques)];
                        $quantite = rand(20, 100);
                        $totalQuantite += $quantite;

                        $ligneEntre = new LigneEntre();
                        $ligneEntre->setQuantite($quantite);
                        $ligneEntre->setModele($modeleBoutique);
                        $ligneEntre->setEntreStock($entreStock);

                        $entityManager->persist($ligneEntre);
                        $entreStock->addLigneEntre($ligneEntre);

                        // Mettre à jour les stocks - utiliser saveInEnvironment
                        $modeleBoutique->setQuantite($modeleBoutique->getQuantite() + $quantite);
                        $modele = $modeleBoutique->getModele();
                        $modele->setQuantiteGlobale($modele->getQuantiteGlobale() + $quantite);
                        
                        // Sauvegarder les modifications de stock (sans flush)
                        $modeleBoutiqueRepository->saveInEnvironment($modeleBoutique, false);
                    }

                    $entreStock->setQuantite($totalQuantite);

                    // Flush final pour cette entrée de stock
                    $entityManager->flush();

                    $createdEntrees[] = $entreStock;
                    $createdCount++;
                    
                    error_log("EntreStock $createdCount créée avec succès (quantité: $totalQuantite)");

                } catch (\Exception $e) {
                    error_log("Erreur lors de la création de l'EntreStock: " . $e->getMessage());
                    error_log("Stack trace: " . $e->getTraceAsString());
                    continue;
                }
            }

            return $this->responseData([
                'message' => "$createdCount entrées de stock créées avec succès",
                'count' => $createdCount,
                'entrees_stock' => $createdEntrees
            ], 'group1', ['Content-Type' => 'application/json']);

        } catch (\Exception $e) {
            error_log("Erreur générale dans createEntreeStockFixtures: " . $e->getMessage());
            return $this->createCustomErrorResponse("Erreur lors de la création des fixtures: " . $e->getMessage(), 500);
        }
    }
}