<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\ModeleBoutique;
use App\Entity\Reservation;
use App\Entity\LigneReservation;
use App\Entity\PaiementReservation;
use App\Repository\ModeleRepository;
use App\Repository\BoutiqueRepository;
use App\Repository\ModeleBoutiqueRepository;
use App\Repository\ClientRepository;
use App\Repository\CaisseBoutiqueRepository;
use App\Service\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;

/**
 * Contrôleur pour les fixtures de données de test
 */
#[Route('/api/fixture')]
#[OA\Tag(name: 'fixture', description: 'Génération de données de test pour le développement')]
class ApiFixtureController extends ApiInterface
{
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
        ModeleBoutiqueRepository $modeleBoutiqueRepository
    ): Response {
        try {
            $modeles = $modeleRepository->findAll();
            $boutiques = $boutiqueRepository->findAll();
            $createdCount = 0;
            $createdModelesBoutique = [];

            $tailles = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
            $prixBase = [8000, 12000, 15000, 18000, 22000, 25000, 30000];

            foreach ($modeles as $modele) {
                foreach ($boutiques as $boutique) {
                    // Vérifier si l'association existe déjà
                    $existing = $modeleBoutiqueRepository->findOneBy([
                        'modele' => $modele,
                        'boutique' => $boutique
                    ]);

                    if (!$existing) {
                        $modeleBoutique = new ModeleBoutique();
                        $modeleBoutique->setPrix($prixBase[array_rand($prixBase)]);
                        $modeleBoutique->setQuantite(rand(10, 100));
                        $modeleBoutique->setBoutique($boutique);
                        $modeleBoutique->setModele($modele);
                        $modeleBoutique->setTaille($tailles[array_rand($tailles)]);
                        $modeleBoutique->setCreatedBy($this->getUser());
                        $modeleBoutique->setUpdatedBy($this->getUser());
                        $modeleBoutique->setCreatedAtValue(new \DateTime());
                        $modeleBoutique->setUpdatedAt(new \DateTime());

                        $modeleBoutiqueRepository->add($modeleBoutique, true);

                        // Mise à jour de la quantité globale du modèle
                        $modele->setQuantiteGlobale($modele->getQuantiteGlobale() + $modeleBoutique->getQuantite());
                        $modeleRepository->add($modele, true);

                        $createdModelesBoutique[] = $modeleBoutique;
                        $createdCount++;
                    }
                }
            }

            return $this->responseData([
                'message' => "$createdCount modèles de boutique créés avec succès",
                'count' => $createdCount,
                'modeles_boutique' => $createdModelesBoutique
            ], 'group1', ['Content-Type' => 'application/json']);

        } catch (\Exception $e) {
            return $this->errorResponse(null, "Erreur lors de la création des fixtures: " . $e->getMessage(), 500);
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
            $clients = $clientRepository->findAll();
            $boutiques = $boutiqueRepository->findAll();
            $createdCount = 0;
            $createdReservations = [];

            if (empty($clients) || empty($boutiques)) {
                return $this->errorResponse(null, "Aucun client ou boutique trouvé pour créer les fixtures", 400);
            }

            // Créer 10 réservations de test
            for ($i = 0; $i < 10; $i++) {
                $client = $clients[array_rand($clients)];
                $boutique = $boutiques[array_rand($boutiques)];
                
                // Récupérer des modèles disponibles pour cette boutique
                $modeleBoutiques = $modeleBoutiqueRepository->findBy(['boutique' => $boutique]);
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
                    $reservation->setEntreprise($this->getUser()->getEntreprise());
                    $reservation->setMontant($montant);
                    $reservation->setReste($reste);
                    $reservation->setCreatedAtValue(new \DateTime());
                    $reservation->setUpdatedAt(new \DateTime());
                    $reservation->setCreatedBy($this->getUser());
                    $reservation->setUpdatedBy($this->getUser());

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
                        $ligne->setCreatedBy($this->getUser());
                        $ligne->setUpdatedBy($this->getUser());

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
                        $paiementReservation->setCreatedBy($this->getUser());
                        $paiementReservation->setUpdatedBy($this->getUser());

                        $entityManager->persist($paiementReservation);

                        // Mettre à jour la caisse
                        $caisseBoutique = $caisseBoutiqueRepository->findOneBy(['boutique' => $boutique]);
                        if ($caisseBoutique) {
                            $caisseBoutique->setMontant($caisseBoutique->getMontant() + $avance);
                            $caisseBoutique->setUpdatedBy($this->getUser());
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
            return $this->errorResponse(null, "Erreur lors de la création des fixtures: " . $e->getMessage(), 500);
        }
    }
}