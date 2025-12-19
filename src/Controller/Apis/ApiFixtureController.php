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
use Symfony\Component\HttpFoundation\JsonResponse;
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
    private function validateEntityBeforePersist($entity, EntityManagerInterface $entityManager = null): bool
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
        EntityManagerProvider $entityManager
    ): Response {
        try {
            $modeles = $modeleRepository->findAllInEnvironment();
            $boutiques = $boutiqueRepository->findAllInEnvironment();
            $createdCount = 0;
            $createdModelesBoutique = [];



            $tailles = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
            $prixBase = [8000, 12000, 15000, 18000, 22000, 25000, 30000];

            foreach ($modeles as $modele) {
                foreach ($boutiques as $boutique) {
                    // Vérifier si l'association existe déjà
                    $existing = $modeleBoutiqueRepository->findOneByInEnvironment([
                        'modele' => $modele,
                        'boutique' => $boutique
                    ]);

                    if (!$existing) {
                        $entityManager->beginTransaction();
                        
                        try {
                            $modeleBoutique = new ModeleBoutique();
                            $modeleBoutique->setPrix($prixBase[array_rand($prixBase)]);
                            $modeleBoutique->setQuantite(rand(10, 100));
                            $modeleBoutique->setBoutique($boutique);
                            $modeleBoutique->setModele($modele);
                            $modeleBoutique->setTaille($tailles[array_rand($tailles)]);
                            
                            // Get managed user for persistence
                            $managedUser = $this->getUser();
                            if ($managedUser) {
                                $modeleBoutique->setCreatedBy($managedUser);
                                $modeleBoutique->setUpdatedBy($managedUser);
                            }
                            
                            $modeleBoutique->setCreatedAtValue(new \DateTime());
                            $modeleBoutique->setUpdatedAt(new \DateTime());

                            // Validate entity before persistence
                        /*     if (!$this->validateEntityBeforePersist($modeleBoutique, $entityManager)) {
                                $entityManager->rollback();
                                continue;
                            } */

                           /*  $entityManager->persist($modeleBoutique); */

                            // Mise à jour de la quantité globale du modèle
                            $modele->setQuantiteGlobale($modele->getQuantiteGlobale() + $modeleBoutique->getQuantite());
                            $modeleRepository->saveInEnvironment($modele);

                            $modeleBoutiqueRepository->saveInEnvironment($modeleBoutique);

                            $createdModelesBoutique[] = $modeleBoutique;
                            $createdCount++;
                            
                        } catch (\Exception $e) {
                            $entityManager->rollback();
                            // Log the error but continue with next iteration
                            continue;
                        }
                    }
                }
            }

            return $this->responseData([
                'message' => "$createdCount modèles de boutique créés avec succès",
                'count' => $createdCount,
                'modeles_boutique' => $createdModelesBoutique
            ], 'group1', ['Content-Type' => 'application/json']);

        } catch (\Exception $e) {
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
        EntityManagerInterface $entityManager
    ): Response {
        try {
            $clients = $clientRepository->findAllInEnvironment();
            $boutiques = $boutiqueRepository->findAllInEnvironment();
            $createdCount = 0;
            $createdReservations = [];

            if (empty($clients) || empty($boutiques)) {
                return $this->createCustomErrorResponse("Aucun client ou boutique trouvé pour créer les fixtures", 400);
            }

            // Créer 10 réservations de test
            for ($i = 0; $i < 10; $i++) {
                $client = $clients[array_rand($clients)];
                $boutique = $boutiques[array_rand($boutiques)];
                
                // Récupérer des modèles disponibles pour cette boutique
                $modeleBoutiques = $modeleBoutiqueRepository->findByInEnvironment(['boutique' => $boutique]);
                if (empty($modeleBoutiques)) continue;

                $entityManager->beginTransaction();

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
                    if (!$this->validateEntityBeforePersist($reservation, $entityManager)) {
                        $entityManager->rollback();
                        continue;
                    }

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

                        // Réduire le stock
                        $modeleBoutique->setQuantite($modeleBoutique->getQuantite() - $quantite);
                        $modele = $modeleBoutique->getModele();
                        if ($modele && $modele->getQuantiteGlobale() >= $quantite) {
                            $modele->setQuantiteGlobale($modele->getQuantiteGlobale() - $quantite);
                        }
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

                        // Mettre à jour la caisse
                        $caisseBoutique = $caisseBoutiqueRepository->findOneByInEnvironment(['boutique' => $boutique]);
                        if ($caisseBoutique) {
                            $caisseBoutique->setMontant($caisseBoutique->getMontant() + $avance);
                            if ($managedUser) {
                                $caisseBoutique->setUpdatedBy($managedUser);
                            }
                            $caisseBoutique->setUpdatedAt(new \DateTime());
                        }
                    }

                    $entityManager->flush();
                    $entityManager->commit();

                    $createdReservations[] = $reservation;
                    $createdCount++;

                } catch (\Exception $e) {
                    $entityManager->rollback();
                    continue;
                }
            }

            return $this->responseData([
                'message' => "$createdCount réservations créées avec succès",
                'count' => $createdCount,
                'reservations' => $createdReservations
            ], 'group1', ['Content-Type' => 'application/json']);

        } catch (\Exception $e) {
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
        EntityManagerInterface $entityManager
    ): Response {
        try {
            $boutiques = $boutiqueRepository->findAllInEnvironment();
            $createdCount = 0;
            $createdEntrees = [];

            if (empty($boutiques)) {
                return $this->createCustomErrorResponse("Aucune boutique trouvée pour créer les fixtures", 400);
            }

            // Créer 8 entrées de stock de test
            for ($i = 0; $i < 8; $i++) {
                $boutique = $boutiques[array_rand($boutiques)];
                
                // Récupérer des modèles disponibles pour cette boutique
                $modeleBoutiques = $modeleBoutiqueRepository->findByInEnvironment(['boutique' => $boutique]);
                if (empty($modeleBoutiques)) continue;

                $entityManager->beginTransaction();

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
                    if (!$this->validateEntityBeforePersist($entreStock, $entityManager)) {
                        $entityManager->rollback();
                        continue;
                    }

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

                        // Mettre à jour les stocks
                        $modeleBoutique->setQuantite($modeleBoutique->getQuantite() + $quantite);
                        $modele = $modeleBoutique->getModele();
                        $modele->setQuantiteGlobale($modele->getQuantiteGlobale() + $quantite);
                    }

                    $entreStock->setQuantite($totalQuantite);

                    $entityManager->flush();
                    $entityManager->commit();

                    $createdEntrees[] = $entreStock;
                    $createdCount++;

                } catch (\Exception $e) {
                    $entityManager->rollback();
                    continue;
                }
            }

            return $this->responseData([
                'message' => "$createdCount entrées de stock créées avec succès",
                'count' => $createdCount,
                'entrees_stock' => $createdEntrees
            ], 'group1', ['Content-Type' => 'application/json']);

        } catch (\Exception $e) {
            return $this->createCustomErrorResponse("Erreur lors de la création des fixtures: " . $e->getMessage(), 500);
        }
    }
}