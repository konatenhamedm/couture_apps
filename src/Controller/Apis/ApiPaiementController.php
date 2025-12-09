<?php

namespace App\Controller\Apis;

use App\Entity\PaiementFacture;
use App\Entity\PaiementReservation;
use App\Repository\PaiementFactureRepository;
use App\Repository\PaiementReservationRepository;
use App\Repository\BoutiqueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

#[Route('/api')]
class ApiPaiementController extends AbstractController
{
    /**
     * Paiements de factures par boutique
     */
    #[Route('/paiement/facture/boutique/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/paiement/facture/boutique/{id}",
        summary: "Paiements de factures par boutique",
        description: "Retourne l'historique des paiements de factures d'une boutique",
        tags: ['Paiements']
    )]
    public function getPaiementsFacturesByBoutique(
        int $id,
        PaiementFactureRepository $paiementRepository,
        BoutiqueRepository $boutiqueRepository
    ): Response {
        try {
            $boutique = $boutiqueRepository->find($id);
            if (!$boutique) {
                return $this->json(['success' => false, 'message' => 'Boutique non trouvée'], 404);
            }

            $paiements = $paiementRepository->createQueryBuilder('p')
                ->join('p.facture', 'f')
                ->where('f.boutique = :boutique')
                ->setParameter('boutique', $boutique)
                ->orderBy('p.date', 'DESC')
                ->getQuery()
                ->getResult();
            
            $data = [];
            foreach ($paiements as $paiement) {
                $data[] = [
                    'id' => $paiement->getId(),
                    'date' => $paiement->getDate()->format('Y-m-d H:i:s'),
                    'montant' => $paiement->getMontant(),
                    'modePaiement' => $paiement->getModePaiement(),
                    'reference' => $paiement->getReference(),
                    'facture' => [
                        'id' => $paiement->getFacture()->getId(),
                        'numero' => $paiement->getFacture()->getNumero(),
                        'client' => [
                            'nom' => $paiement->getFacture()->getClient()?->getNom(),
                            'prenom' => $paiement->getFacture()->getClient()?->getPrenom()
                        ]
                    ]
                ];
            }

            return $this->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Paiements de réservations par boutique
     */
    #[Route('/paiement/reservation/boutique/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/paiement/reservation/boutique/{id}",
        summary: "Paiements de réservations par boutique",
        description: "Retourne l'historique des paiements de réservations d'une boutique",
        tags: ['Paiements']
    )]
    public function getPaiementsReservationsByBoutique(
        int $id,
        PaiementReservationRepository $paiementRepository,
        BoutiqueRepository $boutiqueRepository
    ): Response {
        try {
            $boutique = $boutiqueRepository->find($id);
            if (!$boutique) {
                return $this->json(['success' => false, 'message' => 'Boutique non trouvée'], 404);
            }

            $paiements = $paiementRepository->createQueryBuilder('p')
                ->join('p.reservation', 'r')
                ->where('r.boutique = :boutique')
                ->setParameter('boutique', $boutique)
                ->orderBy('p.date', 'DESC')
                ->getQuery()
                ->getResult();
            
            $data = [];
            foreach ($paiements as $paiement) {
                $data[] = [
                    'id' => $paiement->getId(),
                    'date' => $paiement->getDate()->format('Y-m-d H:i:s'),
                    'montant' => $paiement->getMontant(),
                    'modePaiement' => $paiement->getModePaiement(),
                    'reference' => $paiement->getReference(),
                    'reservation' => [
                        'id' => $paiement->getReservation()->getId(),
                        'client' => [
                            'nom' => $paiement->getReservation()->getClient()?->getNom(),
                            'prenom' => $paiement->getReservation()->getClient()?->getPrenom(),
                            'numero' => $paiement->getReservation()->getClient()?->getNumero()
                        ]
                    ]
                ];
            }

            return $this->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Créer un paiement de facture
     */
    #[Route('/paiement/facture', methods: ['POST'])]
    #[OA\Post(
        path: "/api/paiement/facture",
        summary: "Créer un paiement de facture",
        description: "Enregistre un nouveau paiement pour une facture",
        tags: ['Paiements']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "factureId", type: "integer", example: 1),
                new OA\Property(property: "montant", type: "number", example: 25000),
                new OA\Property(property: "modePaiement", type: "string", example: "Espèces"),
                new OA\Property(property: "reference", type: "string", example: "REF123")
            ]
        )
    )]
    public function createPaiementFacture(
        Request $request,
        EntityManagerInterface $em
    ): Response {
        try {
            $data = json_decode($request->getContent(), true);
            
            $paiement = new PaiementFacture();
            $paiement->setDate(new \DateTime());
            $paiement->setMontant($data['montant']);
            $paiement->setModePaiement($data['modePaiement'] ?? 'Espèces');
            $paiement->setReference($data['reference'] ?? null);

            $em->persist($paiement);
            $em->flush();

            return $this->json(['success' => true, 'data' => ['id' => $paiement->getId()]]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Créer un paiement de réservation
     */
    #[Route('/paiement/reservation', methods: ['POST'])]
    #[OA\Post(
        path: "/api/paiement/reservation",
        summary: "Créer un paiement de réservation",
        description: "Enregistre un nouveau paiement pour une réservation",
        tags: ['Paiements']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "reservationId", type: "integer", example: 1),
                new OA\Property(property: "montant", type: "number", example: 15000),
                new OA\Property(property: "modePaiement", type: "string", example: "Mobile Money"),
                new OA\Property(property: "reference", type: "string", example: "MM789")
            ]
        )
    )]
    public function createPaiementReservation(
        Request $request,
        EntityManagerInterface $em
    ): Response {
        try {
            $data = json_decode($request->getContent(), true);
            
            $paiement = new PaiementReservation();
            $paiement->setDate(new \DateTime());
            $paiement->setMontant($data['montant']);
            $paiement->setModePaiement($data['modePaiement'] ?? 'Espèces');
            $paiement->setReference($data['reference'] ?? null);

            $em->persist($paiement);
            $em->flush();

            return $this->json(['success' => true, 'data' => ['id' => $paiement->getId()]]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}