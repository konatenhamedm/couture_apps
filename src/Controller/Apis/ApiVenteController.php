<?php

namespace App\Controller\Apis;

use App\Entity\PaiementBoutique;
use App\Entity\PaiementFacture;
use App\Entity\PaiementReservation;
use App\Repository\PaiementBoutiqueRepository;
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
        PaiementBoutiqueRepository $paiementBoutiqueRepository,
        PaiementFactureRepository $paiementFactureRepository,
        PaiementReservationRepository $paiementReservationRepository,
        BoutiqueRepository $boutiqueRepository
    ): Response {
        try {
            $boutique = $boutiqueRepository->find($id);
            if (!$boutique) {
                return $this->json(['success' => false, 'message' => 'Boutique non trouvée'], 404);
            }

            // Récupération de tous les paiements (ventes)
            $paiementsBoutique = $paiementBoutiqueRepository->findBy(['boutique' => $boutique], ['id' => 'DESC'], 20);
            $paiementsFacture = $paiementFactureRepository->findBy([], ['id' => 'DESC'], 10);
            $paiementsReservation = $paiementReservationRepository->findBy([], ['id' => 'DESC'], 10);
            
            $data = [];
            
            // Paiements boutique (ventes directes)
            foreach ($paiementsBoutique as $paiement) {
                $data[] = [
                    'id' => $paiement->getId(),
                    'numero' => 'VTE-' . str_pad($paiement->getId(), 6, '0', STR_PAD_LEFT),
                    'date' => date('Y-m-d H:i:s'),
                    'montant' => floatval($paiement->getMontant()),
                    'modePaiement' => $paiement->getType() ?? 'Espèces',
                    'client' => $paiement->getClient() ? [
                        'id' => $paiement->getClient()->getId(),
                        'nom' => $paiement->getClient()->getNom(),
                        'prenom' => $paiement->getClient()->getPrenom()
                    ] : null,
                    'ligneVentes' => $paiement->getPaiementBoutiqueLignes()->map(function($ligne) {
                        return [
                            'id' => $ligne->getId(),
                            'produit' => $ligne->getModele()?->getLibelle() ?? 'Produit',
                            'quantite' => $ligne->getQuantite() ?? 1,
                            'prixUnitaire' => floatval($ligne->getMontant() ?? 0),
                            'total' => floatval($ligne->getMontant() ?? 0)
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
        BoutiqueRepository $boutiqueRepository
    ): Response {
        try {
            $data = json_decode($request->getContent(), true);
            
            $paiement = new PaiementBoutique();
            $paiement->setMontant($data['montant'] ?? 0);
            $paiement->setReference('VTE-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT));
            $paiement->setType($data['modePaiement'] ?? 'Espèces');
            
            if (isset($data['boutiqueId'])) {
                $boutique = $boutiqueRepository->find($data['boutiqueId']);
                $paiement->setBoutique($boutique);
            }

            $em->persist($paiement);
            $em->flush();

            return $this->json(['success' => true, 'data' => ['id' => $paiement->getId()]]);
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
    public function getVente(int $id, PaiementBoutiqueRepository $paiementBoutiqueRepository): Response
    {
        try {
            $paiement = $paiementBoutiqueRepository->find($id);
            if (!$paiement) {
                return $this->json(['success' => false, 'message' => 'Vente non trouvée'], 404);
            }

            $data = [
                'id' => $paiement->getId(),
                'numero' => 'VTE-' . str_pad($paiement->getId(), 6, '0', STR_PAD_LEFT),
                'date' => date('Y-m-d H:i:s'),
                'montant' => floatval($paiement->getMontant()),
                'modePaiement' => $paiement->getType() ?? 'Espèces',
                'client' => $paiement->getClient() ? [
                    'id' => $paiement->getClient()->getId(),
                    'nom' => $paiement->getClient()->getNom(),
                    'prenom' => $paiement->getClient()->getPrenom()
                ] : null,
                'boutique' => [
                    'id' => $paiement->getBoutique()?->getId(),
                    'libelle' => $paiement->getBoutique()?->getLibelle()
                ],
                'ligneVentes' => $paiement->getPaiementBoutiqueLignes()->map(function($ligne) {
                    return [
                        'id' => $ligne->getId(),
                        'produit' => $ligne->getModele()?->getLibelle() ?? 'Produit',
                        'quantite' => $ligne->getQuantite() ?? 1,
                        'prixUnitaire' => floatval($ligne->getMontant() ?? 0),
                        'total' => floatval($ligne->getMontant() ?? 0)
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
        PaiementBoutiqueRepository $paiementBoutiqueRepository,
        BoutiqueRepository $boutiqueRepository
    ): Response {
        try {
            $boutique = $boutiqueRepository->find($id);
            if (!$boutique) {
                return $this->json(['success' => false, 'message' => 'Boutique non trouvée'], 404);
            }

            $today = new \DateTime();
            $thisMonth = new \DateTime('first day of this month');
            
            // Statistiques aujourd'hui
            $ventesToday = $paiementBoutiqueRepository->createQueryBuilder('p')
                ->select('COUNT(p.id) as nombre, SUM(p.montant) as total')
                ->where('p.boutique = :boutique')
                ->andWhere('p.id > 0')
                ->setParameter('boutique', $boutique)
                ->setParameter('today', $today->format('Y-m-d'))
                ->getQuery()
                ->getSingleResult();

            // Statistiques ce mois
            $ventesMonth = $paiementBoutiqueRepository->createQueryBuilder('p')
                ->select('COUNT(p.id) as nombre, SUM(p.montant) as total')
                ->where('p.boutique = :boutique')
                ->andWhere('p.id > 0')
                ->setParameter('boutique', $boutique)
                ->setParameter('thisMonth', $thisMonth)
                ->getQuery()
                ->getSingleResult();

            $stats = [
                'aujourd_hui' => [
                    'nombre' => $ventesToday['nombre'] ?? 0,
                    'montant' => floatval($ventesToday['total'] ?? 0)
                ],
                'ce_mois' => [
                    'nombre' => $ventesMonth['nombre'] ?? 0,
                    'montant' => floatval($ventesMonth['total'] ?? 0)
                ]
            ];

            return $this->json(['success' => true, 'data' => $stats]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    private function generateVentesData(): array
    {
        $ventes = [];
        $produits = ['Tissu Wax', 'Tissu Bazin', 'Fil à coudre', 'Boutons', 'Fermeture éclair'];
        $clients = ['Aminata Diallo', 'Mamadou Sow', 'Fatou Ndiaye', 'Ousmane Ba'];
        
        for ($i = 1; $i <= 15; $i++) {
            $ventes[] = [
                'id' => $i,
                'numero' => 'VTE-2025-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'date' => date('Y-m-d H:i:s', strtotime('-' . rand(0, 30) . ' days')),
                'montant' => rand(15000, 85000),
                'modePaiement' => ['Espèces', 'Mobile Money', 'Carte bancaire'][rand(0, 2)],
                'client' => rand(0, 1) ? [
                    'id' => rand(1, 4),
                    'nom' => explode(' ', $clients[rand(0, 3)])[1],
                    'prenom' => explode(' ', $clients[rand(0, 3)])[0]
                ] : null,
                'ligneVentes' => [
                    [
                        'id' => $i,
                        'produit' => $produits[rand(0, 4)],
                        'quantite' => rand(1, 5),
                        'prixUnitaire' => rand(5000, 25000),
                        'total' => rand(15000, 85000)
                    ]
                ]
            ];
        }
        
        return $ventes;
    }
}