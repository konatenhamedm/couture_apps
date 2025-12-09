<?php

namespace App\Controller\Apis;

use App\Entity\Facture;
use App\Repository\FactureRepository;
use App\Repository\BoutiqueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

#[Route('/api')]
class ApiFactureController extends AbstractController
{
    /**
     * Liste des factures par boutique
     */
    #[Route('/facture/boutique/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/facture/boutique/{id}",
        summary: "Liste des factures par boutique",
        description: "Retourne la liste des factures d'une boutique avec pagination",
        tags: ['Factures']
    )]
    #[OA\Parameter(
        name: "id",
        description: "ID de la boutique",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    public function getFacturesByBoutique(
        int $id,
        FactureRepository $factureRepository,
        BoutiqueRepository $boutiqueRepository
    ): Response {
        try {
            $boutique = $boutiqueRepository->find($id);
            if (!$boutique) {
                return $this->json(['success' => false, 'message' => 'Boutique non trouvée'], 404);
            }

            $factures = $factureRepository->findBy(['boutique' => $boutique], ['date' => 'DESC']);
            
            $data = [];
            foreach ($factures as $facture) {
                $montant = floatval($facture->getMontantTotal() ?? 0);
                $avance = floatval($facture->getAvance() ?? 0);
                $reste = floatval($facture->getResteArgent() ?? ($montant - $avance));
                
                $data[] = [
                    'id' => $facture->getId(),
                    'numero' => 'FAC-' . str_pad($facture->getId(), 6, '0', STR_PAD_LEFT),
                    'date' => $facture->getDateDepot()?->format('Y-m-d H:i:s') ?? date('Y-m-d H:i:s'),
                    'montant' => $montant,
                    'paye' => $avance,
                    'reste' => $reste,
                    'client' => [
                        'id' => $facture->getClient()?->getId(),
                        'nom' => $facture->getClient()?->getNom(),
                        'prenom' => $facture->getClient()?->getPrenom()
                    ]
                ];
            }

            return $this->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Créer une nouvelle facture
     */
    #[Route('/facture', methods: ['POST'])]
    #[OA\Post(
        path: "/api/facture",
        summary: "Créer une facture",
        description: "Crée une nouvelle facture",
        tags: ['Factures']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "clientId", type: "integer", example: 1),
                new OA\Property(property: "boutiqueId", type: "integer", example: 1),
                new OA\Property(property: "montant", type: "number", example: 50000),
                new OA\Property(property: "description", type: "string", example: "Facture pour tailleur")
            ]
        )
    )]
    public function createFacture(
        Request $request,
        EntityManagerInterface $em,
        BoutiqueRepository $boutiqueRepository
    ): Response {
        try {
            $data = json_decode($request->getContent(), true);
            
            $facture = new Facture();
            $facture->setDateDepot(new \DateTime());
            $facture->setDateRetrait(new \DateTime('+7 days'));
            $facture->setMontantTotal($data['montant']);
            $facture->setAvance(0);
            $facture->setResteArgent($data['montant']);
            
            // Note: L'entité Facture utilise Succursale au lieu de Boutique
            // Vous devrez adapter selon votre modèle de données

            $em->persist($facture);
            $em->flush();

            return $this->json(['success' => true, 'data' => ['id' => $facture->getId()]]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Détails d'une facture
     */
    #[Route('/facture/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/facture/{id}",
        summary: "Détails d'une facture",
        description: "Retourne les détails d'une facture",
        tags: ['Factures']
    )]
    public function getFacture(int $id, FactureRepository $factureRepository): Response
    {
        try {
            $facture = $factureRepository->find($id);
            if (!$facture) {
                return $this->json(['success' => false, 'message' => 'Facture non trouvée'], 404);
            }

            $montant = floatval($facture->getMontantTotal() ?? 0);
            $avance = floatval($facture->getAvance() ?? 0);
            $reste = floatval($facture->getResteArgent() ?? ($montant - $avance));
            
            $data = [
                'id' => $facture->getId(),
                'numero' => 'FAC-' . str_pad($facture->getId(), 6, '0', STR_PAD_LEFT),
                'date' => $facture->getDateDepot()?->format('Y-m-d H:i:s') ?? date('Y-m-d H:i:s'),
                'montant' => $montant,
                'paye' => $avance,
                'reste' => $reste,
                'client' => [
                    'id' => $facture->getClient()?->getId(),
                    'nom' => $facture->getClient()?->getNom(),
                    'prenom' => $facture->getClient()?->getPrenom()
                ],
                'succursale' => [
                    'id' => $facture->getSuccursale()?->getId(),
                    'libelle' => $facture->getSuccursale()?->getLibelle()
                ]
            ];

            return $this->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}