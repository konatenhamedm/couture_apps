<?php

namespace  App\Controller\Apis;

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
use App\Repository\TypeMesureRepository;
use App\Repository\UserRepository;
use App\Service\Utils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model as Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use Nelmio\ApiDocBundle\Attribute\Model as AttributeModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[Route('/api/facture')]
class ApiFactureController extends ApiInterface
{



    #[Route('/', methods: ['GET'])]
    /**
     * Retourne la liste des factures.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of an user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new AttributeModel(type: Facture::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'facture')]
    // #[Security(name: 'Bearer')]
    public function index(FactureRepository $factureRepository): Response
    {
        try {

            $factures = $this->paginationService->paginate($factureRepository->findAll());

            $response =  $this->responseData($factures, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setMessage("");
            $response = $this->response('[]');
        }

        // On envoie la réponse
        return $response;
    }

    #[Route('/entreprise', methods: ['GET'])]
    /**
     * Retourne la liste des factures d'une entreprise.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of an user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new AttributeModel(type: Facture::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'facture')]
    // #[Security(name: 'Bearer')]
    public function indexAll(FactureRepository $factureRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {

            $factures = $this->paginationService->paginate($factureRepository->findBy(
                ['entreprise' => $this->getUser()->getEntreprise()],
                ['id' => 'ASC']
            ));
            $response =  $this->responseData($factures, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setMessage("");
            $response = $this->response('[]');
        }

        // On envoie la réponse
        return $response;
    }




    #[Route('/get/one/{id}', methods: ['GET'])]
    /**
     * Affiche un(e) facture en offrant un identifiant.
     */
    #[OA\Response(
        response: 200,
        description: 'Affiche un(e) facture en offrant un identifiant',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new AttributeModel(type: Facture::class, groups: ['full']))
        )
    )]
    #[OA\Parameter(
        name: 'code',
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Tag(name: 'facture')]
    //#[Security(name: 'Bearer')]
    public function getOne(?Facture $facture)
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            if ($facture) {

                $response =  $this->responseData($facture, 'group1', ['Content-Type' => 'application/json']);
            } else {
                $this->setMessage('Cette ressource est inexistante');
                $this->setStatusCode(300);
                $response = $this->response($facture);
            }
        } catch (\Exception $exception) {
            $this->setMessage($exception->getMessage());
            $response = $this->response('[]');
        }


        return $response;
    }

    #[Route('/create',  methods: ['POST'])]
    /**
     * Crée une nouvelle facture avec les mesures associées.
     */
    #[OA\Post(
        summary: "Création d'une facture",
        description: "Permet de créer une nouvelle facture avec les informations du client, les mesures et les lignes de mesures associées.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "clientId", type: "string", description: "ID du client existant (optionnel)"),
                        new OA\Property(property: "nom", type: "string", description: "Nom du client (si nouveau client)"),
                        new OA\Property(property: "numero", type: "string", description: "Numéro du client (si nouveau client)"),
                        new OA\Property(property: "avance", type: "number", format: "float", description: "Montant de l'avance"),
                        new OA\Property(property: "remise", type: "number", format: "float", description: "Montant de la remise"),
                        new OA\Property(property: "montantTotal", type: "number", format: "float", description: "Montant total de la facture"),
                        new OA\Property(property: "resteArgent", type: "number", format: "float", description: "Reste à payer"),
                        new OA\Property(property: "dateRetrait", type: "string", format: "date-time", description: "Date de retrait prévue"),
                        new OA\Property(property: "signature", type: "string", description: "Signature du client"),
                        new OA\Property(
                            property: "mesures",
                            type: "array",
                            items: new OA\Items(
                                type: "object",
                                properties: [
                                    new OA\Property(property: "typeMesureId", type: "string", description: "ID du type de mesure"),
                                    new OA\Property(property: "montant", type: "number", format: "float", description: "Montant de la mesure"),
                                    new OA\Property(property: "remise", type: "number", format: "float", description: "Remise sur la mesure"),
                                    new OA\Property(
                                        property: "ligneMesures",
                                        type: "array",
                                        items: new OA\Items(
                                            type: "object",
                                            properties: [
                                                new OA\Property(property: "categorieId", type: "string", description: "ID de la catégorie de mesure"),
                                                new OA\Property(property: "taille", type: "string", description: "Taille de la mesure")
                                            ]
                                        ),
                                        description: "Lignes de mesures détaillées"
                                    ),
                                    new OA\Property(property: "photoPagne", type: "string", format: "binary", description: "Photo du pagne (optionnel)"),
                                    new OA\Property(property: "photoModele", type: "string", format: "binary", description: "Photo du modèle (optionnel)")
                                ]
                            ),
                            description: "Liste des mesures associées à la facture"
                        )
                    ],
                    required: ["montantTotal"]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Facture créée avec succès"),
            new OA\Response(response: 400, description: "Données invalides"),
            new OA\Response(response: 401, description: "Non autorisé")
        ]
    )]
    #[OA\Tag(name: 'facture')]
    #[Security(name: 'Bearer')]
    public function create(Request $request,UserRepository $userRepository, CaisseSuccursaleRepository $caisseSuccursaleRepository, Utils $utils, TypeMesureRepository $typeMesureRepository, ClientRepository $clientRepository, CategorieMesureRepository $categorieMesureRepository, FactureRepository $factureRepository, EntrepriseRepository $entrepriseRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        $names = 'document_' . '01';
        $filePrefix  = str_slug($names);
        $filePath = $this->getUploadDir(self::UPLOAD_PATH, true);
        $data = json_decode($request->getContent(), true);
        $facture = new Facture();
        $admin = $userRepository->getUserByCodeType($this->getUser()->getEntreprise());

        if ($request->get('clientId') == null) {
            $client = new Client();
            $client->setNom($data['nom']);
            $client->setEntreprise($this->getUser()->getEntreprise());
            $client->setNumero($data['numero']);
            $client->setSurccursale($this->getUser()->getSurccursale());
            $facture->setClient($client);
        } else {
            $facture->setClient($clientRepository->find($request->get('clientId')));
        }

        $facture->setDateDepot(new \DateTime());
        $facture->setAvance($request->get('avance'));
        $uploadedFichierSignature = $request->files->get('signature');

        if ($uploadedFichierSignature) {
           $fichierSignature = $utils->sauvegardeFichier($filePath, $filePrefix, $uploadedFichierSignature, self::UPLOAD_PATH);
            $facture->setSignature($fichierSignature);
        }
         

       /*  $facture->setSignature($request->get('signature')); */
        $facture->setRemise($request->get('remise'));
        $facture->setMontantTotal($request->get('montantTotal'));
        $facture->setResteArgent($request->get('resteArgent'));
        $facture->setDateRetrait($request->get('dateRetrait'));
        $facture->setCreatedBy($this->getUser());
        $facture->setUpdatedBy($this->getUser());
        $errorResponse = $this->errorResponse($facture);
        // On vérifie si l'entreprise existe
        $lignesMesure = $request->get('mesures');
        $uploadedFiles = $request->files->get('mesures');

        if (isset($lignesMesure) && is_array($lignesMesure)) {
            foreach ($lignesMesure as $index => $ligne) {
                $mesure = new Mesure();
                $mesure->setTypeMesure($typeMesureRepository->find($ligne['typeMesureId']));
                $mesure->setMontant($ligne['montant']);
                $mesure->setRemise($ligne['remise']);

                if (isset($uploadedFiles[$index])) {
                    $fileKeys = [
                        'photoPagne',
                        'photoModele',
                    ];

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

                $ligneMesures = $ligne['ligneMesures'] ?? [];

                if (isset($ligneMesures) && is_array($ligneMesures)) {
                    foreach ($ligneMesures as $ligneMesure) {
                        $ligneMesure = new LigneMesure();
                        $ligneMesure->setCategorieMesure($categorieMesureRepository->find($ligneMesure['categorieId']));
                        $ligneMesure->setTaille($ligneMesure['taille']);

                        $mesure->addLigneMesure($ligneMesure);
                    }
                }


                $facture->addMesure($mesure);
            }
        }

        $paiement = new PaiementFacture();
        $paiement->setMontant($facture->getMontantTotal());
        $paiement->setType('paiementFacture');
        $paiement->setReference($utils->generateReference('PMT'));
        $paiement->setCreatedBy($this->getUser());
        $paiement->setUpdatedBy($this->getUser());
        $paiement->setCreatedAtValue(new \DateTime());
        $facture->addPaiementFacture($paiement);

        if ($request->get('avance') != null) {

            $caisse =  $caisseSuccursaleRepository->findOneBy(['surccursale' => $this->getUser()->getSurccursale()]);

            $caisse->setMontant((int)$caisse->getMontant() + (int)$request->get('avance'));
            $caisse->setType('caisse_succursale');

                   $this->sendMailService->sendNotification([
                'entreprise' => $this->getUser()->getEntreprise(),
                "user" => $admin,
                "libelle" => sprintf(
                    "Bonjour %s,\n\n" .
                        "Nous vous informons qu'un nouveau paiement vient d'être enregistrée dans le surccursale **%s**.\n\n" .
                        "- Montant : %s\n" .
                        "- Effectuée par : %s\n" .
                        "- Date : %s\n\n" .
                        "Cordialement,\nVotre application de gestion.",
                    $admin->getLogin(),
                    $this->getUser()->getSurccursale(),
                    $data['montant'] ?? "Non spécifié",
                    $this->getUser()->getNom() && $this->getUser()->getPrenoms() ? $this->getUser()->getNom() . " " . $this->getUser()->getPrenoms() : $this->getUser()->getLogin(),
                    (new \DateTime())->format('d/m/Y H:i')
                ),
                "titre" => "Paiemnet facture - " . $this->getUser()->getSurccursale(),

            ]);


            $this->sendMailService->send(
                $this->sendMail,
                $this->superAdmin,
                "Paiement facture - " . $this->getUser()->getEntreprise(),
                "paiement_email",
                  [
                    "boutique_libelle" => $this->getUser()->getEntreprise(),
                    "montant" => $data['montant'],
                    "date" => (new \DateTime())->format('d/m/Y H:i'),
                ]
            );
        }

        if ($errorResponse !== null) {
            return $errorResponse; 
        } else {

            $factureRepository->add($facture, true);
        }

        return $this->responseData($facture, 'group1', ['Content-Type' => 'application/json']);
    }

    #[Route('/update/{id}', methods: ['PUT', 'POST'])]
    /**
     * Met à jour une facture existante.
     */
    #[OA\Post(
        summary: "Mise à jour d'une facture",
        description: "Permet de mettre à jour une facture existante avec les nouvelles informations.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "clientId", type: "string", description: "ID du client"),
                        new OA\Property(property: "avance", type: "number", format: "float", description: "Montant de l'avance"),
                        new OA\Property(property: "remise", type: "number", format: "float", description: "Montant de la remise"),
                        new OA\Property(property: "montantTotal", type: "number", format: "float", description: "Montant total de la facture"),
                        new OA\Property(property: "resteArgent", type: "number", format: "float", description: "Reste à payer"),
                        new OA\Property(property: "dateRetrait", type: "string", format: "date-time", description: "Date de retrait prévue"),
                        new OA\Property(
                            property: "mesures",
                            type: "array",
                            items: new OA\Items(
                                type: "object",
                                properties: [
                                    new OA\Property(property: "id", type: "string", description: "ID de la mesure (pour mise à jour)"),
                                    new OA\Property(property: "typeMesureId", type: "string", description: "ID du type de mesure"),
                                    new OA\Property(property: "montant", type: "number", format: "float", description: "Montant de la mesure"),
                                    new OA\Property(property: "remise", type: "number", format: "float", description: "Remise sur la mesure"),
                                    new OA\Property(
                                        property: "ligneMesures",
                                        type: "array",
                                        items: new OA\Items(
                                            type: "object",
                                            properties: [
                                                new OA\Property(property: "id", type: "string", description: "ID de la ligne de mesure (pour mise à jour)"),
                                                new OA\Property(property: "categorieId", type: "string", description: "ID de la catégorie de mesure"),
                                                new OA\Property(property: "taille", type: "string", description: "Taille de la mesure")
                                            ]
                                        ),
                                        description: "Lignes de mesures détaillées"
                                    ),
                                    new OA\Property(property: "photoPagne", type: "string", format: "binary", description: "Nouvelle photo du pagne (optionnel)"),
                                    new OA\Property(property: "photoModele", type: "string", format: "binary", description: "Nouvelle photo du modèle (optionnel)")
                                ]
                            ),
                            description: "Liste des mesures à mettre à jour ou ajouter"
                        )
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Facture mise à jour avec succès"),
            new OA\Response(response: 400, description: "Données invalides"),
            new OA\Response(response: 401, description: "Non autorisé"),
            new OA\Response(response: 404, description: "Facture non trouvée")
        ]
    )]
    #[OA\Tag(name: 'facture')]
    #[Security(name: 'Bearer')]
    public function update(
        Request $request,
        Facture $facture,
        FactureRepository $factureRepository,
        TypeMesureRepository $typeMesureRepository,
        ClientRepository $clientRepository,
        CategorieMesureRepository $categorieMesureRepository,
        Utils $utils
    ): Response {
        try {
            if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

            $data = json_decode($request->getContent(), true);
            $uploadedFiles = $request->files->get('mesures');

            if ($facture === null) {
                $this->setMessage("Cette ressource est inexistante");
                $this->setStatusCode(404);
                return $this->response('[]');
            }

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

                foreach ($facture->getMesures() as $existingMesure) {
                    if (!in_array($existingMesure->getId(), $mesureIds)) {
                        $facture->removeMesure($existingMesure);
                    }
                }

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
            $this->setMessage($exception->getMessage());
            $this->setStatusCode(500);
            return $this->response('[]');
        }
    }

    #[Route('/delete/{id}',  methods: ['DELETE'])]
    /**
     * permet de supprimer un(e) facture.
     */
    #[OA\Response(
        response: 200,
        description: 'permet de supprimer un(e) facture',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new AttributeModel(type: Facture::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'facture')]
    //#[Security(name: 'Bearer')]
    public function delete(Request $request, Facture $facture, FactureRepository $villeRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {

            if ($facture != null) {

                $villeRepository->remove($facture, true);

                // On retourne la confirmation
                $this->setMessage("Operation effectuées avec success");
                $response = $this->response($facture);
            } else {
                $this->setMessage("Cette ressource est inexistante");
                $this->setStatusCode(300);
                $response = $this->response('[]');
            }
        } catch (\Exception $exception) {
            $this->setMessage("");
            $response = $this->response('[]');
        }
        return $response;
    }

    #[Route('/delete/all',  methods: ['DELETE'])]
    /**
     * Permet de supprimer plusieurs facture.
     */
     #[OA\RequestBody(
        required: true,
        description: 'Tableau d’identifiants à supprimer',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'ids',
                    type: 'array',
                    items: new OA\Items(type: 'integer', example: 1)
                )
            ]
        )
    )]
    #[OA\Tag(name: 'facture')]
    #[Security(name: 'Bearer')]
    public function deleteAll(Request $request, FactureRepository $villeRepository): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $data = json_decode($request->getContent());

            foreach ($data['ids'] as $id) {
                $facture = $villeRepository->find($id);

                if ($facture != null) {
                    $villeRepository->remove($facture);
                }
            }
            $this->setMessage("Operation effectuées avec success");
            $response = $this->response('[]');
        } catch (\Exception $exception) {
            $this->setMessage("");
            $response = $this->response('[]');
        }
        return $response;
    }
}
