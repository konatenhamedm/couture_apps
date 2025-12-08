<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\ModeleBoutique;
use App\Repository\ModeleRepository;
use App\Repository\BoutiqueRepository;
use App\Repository\ModeleBoutiqueRepository;
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
}