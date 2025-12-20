<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Repository\FactureRepository;
use App\Repository\VenteRepository;
use App\Repository\PaiementReservationRepository;
use App\Repository\PaiementFactureRepository;
use App\Repository\BoutiqueRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

#[Route('/api')]
class ApiRapportController extends ApiInterface
{
    /**
     * Données pour les rapports financiers
     */
    #[Route('/rapport/financier', methods: ['POST'])]
    #[OA\Post(
        path: "/api/rapport/financier",
        summary: "Rapport financier",
        description: "Génère les données pour les rapports financiers avec KPIs et graphiques",
        tags: ['Rapports']
    )]
    #[OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "periode", type: "string", enum: ["jour", "semaine", "mois", "trimestre", "annee", "personnalise"], example: "mois"),
                new OA\Property(property: "dateDebut", type: "string", format: "date", example: "2025-01-01"),
                new OA\Property(property: "dateFin", type: "string", format: "date", example: "2025-01-31"),
                new OA\Property(property: "boutiqueId", type: "integer", example: 1),
                new OA\Property(property: "typeRapport", type: "string", enum: ["complet", "factures", "reservations", "ventes", "paiements"], example: "complet")
            ]
        )
    )]
    public function getRapportFinancier(
        Request $request,
        FactureRepository $factureRepository,
        VenteRepository $venteRepository,
        PaiementReservationRepository $paiementReservationRepository,
        PaiementFactureRepository $paiementFactureRepository,
        BoutiqueRepository $boutiqueRepository
    ): Response {
        try {
            $data = json_decode($request->getContent(), true) ?? [];
            $periode = $data['periode'] ?? 'mois';
            $typeRapport = $data['typeRapport'] ?? 'complet';
            $entreprise = $this->getUser()->getEntreprise();

            // Calcul des dates selon la période
            $dates = $this->calculatePeriodDates($periode, $data);

            $rapport = [
                'kpis' => $this->getKpisFinanciers($dates, $entreprise, $typeRapport),
                'evolutionRevenus' => $this->getEvolutionRevenus($dates, $entreprise),
                'repartitionPaiements' => $this->getRepartitionPaiements($dates, $entreprise),
                'topBoutiques' => $this->getTopBoutiques($dates, $entreprise),
                'comparaisonPeriodes' => $this->getComparaisonPeriodes($dates, $entreprise)
            ];

            return $this->json(['success' => true, 'data' => $rapport]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Export PDF du rapport
     */
    #[Route('/rapport/export/pdf', methods: ['POST'])]
    #[OA\Post(
        path: "/api/rapport/export/pdf",
        summary: "Export PDF",
        description: "Génère et télécharge un rapport PDF",
        tags: ['Rapports']
    )]
    public function exportPDF(Request $request): Response
    {
        try {
            // Simulation de génération PDF
            $data = json_decode($request->getContent(), true) ?? [];
            
            // Ici vous intégreriez une librairie PDF comme TCPDF ou DomPDF
            $pdfContent = $this->generatePDFContent($data);
            
            return $this->json([
                'success' => true, 
                'data' => [
                    'url' => '/downloads/rapport_' . date('Y-m-d_H-i-s') . '.pdf',
                    'message' => 'Rapport PDF généré avec succès'
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Export Excel du rapport
     */
    #[Route('/rapport/export/excel', methods: ['POST'])]
    #[OA\Post(
        path: "/api/rapport/export/excel",
        summary: "Export Excel",
        description: "Génère et télécharge un rapport Excel",
        tags: ['Rapports']
    )]
    public function exportExcel(Request $request): Response
    {
        try {
            // Simulation de génération Excel
            $data = json_decode($request->getContent(), true) ?? [];
            
            // Ici vous intégreriez PhpSpreadsheet
            $excelContent = $this->generateExcelContent($data);
            
            return $this->json([
                'success' => true, 
                'data' => [
                    'url' => '/downloads/rapport_' . date('Y-m-d_H-i-s') . '.xlsx',
                    'message' => 'Rapport Excel généré avec succès'
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    private function calculatePeriodDates(string $periode, array $data): array
    {
        $now = new \DateTime();
        
        switch ($periode) {
            case 'jour':
                return [
                    'debut' => new \DateTime('today'),
                    'fin' => new \DateTime('tomorrow')
                ];
            case 'semaine':
                return [
                    'debut' => new \DateTime('monday this week'),
                    'fin' => new \DateTime('sunday this week')
                ];
            case 'mois':
                return [
                    'debut' => new \DateTime('first day of this month'),
                    'fin' => new \DateTime('last day of this month')
                ];
            case 'trimestre':
                $month = (int)$now->format('n');
                $quarter = ceil($month / 3);
                $startMonth = ($quarter - 1) * 3 + 1;
                return [
                    'debut' => new \DateTime($now->format('Y') . '-' . $startMonth . '-01'),
                    'fin' => new \DateTime($now->format('Y') . '-' . ($startMonth + 2) . '-' . date('t', mktime(0, 0, 0, $startMonth + 2, 1, $now->format('Y'))))
                ];
            case 'annee':
                return [
                    'debut' => new \DateTime($now->format('Y') . '-01-01'),
                    'fin' => new \DateTime($now->format('Y') . '-12-31')
                ];
            case 'personnalise':
                return [
                    'debut' => new \DateTime($data['dateDebut'] ?? 'today'),
                    'fin' => new \DateTime($data['dateFin'] ?? 'today')
                ];
            default:
                return [
                    'debut' => new \DateTime('first day of this month'),
                    'fin' => new \DateTime('last day of this month')
                ];
        }
    }

    private function getKpisFinanciers(array $dates, $entreprise, string $typeRapport): array
    {
        // Simulation des KPIs - à remplacer par de vraies requêtes
        return [
            'revenusTotal' => rand(35000000, 45000000),
            'factures' => rand(20000000, 28000000),
            'ventes' => rand(8000000, 15000000),
            'croissance' => rand(15, 25) / 10,
            'nombreTransactions' => rand(450, 650),
            'panierMoyen' => rand(65000, 85000)
        ];
    }

    private function getEvolutionRevenus(array $dates, $entreprise): array
    {
        $mois = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'];
        $evolution = [];
        
        foreach ($mois as $m) {
            $evolution[] = [
                'mois' => $m,
                'factures' => rand(3000000, 5000000),
                'reservations' => rand(2500000, 4000000),
                'ventes' => rand(1200000, 2500000)
            ];
        }
        
        return $evolution;
    }

    private function getRepartitionPaiements(array $dates, $entreprise): array
    {
        return [
            ['type' => 'Espèces', 'montant' => rand(12000000, 18000000)],
            ['type' => 'Mobile Money', 'montant' => rand(10000000, 15000000)],
            ['type' => 'Carte bancaire', 'montant' => rand(6000000, 10000000)],
            ['type' => 'Virement', 'montant' => rand(3000000, 7000000)]
        ];
    }

    private function getTopBoutiques(array $dates, $entreprise): array
    {
        return [
            ['nom' => 'Boutique Centre-ville', 'revenus' => rand(8000000, 12000000)],
            ['nom' => 'Boutique Plateau', 'revenus' => rand(6000000, 10000000)],
            ['nom' => 'Boutique Médina', 'revenus' => rand(5000000, 8000000)],
            ['nom' => 'Boutique Parcelles', 'revenus' => rand(4000000, 7000000)]
        ];
    }

    private function getComparaisonPeriodes(array $dates, $entreprise): array
    {
        return [
            'periodeCourante' => rand(35000000, 45000000),
            'periodePrecedente' => rand(30000000, 40000000),
            'evolution' => rand(10, 25),
            'tendance' => 'hausse'
        ];
    }

    private function generatePDFContent(array $data): string
    {
        // Simulation - à remplacer par une vraie génération PDF
        return "Contenu PDF généré";
    }

    private function generateExcelContent(array $data): string
    {
        // Simulation - à remplacer par une vraie génération Excel
        return "Contenu Excel généré";
    }
}