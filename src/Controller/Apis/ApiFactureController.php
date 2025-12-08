<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\CategorieMesure;
use App\Entity\CategorieFacture;
use App\Entity\Client;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Facture;
use App\Entity\LigneMesure;
use App\Entity\Mesure;
use App\Entity\PaiementFacture;
use App\Repository\CaisseSuccursaleRepository;
use App\Repository\CategorieMesureRepository;
use App\Repository\CategorieFactureRepository;
use App\Repository\ClientRepository;
use App\Repository\EntrepriseRepository;
use App\Repository\FactureRepository;
use App\Repository\SurccursaleRepository;
use App\Repository\TypeMesureRepository;
use App\Repository\TypeUserRepository;
use App\Repository\UserRepository;
use App\Service\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model as Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use Nelmio\ApiDocBundle\Attribute\Model as AttributeModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Contrôleur pour la gestion des factures
 * Permet de créer, lire, mettre à jour et supprimer des factures avec mesures, lignes de mesures et paiements associés
 */
#[Route('/api/facture')]
#[OA\Tag(name: 'facture', description: 'Gestion des factures de couture avec mesures et paiements')]
class ApiFactureController extends ApiInterface
{
    /**
     * Liste toutes les factures du système
     */
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/facture/",
        summary: "Lister toutes les factures",
        description: "Retourne la liste paginée de toutes les factures disponibles dans le système",
        tags: ['facture']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des factures récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1, description: "Identifiant unique de la facture"),
                    new OA\Property(property: "reference", type: "string", example: "FACT-2025-001", description: "Référence unique de la facture"),
                    new OA\Property(property: "montantTotal", type: "number", format: "float", example: 50000, description: "Montant total de la facture"),
                    new OA\Property(property: "avance", type: "number", format: "float", example: 20000, description: "Montant de l'avance payée"),
                    new OA\Property(property: "remise", type: "number", format: "float", example: 5000, description: "Montant de la remise"),
                    new OA\Property(property: "resteArgent", type: "number", format: "float", example: 30000, description: "Reste à payer"),
                    new OA\Property(property: "dateDepot", type: "string", format: "date-time", example: "2025-01-15T10:30:00+00:00", description: "Date de dépôt"),
                    new OA\Property(property: "dateRetrait", type: "string", format: "date-time", example: "2025-01-22T10:30:00+00:00", description: "Date de retrait prévue"),
                    new OA\Property(property: "signature", type: "string", nullable: true, example: "/uploads/signatures/sign_001.jpg", description: "Signature du client"),
                    new OA\Property(property: "client", type: "object", description: "Informations du client"),
                    new OA\Property(property: "entreprise", type: "object", description: "Entreprise"),
                    new OA\Property(property: "mesures", type: "array", description: "Liste des mesures associées", items: new OA\Items(type: "object"))
                ]
            )
        )
    )]
    #[OA\Response(response: 500, description: "Erreur serveur lors de la récupération")]
    public function index(FactureRepository $factureRepository): Response
    {
        try {
            $factures = $this->paginationService->paginate($factureRepository->findAll());
            $response = $this->responseData($factures, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des factures");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Liste les factures d'une entreprise spécifique
     */
    #[Route('/entreprise', methods: ['GET'])]
    #[OA\Get(
        path: "/api/facture/entreprise",
        summary: "Lister les factures d'une entreprise",
        description: "Retourne la liste paginée des factures de l'entreprise de l'utilisateur authentifié. Nécessite un abonnement actif.",
        tags: ['facture']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des factures de l'entreprise récupérée avec succès",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 1),
                    new OA\Property(property: "reference", type: "string", example: "FACT-2025-001"),
                    new OA\Property(property: "montantTotal", type: "number", format: "float", example: 50000),
                    new OA\Property(property: "avance", type: "number", format: "float", example: 20000),
                    new OA\Property(property: "resteArgent", type: "number", format: "float", example: 30000),
                    new OA\Property(property: "dateDepot", type: "string", format: "date-time"),
                    new OA\Property(property: "dateRetrait", type: "string", format: "date-time"),
                    new OA\Property(property: "client", type: "object", description: "Client associé"),
                    new OA\Property(property: "mesures", type: "array", description: "Mesures", items: new OA\Items(type: "object")),
                    new OA\Property(property: "paiements", type: "array", description: "Paiements", items: new OA\Items(type: "object"))
                ]
            )
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 500, description: "Erreur lors de la récupération")]
    public function indexAll(FactureRepository $factureRepository, TypeUserRepository $typeUserRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }



        try {

            if ($this->getUser()->getType() == $typeUserRepository->findOneBy(['code' => 'SADM'])) {
                $factures = $this->paginationService->paginate($factureRepository->findBy(
                    ['entreprise' => $this->getUser()->getEntreprise()],
                    ['id' => 'DESC']
                ));
            } else {
                $factures = $this->paginationService->paginate($factureRepository->findBy(
                    ['succursale' => $this->getUser()->getSurccursale()],
                    ['id' => 'DESC']
                ));
            }

            $response = $this->responseData($factures, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des factures de l'entreprise");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Récupère les détails d'une facture spécifique
     */
    #[Route('/get/one/{id}', methods: ['GET'])]
    #[OA\Get(
        path: "/api/facture/get/one/{id}",
        summary: "Détails d'une facture",
        description: "Affiche les informations détaillées d'une facture spécifique avec toutes ses mesures, lignes de mesures et paiements. Nécessite un abonnement actif.",
        tags: ['facture']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique de la facture",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: "Facture trouvée avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "reference", type: "string", example: "FACT-2025-001"),
                new OA\Property(property: "montantTotal", type: "number", format: "float", example: 50000),
                new OA\Property(property: "avance", type: "number", format: "float", example: 20000),
                new OA\Property(property: "remise", type: "number", format: "float", example: 5000),
                new OA\Property(property: "resteArgent", type: "number", format: "float", example: 30000),
                new OA\Property(property: "dateDepot", type: "string", format: "date-time"),
                new OA\Property(property: "dateRetrait", type: "string", format: "date-time"),
                new OA\Property(property: "signature", type: "string", nullable: true),
                new OA\Property(
                    property: "client",
                    type: "object",
                    description: "Client",
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 5),
                        new OA\Property(property: "nom", type: "string", example: "Kouassi"),
                        new OA\Property(property: "prenom", type: "string", example: "Yao"),
                        new OA\Property(property: "numero", type: "string", example: "+225 0123456789")
                    ]
                ),
                new OA\Property(property: "mesures", type: "array", description: "Mesures de couture", items: new OA\Items(type: "object")),
                new OA\Property(property: "paiements", type: "array", description: "Historique des paiements", items: new OA\Items(type: "object"))
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 404, description: "Facture non trouvée")]
    public function getOne(?Facture $facture): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            if ($facture) {
                $response = $this->responseData($facture, 'group1', ['Content-Type' => 'application/json']);
            } else {
                $this->setMessage('Cette ressource est inexistante');
                $this->setStatusCode(404);
                $response = $this->response(null);
            }
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage($exception->getMessage());
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Crée une nouvelle facture avec mesures et paiement
     * 
     * Structure des données attendues :
     * - Champs simples : clientId, succursaleId, avance, remise, montantTotal, resteArgent, dateRetrait
     * - Fichier signature : signature (optionnel)
     * - Mesures (JSON string) : 
     *   [
     *     {
     *       "typeMesureId": 1,
     *       "montant": 25000,
     *       "remise": 2000,
     *       "ligneMesures": [
     *         {"categorieId": 1, "taille": "85cm"},
     *         {"categorieId": 2, "taille": "120cm"}
     *       ]
     *     }
     *   ]
     * - Photos des mesures : mesures[0][photoPagne], mesures[0][photoModele], etc.
     */
    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/facture/create",
        summary: "Créer une nouvelle facture",
        description: "Permet de créer une nouvelle facture complète avec client (nouveau ou existant), mesures de couture détaillées, lignes de mesures, photos (pagne et modèle), signature et paiement initial. La facture génère automatiquement un paiement et met à jour la caisse de la succursale si une avance est versée. Nécessite un abonnement actif.",
        tags: ['facture']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                type: "object",
                required: ["montantTotal"],
                properties: [
                    new OA\Property(
                        property: "clientId",
                        type: "integer",
                        example: 5,
                        description: "ID du client existant (obligatoire)"
                    ),
                    new OA\Property(
                        property: "succursaleId",
                        type: "integer",
                        example: 1,
                        description: "ID de la succursale (obligatoire)"
                    ),
                    new OA\Property(
                        property: "avance",
                        type: "number",
                        example: 20000,
                        description: "Montant de l'avance payée (optionnel)"
                    ),
                    new OA\Property(
                        property: "remise",
                        type: "number",
                        example: 5000,
                        description: "Montant de la remise accordée (optionnel)"
                    ),
                    new OA\Property(
                        property: "montantTotal",
                        type: "number",
                        example: 50000,
                        description: "Montant total de la facture (obligatoire)"
                    ),
                    new OA\Property(
                        property: "resteArgent",
                        type: "number",
                        example: 30000,
                        description: "Reste à payer après avance et remise"
                    ),
                    new OA\Property(
                        property: "dateRetrait",
                        type: "string",
                        example: "2025-02-01 14:00:00",
                        description: "Date de retrait prévue (format: Y-m-d H:i:s)"
                    ),
                    new OA\Property(
                        property: "signature",
                        type: "string",
                        format: "binary",
                        description: "Image de la signature du client (optionnel, formats: JPG, PNG)"
                    ),
                    new OA\Property(
                        property: "mesures",
                        type: "string",
                        description: "Données des mesures au format JSON string (obligatoire). Structure: [{typeMesureId, montant, remise?, ligneMesures: [{categorieId, taille}]}]",
                        example: '[{"typeMesureId":1,"montant":25000,"remise":2000,"ligneMesures":[{"categorieId":1,"taille":"85cm"},{"categorieId":2,"taille":"120cm"}]},{"typeMesureId":2,"montant":20000,"ligneMesures":[{"categorieId":3,"taille":"90cm"},{"categorieId":4,"taille":"42cm"}]}]'
                    ),
                    new OA\Property(
                        property: "mesures[0][photoPagne]",
                        type: "string",
                        format: "binary",
                        description: "Photo du tissu/pagne pour la mesure 0 (optionnel)"
                    ),
                    new OA\Property(
                        property: "mesures[0][photoModele]",
                        type: "string",
                        format: "binary",
                        description: "Photo du modèle pour la mesure 0 (optionnel)"
                    ),
                    new OA\Property(
                        property: "mesures[1][photoPagne]",
                        type: "string",
                        format: "binary",
                        description: "Photo du tissu/pagne pour la mesure 1 (optionnel)"
                    ),
                    new OA\Property(
                        property: "mesures[1][photoModele]",
                        type: "string",
                        format: "binary",
                        description: "Photo du modèle pour la mesure 1 (optionnel)"
                    )
                ]
            )
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Facture créée avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 15),
                new OA\Property(property: "reference", type: "string", example: "FACT-2025-015"),
                new OA\Property(property: "montantTotal", type: "number", example: 50000),
                new OA\Property(property: "avance", type: "number", example: 20000),
                new OA\Property(property: "resteArgent", type: "number", example: 30000),
                new OA\Property(property: "dateDepot", type: "string", format: "date-time"),
                new OA\Property(property: "dateRetrait", type: "string", format: "date-time"),
                new OA\Property(property: "client", type: "object"),
                new OA\Property(property: "mesures", type: "array", items: new OA\Items(type: "object")),
                new OA\Property(property: "paiements", type: "array", items: new OA\Items(type: "object"))
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides ou fichiers non acceptés")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    public function create(
        Request $request,
        UserRepository $userRepository,
        CaisseSuccursaleRepository $caisseSuccursaleRepository,
        Utils $utils,
        TypeMesureRepository $typeMesureRepository,
        ClientRepository $clientRepository,
        CategorieMesureRepository $categorieMesureRepository,
        FactureRepository $factureRepository,
        EntrepriseRepository $entrepriseRepository,
        EntityManagerInterface $entityManager,
        SurccursaleRepository $surccursaleRepository
    ): Response {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        $names = 'document_' . '01';
        $filePrefix = str_slug($names);
        $filePath = $this->getUploadDir(self::UPLOAD_PATH, true);
        $facture = new Facture();
        $facture->setEntreprise($this->getUser()->getEntreprise());
        $admin = $userRepository->getUserByCodeType($this->getUser()->getEntreprise());

        $facture->setClient($clientRepository->find($request->get('clientId')));
        $facture->setDateDepot(new \DateTime());
        $facture->setAvance($request->get('avance'));
        $facture->setSuccursale($surccursaleRepository->find($request->get('succursaleId')));

        // Gestion de la signature
        $uploadedFichierSignature = $request->files->get('signature');
        if ($uploadedFichierSignature) {
            $fichierSignature = $utils->sauvegardeFichier($filePath, $filePrefix, $uploadedFichierSignature, self::UPLOAD_PATH);
            $facture->setSignature($fichierSignature);
        }

        $facture->setRemise($request->get('remise'));
        $facture->setMontantTotal($request->get('montantTotal'));
        $facture->setResteArgent($request->get('resteArgent'));
        $facture->setDateRetrait($request->get('dateRetrait') ? new \DateTime($request->get('dateRetrait')) : null);
        $facture->setCreatedBy($this->getUser());
        $facture->setUpdatedBy($this->getUser());
        $facture->setCreatedAtValue(new \DateTime());
        $facture->setUpdatedAt(new \DateTime());

        // Gestion des mesures depuis formData
        $mesuresJson = $request->get('mesures');
        $lignesMesure = $mesuresJson ? json_decode($mesuresJson, true) : [];
        $uploadedFiles = $request->files->get('mesures');

        if (isset($lignesMesure) && is_array($lignesMesure)) {
            foreach ($lignesMesure as $index => $ligne) {
                $mesure = new Mesure();
                $mesure->setTypeMesure($typeMesureRepository->find($ligne['typeMesureId']));
                $mesure->setMontant($ligne['montant']);
                $mesure->setRemise($ligne['remise'] ?? 0);

                // Upload des photos (pagne et modèle)
                if (isset($uploadedFiles[$index])) {
                    $fileKeys = ['photoPagne', 'photoModele'];
                    foreach ($fileKeys as $key) {
                        if (!empty($uploadedFiles[$index][$key])) {
                            $uploadedFile = $uploadedFiles[$index][$key];
                            $fichier = $utils->sauvegardeFichier($filePath, $filePrefix, $uploadedFile, self::UPLOAD_PATH);
                            if ($fichier) {
                                $setter = 'set' . ucfirst($key);
                                $mesure->$setter($fichier);
                            }
                        }
                    }
                }

                // Gestion des lignes de mesures détaillées
                $ligneMesures = $ligne['ligneMesures'] ?? [];
                if (isset($ligneMesures) && is_array($ligneMesures)) {
                    foreach ($ligneMesures as $ligneData) {
                        $ligneMesure = new LigneMesure();
                        $ligneMesure->setCategorieMesure($categorieMesureRepository->find($ligneData['categorieId']));
                        $ligneMesure->setTaille($ligneData['taille']);
                        $entityManager->persist($ligneMesure);
                        $mesure->addLigneMesure($ligneMesure);
                    }
                }

                $entityManager->persist($mesure);
                $facture->addMesure($mesure);
            }
        }

        // Validation AVANT la persistence
        $errorResponse = $this->errorResponse($facture);
        if ($errorResponse !== null) {
            return $errorResponse;
        }

        // Persister la facture
        $entityManager->persist($facture);

        // Créer le paiement
        $paiement = new PaiementFacture();
        $paiement->setMontant($facture->getAvance() ?? 0);
        $paiement->setType('paiementFacture');
        $paiement->setReference($utils->generateReference('PMT'));
        $paiement->setCreatedBy($this->getUser());
        $paiement->setUpdatedBy($this->getUser());
        $paiement->setCreatedAtValue(new \DateTime());
        $paiement->setUpdatedAt(new \DateTime());
        $paiement->setFacture($facture);
        
        $entityManager->persist($paiement);
        $facture->addPaiementFacture($paiement);

        // Mise à jour de la caisse si avance
        if ($request->get('avance') != null && $request->get('avance') > 0) {
            $caisse = $caisseSuccursaleRepository->findOneBy(['succursale' => $request->get('succursaleId')]);
            if ($caisse) {
                $caisse->setMontant((int)$caisse->getMontant() + (int)$request->get('avance'));
                $caisse->setType('caisse_succursale');
                $caisseSuccursaleRepository->add($caisse, true);
            }

            // Envoi de notifications
            if ($admin) {
                $this->sendMailService->sendNotification([
                    'entreprise' => $this->getUser()->getEntreprise(),
                    "user" => $admin,
                    "libelle" => sprintf(
                        "Bonjour %s,\n\n" .
                            "Nous vous informons qu'un nouveau paiement vient d'être enregistré dans la succursale **%s**.\n\n" .
                            "- Montant : %s FCFA\n" .
                            "- Effectué par : %s\n" .
                            "- Date : %s\n\n" .
                            "Cordialement,\nVotre application de gestion.",
                        $admin->getLogin(),
                        $this->getUser()->getSurccursale() ? $this->getUser()->getSurccursale()->getLibelle() : "N/A",
                        number_format($request->get('avance'), 0, ',', ' '),
                        $this->getUser()->getNom() && $this->getUser()->getPrenoms()
                            ? $this->getUser()->getNom() . " " . $this->getUser()->getPrenoms()
                            : $this->getUser()->getLogin(),
                        (new \DateTime())->format('d/m/Y H:i')
                    ),
                    "titre" => "Paiement facture - " . ($this->getUser()->getSurccursale() ? $this->getUser()->getSurccursale()->getLibelle() : ""),
                ]);
            }

            $this->sendMailService->send(
                $this->sendMail,
                $this->superAdmin,
                "Paiement facture - " . $this->getUser()->getEntreprise()->getLibelle(),
                "paiement_email",
                [
                    "boutique_libelle" => $this->getUser()->getEntreprise()->getLibelle(),
                    "montant" => number_format($request->get('avance'), 0, ',', ' ') . " FCFA",
                    "date" => (new \DateTime())->format('d/m/Y H:i'),
                ]
            );
        }

        // Flush final pour tout sauvegarder
        $entityManager->flush();

        return $this->responseData($facture, 'group1', ['Content-Type' => 'application/json']);
    }

    /**
     * Met à jour une facture existante avec ses mesures
     */
    #[Route('/update/{id}', methods: ['PUT', 'POST'])]
    #[OA\Put(
        path: "/api/facture/update/{id}",
        summary: "Mettre à jour une facture",
        description: "Permet de mettre à jour une facture existante avec modification des informations client, mesures, lignes de mesures et photos. Les mesures et lignes existantes peuvent être modifiées, supprimées ou de nouvelles peuvent être ajoutées. Nécessite un abonnement actif.",
        tags: ['facture']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique de la facture à mettre à jour",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                type: "object",
                properties: [
                    new OA\Property(property: "clientId", type: "integer", example: 5, description: "Nouvel ID du client"),
                    new OA\Property(property: "avance", type: "number", example: 25000, description: "Nouveau montant de l'avance"),
                    new OA\Property(property: "remise", type: "number", example: 7000, description: "Nouvelle remise"),
                    new OA\Property(property: "montantTotal", type: "number", example: 55000, description: "Nouveau montant total"),
                    new OA\Property(property: "resteArgent", type: "number", example: 28000, description: "Nouveau reste à payer"),
                    new OA\Property(property: "dateRetrait", type: "string", example: "2025-02-05 15:00:00", description: "Nouvelle date de retrait"),
                    new OA\Property(
                        property: "mesures",
                        type: "string",
                        description: "Mesures au format JSON string. Structure: [{id?, typeMesureId, montant, remise?, ligneMesures: [{id?, categorieId, taille}]}]",
                        example: '[{"id":1,"typeMesureId":1,"montant":27000,"remise":2500,"ligneMesures":[{"id":1,"categorieId":1,"taille":"90cm"},{"categorieId":2,"taille":"125cm"}]},{"typeMesureId":2,"montant":22000,"ligneMesures":[{"categorieId":3,"taille":"95cm"}]}]'
                    ),
                    new OA\Property(
                        property: "mesures[0][photoPagne]",
                        type: "string",
                        format: "binary",
                        description: "Nouvelle photo du pagne pour la mesure 0"
                    ),
                    new OA\Property(
                        property: "mesures[0][photoModele]",
                        type: "string",
                        format: "binary",
                        description: "Nouvelle photo du modèle pour la mesure 0"
                    )
                ]
            )
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Facture mise à jour avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "reference", type: "string", example: "FACT-2025-001"),
                new OA\Property(property: "montantTotal", type: "number", example: 55000),
                new OA\Property(property: "updatedAt", type: "string", format: "date-time")
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 404, description: "Facture non trouvée")]
    #[OA\Response(response: 500, description: "Erreur lors de la mise à jour")]
    public function update(
        Request $request,
        Facture $facture,
        FactureRepository $factureRepository,
        TypeMesureRepository $typeMesureRepository,
        ClientRepository $clientRepository,
        CategorieMesureRepository $categorieMesureRepository,
        Utils $utils,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            // Récupération des données depuis formData
            $mesuresJson = $request->get('mesures');
            $data = [];
            if ($mesuresJson) {
                $data['mesures'] = json_decode($mesuresJson, true);
            }
            
            // Récupération des autres champs
            if ($request->get('clientId')) $data['clientId'] = $request->get('clientId');
            if ($request->get('avance')) $data['avance'] = $request->get('avance');
            if ($request->get('remise')) $data['remise'] = $request->get('remise');
            if ($request->get('montantTotal')) $data['montantTotal'] = $request->get('montantTotal');
            if ($request->get('resteArgent')) $data['resteArgent'] = $request->get('resteArgent');
            if ($request->get('dateRetrait')) $data['dateRetrait'] = $request->get('dateRetrait');
            
            $uploadedFiles = $request->files->get('mesures');

            if ($facture === null) {
                $this->setMessage("Cette ressource est inexistante");
                $this->setStatusCode(404);
                return $this->response('[]');
            }

            // Mise à jour des informations de base
            if (isset($data['clientId'])) {
                $facture->setClient($clientRepository->find($data['clientId']));
            }

            $facture->setAvance($data['avance'] ?? $facture->getAvance());
            $facture->setRemise($data['remise'] ?? $facture->getRemise());
            $facture->setMontantTotal($data['montantTotal'] ?? $facture->getMontantTotal());
            $facture->setResteArgent($data['resteArgent'] ?? $facture->getResteArgent());
            $facture->setDateRetrait(isset($data['dateRetrait']) ? new \DateTime($data['dateRetrait']) : $facture->getDateRetrait());
            $facture->setUpdatedBy($this->getUser());
            $facture->setUpdatedAt(new \DateTime());

            // Gestion des mesures
            if (isset($data['mesures']) && is_array($data['mesures'])) {
                $mesureIds = array_filter(array_column($data['mesures'], 'id'));

                // Suppression des mesures non incluses
                foreach ($facture->getMesures() as $existingMesure) {
                    if (!in_array($existingMesure->getId(), $mesureIds)) {
                        $facture->removeMesure($existingMesure);
                    }
                }

                // Mise à jour ou ajout de mesures
                foreach ($data['mesures'] as $index => $mesureData) {
                    if (isset($mesureData['id'])) {
                        $mesure = $facture->getMesures()->filter(fn($m) => $m->getId() == $mesureData['id'])->first();
                    } else {
                        $mesure = new Mesure();
                        $facture->addMesure($mesure);
                    }

                    if ($mesure) {
                        $mesure->setTypeMesure($typeMesureRepository->find($mesureData['typeMesureId']));
                        $mesure->setMontant($mesureData['montant']);
                        $mesure->setRemise($mesureData['remise'] ?? 0);
                        
                        if (!isset($mesureData['id'])) {
                            $entityManager->persist($mesure);
                        }

                        // Gestion des fichiers uploadés
                        if (isset($uploadedFiles[$index])) {
                            $filePath = $this->getUploadDir(self::UPLOAD_PATH, true);
                            $filePrefix = 'document_' . uniqid();

                            foreach (['photoPagne', 'photoModele'] as $fileKey) {
                                if (!empty($uploadedFiles[$index][$fileKey])) {
                                    $uploadedFile = $uploadedFiles[$index][$fileKey];
                                    $fichier = $utils->sauvegardeFichier($filePath, $filePrefix, $uploadedFile, self::UPLOAD_PATH);
                                    if ($fichier) {
                                        $setter = 'set' . ucfirst($fileKey);
                                        $mesure->$setter($fichier);
                                    }
                                }
                            }
                        }

                        // Gestion des lignes de mesures
                        if (isset($mesureData['ligneMesures']) && is_array($mesureData['ligneMesures'])) {
                            $ligneIds = array_filter(array_column($mesureData['ligneMesures'], 'id'));

                            foreach ($mesure->getLigneMesures() as $existingLigne) {
                                if (!in_array($existingLigne->getId(), $ligneIds)) {
                                    $mesure->removeLigneMesure($existingLigne);
                                }
                            }

                            foreach ($mesureData['ligneMesures'] as $ligneData) {
                                if (isset($ligneData['id'])) {
                                    $ligneMesure = $mesure->getLigneMesures()->filter(fn($l) => $l->getId() == $ligneData['id'])->first();
                                } else {
                                    $ligneMesure = new LigneMesure();
                                    $mesure->addLigneMesure($ligneMesure);
                                }

                                if ($ligneMesure) {
                                    $ligneMesure->setCategorieMesure($categorieMesureRepository->find($ligneData['categorieId']));
                                    $ligneMesure->setTaille($ligneData['taille']);
                                    if (!isset($ligneData['id'])) {
                                        $entityManager->persist($ligneMesure);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $errorResponse = $this->errorResponse($facture);
            if ($errorResponse !== null) {
                return $errorResponse;
            }

            $factureRepository->add($facture, true);
            return $this->responseData($facture, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la mise à jour de la facture: " . $exception->getMessage());
            $this->setStatusCode(500);
            return $this->response('[]');
        }
    }

    /**
     * Supprime une facture
     */
    #[Route('/delete/{id}', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/facture/delete/{id}",
        summary: "Supprimer une facture",
        description: "Permet de supprimer définitivement une facture par son identifiant, incluant toutes ses mesures, lignes de mesures et paiements associés. Nécessite un abonnement actif.",
        tags: ['facture']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: "Identifiant unique de la facture à supprimer",
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: "Facture supprimée avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "message", type: "string", example: "Operation effectuées avec succès"),
                new OA\Property(property: "deleted", type: "boolean", example: true)
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 404, description: "Facture non trouvée")]
    #[OA\Response(response: 500, description: "Erreur lors de la suppression")]
    public function delete(Request $request, Facture $facture, FactureRepository $villeRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            if ($facture != null) {
                $villeRepository->remove($facture, true);
                $this->setMessage("Operation effectuées avec succès");
                $response = $this->response($facture);
            } else {
                $this->setMessage("Cette ressource est inexistante");
                $this->setStatusCode(404);
                $response = $this->response([]);
            }
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la suppression de la facture");
            $response = $this->response([]);
        }
        return $response;
    }

    /**
     * Supprime plusieurs factures en masse
     */
    #[Route('/delete/all/items', methods: ['DELETE'])]
    #[OA\Delete(
        path: "/api/facture/delete/all/items",
        summary: "Supprimer plusieurs factures",
        description: "Permet de supprimer plusieurs factures en une seule opération en fournissant un tableau d'identifiants. Toutes les mesures et paiements associés seront également supprimés. Nécessite un abonnement actif.",
        tags: ['facture']
    )]
    #[OA\RequestBody(
        required: true,
        description: "Tableau des identifiants des factures à supprimer",
        content: new OA\JsonContent(
            type: "object",
            required: ["ids"],
            properties: [
                new OA\Property(
                    property: 'ids',
                    type: 'array',
                    description: "Liste des identifiants des factures à supprimer",
                    items: new OA\Items(type: 'integer', example: 1),
                    example: [1, 2, 3, 5, 8]
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Factures supprimées avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "message", type: "string", example: "Operation effectuées avec succès"),
                new OA\Property(property: "deletedCount", type: "integer", example: 5, description: "Nombre de factures supprimées")
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Données invalides")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 500, description: "Erreur lors de la suppression")]
    public function deleteAll(Request $request, FactureRepository $villeRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $data = json_decode($request->getContent(), true);

            foreach ($data['ids'] as $id) {
                $facture = $villeRepository->find($id);

                if ($facture != null) {
                    $villeRepository->remove($facture);
                }
            }
            $this->setMessage("Operation effectuées avec succès");
            $response = $this->response([]);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la suppression des factures");
            $response = $this->response([]);
        }
        return $response;
    }
}
