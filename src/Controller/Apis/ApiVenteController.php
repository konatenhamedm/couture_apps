<?php

namespace App\Controller\Apis;

use App\Entity\Vente;
use App\Entity\LigneVente;
use App\Repository\VenteRepository;
use App\Repository\BoutiqueRepository;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

#[Route('/api')]
class ApiVenteController extends AbstractController
{
    /**
     * Liste des ventes par boutique
     */
    #[Route('/vente/boutique/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/vente/boutique/{id}",
        summary: "Liste des ventes par boutique",
        description: "Retourne la liste des ventes d'une boutique avec pagination",
        tags: ['Ventes']
    )]
    #[OA\Parameter(
        name: "id",
        description: "ID de la boutique",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    public function getVentesByBoutique(
        int $id,
        VenteRepository $venteRepository,
        BoutiqueRepository $boutiqueRepository
    ): Response {
        try {
            $boutique = $boutiqueRepository->find($id);
            if (!$boutique) {
                return $this->json(['success' => false, 'message' => 'Boutique non trouvée'], 404);
            }

            $ventes = $venteRepository->findBy(['boutique' => $boutique], ['date' => 'DESC']);
            
            $data = [];
            foreach ($ventes as $vente) {
                $data[] = [
                    'id' => $vente->getId(),
                    'numero' => $vente->getNumero(),
                    'date' => $vente->getDate()->format('Y-m-d H:i:s'),
                    'montant' => $vente->getMontant(),
                    'modePaiement' => $vente->getModePaiement(),
                    'client' => $vente->getClient() ? [
                        'id' => $vente->getClient()->getId(),
                        'nom' => $vente->getClient()->getNom(),
                        'prenom' => $vente->getClient()->getPrenom()
                    ] : null,
                    'ligneVentes' => $vente->getLigneVentes()->map(function($ligne) {
                        return [
                            'id' => $ligne->getId(),
                            'produit' => $ligne->getProduit(),
                            'quantite' => $ligne->getQuantite(),
                            'prixUnitaire' => $ligne->getPrixUnitaire(),
                            'total' => $ligne->getTotal()
                        ];
                    })->toArray()
                ];
            }

            return $this->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Créer une nouvelle vente
     */
    #[Route('/vente', methods: ['POST'])]
    #[OA\Post(
        path: "/api/vente",
        summary: "Créer une vente",
        description: "Crée une nouvelle vente avec ses lignes",
        tags: ['Ventes']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "boutiqueId", type: "integer", example: 1),
                new OA\Property(property: "clientId", type: "integer", example: 1),
                new OA\Property(property: "modePaiement", type: "string", example: "Espèces"),
                new OA\Property(
                    property: "lignes",
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "produit", type: "string", example: "Tissu Wax"),
                            new OA\Property(property: "quantite", type: "integer", example: 2),
                            new OA\Property(property: "prixUnitaire", type: "number", example: 15000)
                        ]
                    )
                )
            ]
        )
    )]
    public function createVente(
        Request $request,
        EntityManagerInterface $em,
        BoutiqueRepository $boutiqueRepository,
        ClientRepository $clientRepository
    ): Response {
        try {
            $data = json_decode($request->getContent(), true);
            
            $vente = new Vente();
            $vente->setNumero('VTE-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT));
            $vente->setDate(new \DateTime());
            $vente->setModePaiement($data['modePaiement'] ?? 'Espèces');
            
            if (isset($data['boutiqueId'])) {
                $boutique = $boutiqueRepository->find($data['boutiqueId']);
                $vente->setBoutique($boutique);
            }

            if (isset($data['clientId'])) {
                $client = $clientRepository->find($data['clientId']);
                $vente->setClient($client);
            }

            $montantTotal = 0;
            
            // Ajouter les lignes de vente
            if (isset($data['lignes'])) {
                foreach ($data['lignes'] as $ligneData) {
                    $ligne = new LigneVente();
                    $ligne->setProduit($ligneData['produit']);
                    $ligne->setQuantite($ligneData['quantite']);
                    $ligne->setPrixUnitaire($ligneData['prixUnitaire']);
                    $ligne->setTotal($ligneData['quantite'] * $ligneData['prixUnitaire']);
                    $ligne->setVente($vente);
                    
                    $montantTotal += $ligne->getTotal();
                    $em->persist($ligne);
                }
            }

            $vente->setMontant($montantTotal);
            $em->persist($vente);
            $em->flush();

            return $this->json(['success' => true, 'data' => ['id' => $vente->getId()]]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Détails d'une vente
     */
    #[Route('/vente/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/vente/{id}",
        summary: "Détails d'une vente",
        description: "Retourne les détails d'une vente avec ses lignes",
        tags: ['Ventes']
    )]
    public function getVente(int $id, VenteRepository $venteRepository): Response
    {
        try {
            $vente = $venteRepository->find($id);
            if (!$vente) {
                return $this->json(['success' => false, 'message' => 'Vente non trouvée'], 404);
            }

            $data = [
                'id' => $vente->getId(),
                'numero' => $vente->getNumero(),
                'date' => $vente->getDate()->format('Y-m-d H:i:s'),
                'montant' => $vente->getMontant(),
                'modePaiement' => $vente->getModePaiement(),
                'client' => $vente->getClient() ? [
                    'id' => $vente->getClient()->getId(),
                    'nom' => $vente->getClient()->getNom(),
                    'prenom' => $vente->getClient()->getPrenom()
                ] : null,
                'boutique' => [
                    'id' => $vente->getBoutique()->getId(),
                    'libelle' => $vente->getBoutique()->getLibelle()
                ],
                'ligneVentes' => $vente->getLigneVentes()->map(function($ligne) {
                    return [
                        'id' => $ligne->getId(),
                        'produit' => $ligne->getProduit(),
                        'quantite' => $ligne->getQuantite(),
                        'prixUnitaire' => $ligne->getPrixUnitaire(),
                        'total' => $ligne->getTotal()
                    ];
                })->toArray()
            ];

            return $this->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Statistiques des ventes
     */
    #[Route('/vente/stats/boutique/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/vente/stats/boutique/{id}",
        summary: "Statistiques des ventes",
        description: "Retourne les statistiques des ventes d'une boutique",
        tags: ['Ventes']
    )]
    public function getVentesStats(
        int $id,
        VenteRepository $venteRepository,
        BoutiqueRepository $boutiqueRepository
    ): Response {
        try {
            $boutique = $boutiqueRepository->find($id);
            if (!$boutique) {
                return $this->json(['success' => false, 'message' => 'Boutique non trouvée'], 404);
            }

            $today = new \DateTime();
            $thisMonth = new \DateTime('first day of this month');
            
            $ventesToday = $venteRepository->createQueryBuilder('v')
                ->select('COUNT(v.id) as nombre, SUM(v.montant) as total')
                ->where('v.boutique = :boutique')
                ->andWhere('DATE(v.date) = :today')
                ->setParameter('boutique', $boutique)
                ->setParameter('today', $today->format('Y-m-d'))
                ->getQuery()
                ->getSingleResult();

            $ventesMonth = $venteRepository->createQueryBuilder('v')
                ->select('COUNT(v.id) as nombre, SUM(v.montant) as total')
                ->where('v.boutique = :boutique')
                ->andWhere('v.date >= :thisMonth')
                ->setParameter('boutique', $boutique)
                ->setParameter('thisMonth', $thisMonth)
                ->getQuery()
                ->getSingleResult();

            $stats = [
                'aujourd_hui' => [
                    'nombre' => $ventesToday['nombre'] ?? 0,
                    'montant' => $ventesToday['total'] ?? 0
                ],
                'ce_mois' => [
                    'nombre' => $ventesMonth['nombre'] ?? 0,
                    'montant' => $ventesMonth['total'] ?? 0
                ]
            ];

            return $this->json(['success' => true, 'data' => $stats]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}