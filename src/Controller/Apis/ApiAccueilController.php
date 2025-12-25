<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Abonnement;
use App\Entity\Boutique;
use App\Entity\ModuleAbonnement;
use App\Entity\PaiementReservation;
use App\Repository\AbonnementRepository;
use App\Repository\CaisseBoutiqueRepository;
use App\Repository\CaisseSuccursaleRepository;
use App\Repository\FactureRepository;
use App\Repository\ModuleAbonnementRepository;
use App\Repository\PaiementBoutiqueRepository;
use App\Repository\PaiementFactureRepository;
use App\Repository\PaiementReservationRepository;
use App\Repository\SettingRepository;
use App\Repository\TypeUserRepository;
use App\Repository\UserRepository;
use App\Service\PaiementService;
use App\Service\Utils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model as Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use Nelmio\ApiDocBundle\Attribute\Model as AttributeModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * 
 */
#[Route('/api/accueil/{id}/{type}')]
#[OA\Tag(name: 'accueil', description: '')]
class ApiAccueilController extends ApiInterface
{
    #[Route('', name: 'api_accueil_info', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Retourne les informations de l\'accueil',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'abonnements', type: 'array', items: new OA\Items(type: 'object')),
                new OA\Property(property: 'facturesProches', type: 'array', items: new OA\Items(type: 'object')),
                new OA\Property(property: 'meilleuresVentes', type: 'array', items: new OA\Items(type: 'object'))
            ]
        )
    )]
    public function getAllInfoAccueil(
        ModuleAbonnementRepository $abonnementRepository,
        $id,
        $type,
        SettingRepository $settingRepository,
        FactureRepository $factureRepository,
        PaiementBoutiqueRepository $paiementBoutiqueRepository,
        PaiementReservationRepository $paiementReservationRepository,
        TypeUserRepository $typeUserRepository,
        CaisseBoutiqueRepository $caisseBoutiqueRepository,
        CaisseSuccursaleRepository $caisseSuccursaleRepository,
    ): JsonResponse {

       $abonnements = $abonnementRepository->findBy(["etat" => 'actif'], ['numero' => 'ASC']);
        $settings = $settingRepository->findOneBy(['entreprise' => $this->getUser()->getEntreprise()]);
        $facturesProches = $type != 'boutique' ? $factureRepository->findUpcomingUnpaidInvoices($id, 10) : [];
        $ventesBoutique = $type == 'boutique' ? $paiementBoutiqueRepository->findTopSellingModelsOfWeek($id, 10) :[];
        $ventesReservation = $type == 'boutique' ? $paiementReservationRepository->findTopReservedModelsOfWeek($id, 10) :[];
        $meilleuresVentes = $type == 'boutique' ?  $this->combineAndSortSales($ventesBoutique, $ventesReservation, 10) : [];
        
        $response = $this->responseData([
            "caisse"=> $type == 'boutique' ? $caisseBoutiqueRepository->findOneBy(['boutique' => $id])->getMontant() : $caisseSuccursaleRepository->findOneBy(['succursale' => $id])->getMontant(), 
            "depenses"=> 0,
            "settings"=>$settings,
            "abonnements" => $abonnements,
            "commandes" => $facturesProches,
            "meilleuresVentes" =>  $meilleuresVentes
        ], 'group1', ['Content-Type' => 'application/json']);
        return $response;
    }

    /**
     * Combine et trie les ventes de boutique et réservations
     */
    private function combineAndSortSales(array $ventesBoutique, array $ventesReservation, int $limit): array
    {
        $combined = [];
        
        // Ajouter les ventes de boutique
        foreach ($ventesBoutique as $vente) {
            $key = $vente['modele_id'];
            if (!isset($combined[$key])) {
                $combined[$key] = [
                    'modele_id' => $vente['modele_id'],
                    'modele_nom' => $vente['modele_nom'],
                    'quantite_totale' => 0,
                    'chiffre_affaires' => 0
                ];
            }
            $combined[$key]['quantite_totale'] += (int)$vente['quantite_totale'];
            $combined[$key]['chiffre_affaires'] += (float)$vente['chiffre_affaires'];
        }
        
        // Ajouter les ventes de réservation
        foreach ($ventesReservation as $vente) {
            $key = $vente['modele_id'];
            if (!isset($combined[$key])) {
                $combined[$key] = [
                    'modele_id' => $vente['modele_id'],
                    'modele_nom' => $vente['modele_nom'],
                    'quantite_totale' => 0,
                    'chiffre_affaires' => 0
                ];
            }
            $combined[$key]['quantite_totale'] += (int)$vente['quantite_totale'];
            $combined[$key]['chiffre_affaires'] += (float)$vente['chiffre_affaires'];
        }
        
        // Trier par quantité puis par chiffre d'affaires
        usort($combined, function($a, $b) {
            if ($a['quantite_totale'] === $b['quantite_totale']) {
                return $b['chiffre_affaires'] <=> $a['chiffre_affaires'];
            }
            return $b['quantite_totale'] <=> $a['quantite_totale'];
        });
        
        // Limiter aux N premiers résultats
        return array_slice($combined, 0, $limit);
    }
}
