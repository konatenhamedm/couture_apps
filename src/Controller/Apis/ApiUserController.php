<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\DTO\UserDTO;
use App\Entity\Abonnement;
use App\Entity\Administrateur;
use App\Entity\Entreprise;
use App\Entity\LigneModule;
use App\Entity\ModuleAbonnement;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\User;
use App\Repository\AbonnementRepository;
use App\Repository\AdministrateurRepository;
use App\Repository\BoutiqueRepository;
use App\Repository\EntrepriseRepository;
use App\Repository\ModuleAbonnementRepository;
use App\Repository\PaysRepository;
use App\Repository\ResetPasswordTokenRepository;
use App\Repository\SettingRepository;
use App\Repository\SurccursaleRepository;
use App\Repository\TypeUserRepository;
use App\Repository\UserRepository;
use App\Service\AddCategorie;
use App\Service\JwtService;
use App\Service\ResetPasswordService;
use App\Service\SendMailService;
use App\Service\SubscriptionChecker;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Attribute\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * Contrôleur pour la gestion des utilisateurs
 * Gère l'inscription des entreprises, la création de membres, la mise à jour des profils
 */
#[Route('/api/user')]
#[OA\Tag(name: 'user', description: 'Gestion des utilisateurs : inscription entreprises avec abonnement gratuit, création membres, profils')]
class ApiUserController extends ApiInterface
{
    /**
     * Liste tous les utilisateurs du système
     */
    #[Route('/', methods: ['GET'])]
    #[OA\Get(
        path: "/api/user/",
        summary: "Lister tous les utilisateurs",
        description: "Retourne la liste paginée de tous les utilisateurs du système, toutes entreprises confondues.",
        tags: ['user']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des utilisateurs récupérée avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "code", type: "integer", example: 200),
                new OA\Property(
                    property: "data",
                    type: "array",
                    items: new OA\Items(
                        type: "object",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "login", type: "string", example: "admin@fashionci.com"),
                            new OA\Property(property: "nom", type: "string", example: "Kouassi", nullable: true),
                            new OA\Property(property: "prenoms", type: "string", example: "Jean", nullable: true),
                            new OA\Property(property: "setsetIsActive", type: "boolean", example: true),
                            new OA\Property(property: "roles", type: "array", items: new OA\Items(type: "string", example: "ROLE_ADMIN")),
                            new OA\Property(property: "type", type: "object", description: "Type d'utilisateur (SADM, ADMIN, GESTIONNAIRE, etc.)"),
                            new OA\Property(property: "entreprise", type: "object"),
                            new OA\Property(property: "boutique", type: "object", nullable: true),
                            new OA\Property(property: "succursale", type: "object", nullable: true),
                            new OA\Property(property: "createdAt", type: "string", format: "date-time")
                        ]
                    )
                )
            ]
        )
    )]
    #[OA\Response(response: 500, description: "Erreur serveur lors de la récupération")]
    public function index(UserRepository $userRepository): Response
    {
        try {
            $users = $this->paginationService->paginate($userRepository->findAll());

            $context = [AbstractNormalizer::GROUPS => 'group1'];
            $json = $this->serializer->serialize($users, 'json', $context);

            return new JsonResponse(['code' => 200, 'data' => json_decode($json)]);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setStatusCode(500);

            $this->setMessage("Erreur lors de la récupération des utilisateurs");
            $response = $this->response([]);
        }

        return $response;
    }


    /**
     * Liste les utilisateurs actifs de l'entreprise de l'utilisateur connecté
     */
    #[Route('/actif/entreprise', methods: ['GET'])]
    #[OA\Get(
        path: "/api/user/actif/entreprise",
        summary: "Lister les utilisateurs actifs de l'entreprise",
        description: "Retourne la liste paginée uniquement des utilisateurs actifs de l'entreprise de l'utilisateur connecté. Utile pour les formulaires d'assignation.",
        tags: ['user']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des utilisateurs actifs récupérée avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "code", type: "integer", example: 200),
                new OA\Property(
                    property: "data",
                    type: "array",
                    items: new OA\Items(type: "object")
                )
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 500, description: "Erreur lors de la récupération")]
    public function indexEntrepriseActive(UserRepository $userRepository): Response
    {
        try {
            $users = $this->paginationService->paginate($userRepository->findBy(
                ['entreprise' => $this->getUser()->getEntreprise(), 'setsetIsActive' => true],
                ['nom' => 'ASC']
            ));

            $context = [AbstractNormalizer::GROUPS => 'group1'];
            $json = $this->serializer->serialize($users, 'json', $context);

            return new JsonResponse(['code' => 200, 'data' => json_decode($json)]);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des utilisateurs actifs");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Liste tous les utilisateurs de l'entreprise de l'utilisateur connecté
     */
    #[Route('/entreprise', methods: ['GET'])]
    #[OA\Get(
        path: "/api/user/entreprise",
        summary: "Lister tous les utilisateurs de l'entreprise",
        description: "Retourne la liste paginée de tous les utilisateurs (actifs et inactifs) de l'entreprise de l'utilisateur connecté.",
        tags: ['user']
    )]
    #[OA\Response(
        response: 200,
        description: "Liste des utilisateurs de l'entreprise récupérée avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "code", type: "integer", example: 200),
                new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object"))
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 500, description: "Erreur lors de la récupération")]
    public function indexEntreprise(UserRepository $userRepository): Response
    {
        try {
            $users = $this->paginationService->paginate($userRepository->findBy(
                ['entreprise' => $this->getUser()->getEntreprise()],
                ['id' => 'DESC']
            ));

            $response =  $this->responseData($users, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la récupération des utilisateurs de l'entreprise");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Inscription complète : création entreprise + administrateur + abonnement gratuit
     */
    #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: "/api/user/create",
        summary: "Inscription complète (entreprise + admin)",
        description: "Permet l'inscription d'une nouvelle entreprise avec création automatique de : l'entreprise, l'utilisateur administrateur avec rôle ROLE_ADMIN, un abonnement gratuit (plan FREE), les paramètres par défaut, et envoi d'emails de bienvenue. Retourne un token JWT pour connexion immédiate.",
        tags: ['user']
    )]
    #[OA\RequestBody(
        required: true,
        description: "Données d'inscription de l'entreprise et de l'administrateur",
        content: new OA\JsonContent(
            type: "object",
            required: ["email", "password", "confirmPassword", "denominationEntreprise", "emailEntreprise", "numeroEntreprise", "pays"],
            properties: [
                new OA\Property(
                    property: "email",
                    type: "string",
                    format: "email",
                    example: "admin@fashionci.com",
                    description: "Email de connexion de l'administrateur (obligatoire, unique)"
                ),
                new OA\Property(
                    property: "password",
                    type: "string",
                    format: "password",
                    example: "SecurePass123!",
                    description: "Mot de passe (obligatoire, minimum 8 caractères)"
                ),
                new OA\Property(
                    property: "confirmPassword",
                    type: "string",
                    format: "password",
                    example: "SecurePass123!",
                    description: "Confirmation du mot de passe (obligatoire, doit correspondre à password)"
                ),
                new OA\Property(
                    property: "denominationEntreprise",
                    type: "string",
                    example: "Fashion Boutique CI",
                    description: "Nom de l'entreprise (obligatoire)"
                ),
                new OA\Property(
                    property: "emailEntreprise",
                    type: "string",
                    format: "email",
                    example: "contact@fashionci.com",
                    description: "Email de contact de l'entreprise (obligatoire)"
                ),
                new OA\Property(
                    property: "numeroEntreprise",
                    type: "string",
                    example: "+225 27 20 12 34 56",
                    description: "Numéro de téléphone de l'entreprise (obligatoire)"
                ),
                new OA\Property(
                    property: "pays",
                    type: "integer",
                    example: 1,
                    description: "ID du pays de l'entreprise (obligatoire)"
                )
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Inscription réussie, token JWT retourné",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "token", type: "string", example: "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...", description: "Token JWT pour authentification"),
                new OA\Property(
                    property: "user",
                    type: "object",
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 50),
                        new OA\Property(property: "login", type: "string", example: "admin@fashionci.com"),
                        new OA\Property(property: "nom", type: "string", example: ""),
                        new OA\Property(property: "prenoms", type: "string", example: ""),
                        new OA\Property(property: "roles", type: "array", items: new OA\Items(type: "string", example: "ROLE_ADMIN")),
                        new OA\Property(property: "is_active", type: "boolean", example: true),
                        new OA\Property(property: "pays", type: "integer", example: 1),
                        new OA\Property(property: "type", type: "object", description: "Type SADM (Super Admin)"),
                        new OA\Property(property: "settings", type: "object", description: "Paramètres de l'entreprise"),
                        new OA\Property(
                            property: "activeSubscriptions",
                            type: "object",
                            description: "Abonnement gratuit actif",
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 100),
                                new OA\Property(property: "etat", type: "string", example: "actif"),
                                new OA\Property(property: "type", type: "string", example: "gratuit"),
                                new OA\Property(property: "moduleAbonnement", type: "object", description: "Plan FREE")
                            ]
                        )
                    ]
                ),
                new OA\Property(property: "token_expires_in", type: "integer", example: 3600, description: "Durée de validité du token en secondes")
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Email déjà utilisé ou mots de passe non identiques")]
    #[OA\Response(response: 404, description: "Pays non trouvé")]
    #[OA\Response(response: 500, description: "Erreur lors de la création")]
    public function create(
        Request $request,
        SettingRepository $settingRepository,
        SubscriptionChecker $subscriptionChecker,
        JwtService $jwtService,
        AddCategorie $addCategorie,
        PaysRepository $paysRepository,
        AbonnementRepository $abonnementRepository,
        ModuleAbonnementRepository $moduleAbonnementRepository,
        TypeUserRepository $typeUserRepository,
        UserRepository $userRepository,
        EntrepriseRepository $entrepriseRepository,
        SendMailService $sendMailService
    ): Response {
        try {
            $data = json_decode($request->getContent(), true);

            // Vérification unicité email
            if ($userRepository->findOneBy(['login' => $data['email']])) {
                return $this->errorResponse(null, "Cet email existe déjà, veuillez utiliser un autre");
            }

            // Création de l'entreprise
            $entreprise = new Entreprise();
            $entreprise->setLibelle($data['denominationEntreprise']);
            $entreprise->setEmail($data['emailEntreprise']);
            $entreprise->setNumero($data['numeroEntreprise']);
            $entreprise->setIsActive(true);

            $pays = $paysRepository->find($data['pays']);
            if (!$pays) {
                return $this->errorResponse(null, "Pays non trouvé", 404);
            }
            $entreprise->setPays($pays);
            $entreprise->setCreatedAtValue(new \DateTime());
            $entreprise->setUpdatedAt(new \DateTime());

            // Création de l'utilisateur administrateur
            $user = new User();
            $user->setLogin($data['email']);
            $user->setEntreprise($entreprise);
            $user->setIsActive(true);
            $user->setPassword($this->hasher->hashPassword($user, $data['password']));
            $user->setRoles(['ROLE_ADMIN']);
            $user->setType($typeUserRepository->findOneBy(['code' => 'SADM']));
            /* $user->setCreatedAtValue(new \DateTime());
            $user->setUpdatedAt(new \DateTime()); */

            // Récupération du plan gratuit FREE
            $module = $moduleAbonnementRepository->findOneBy(['code' => 'FREE']);
            if (!$module) {
                return $this->errorResponse(null, "Plan d'abonnement FREE non trouvé", 500);
            }

            $nombreSms = 0;
            $nombreUser = 0;
            $nombresuccursale = 0;
            $nombreBoutique = 0;

            foreach ($module->getLigneModules() as $ligneModule) {
                if ($ligneModule->getLibelle() == "SMS") {
                    $nombreSms = $ligneModule->getQuantite();
                }
                if ($ligneModule->getLibelle() == "USER") {
                    $nombreUser = $ligneModule->getQuantite();
                }
                if ($ligneModule->getLibelle() == "SUCCURSALE") {
                    $nombresuccursale = $ligneModule->getQuantite();
                }
                if ($ligneModule->getLibelle() == "BOUTIQUE") {
                    $nombreBoutique = $ligneModule->getQuantite();
                }
            }

            // Création de l'abonnement gratuit
            $abonnement = new Abonnement();
            $abonnement->setEntreprise($entreprise);
            $abonnement->setCreatedAtValue(new \DateTime());
            $abonnement->setUpdatedAt(new \DateTime());
            $abonnement->setModuleAbonnement($module);
            $abonnement->setEtat("actif");
            $abonnement->setDateFin((new \DateTime())->modify('+' . $module->getDuree() . ' month'));
            $abonnement->setType('gratuit');

            // Validations
            if ($data['password'] !== $data['confirmPassword']) {
                return $this->errorResponse($user, "Les mots de passe ne sont pas identiques");
            }

            $errorResponse = $this->errorResponse($user);
            $errorResponse2 = $this->errorResponse($entreprise);
            $errorResponse3 = $this->errorResponse($abonnement);

            if ($errorResponse !== null || $errorResponse2 !== null || $errorResponse3 !== null) {
                if ($errorResponse !== null) {
                    return $errorResponse;
                } elseif ($errorResponse2 !== null) {
                    return $errorResponse2;
                } elseif ($errorResponse3 !== null) {
                    return $errorResponse3;
                }
            } else {
                // Enregistrement en base de données
                $entrepriseRepository->add($entreprise, true);
                $userRepository->add($user, true);
                $abonnementRepository->add($abonnement, true);

                // Initialisation des paramètres et catégories
                $addCategorie->initializeCategorieTypeMesureForEntreprise($entreprise, $user);
                $addCategorie->setting($entreprise, [
                    'succursale' => $nombresuccursale,
                    'user' => $nombreUser,
                    'sms' => $nombreSms,
                    'boutique' => $nombreBoutique,
                    'numero' => $module->getNumero()
                ]);

                // Envoi des notifications
                $sendMailService->sendNotification([
                    'libelle' => "Bienvenue dans notre application",
                    'titre' => "Bienvenue",
                    'entreprise' => $entreprise,
                    'user' => $user,
                    'userUpdate' => $user
                ]);

                // Email à l'admin système
                $this->sendMailService->send(
                    $this->sendMail,
                    $this->superAdmin,
                    "Nouvelle inscription - " . $entreprise->getLibelle(),
                    "nouvellesinscription",
                    [
                        "entreprise" => $entreprise->getLibelle(),
                        "abonnement" => $module->getCode(),
                        "date" => (new \DateTime())->format('d/m/Y H:i'),
                    ]
                );

                // Email de bienvenue au nouvel utilisateur
                $this->sendMailService->send(
                    $this->sendMail,
                    $data['email'],
                    "Bienvenue dans notre application",
                    "welcome_user",
                    [
                        "user" => [
                            "nom" => $user->getLogin(),
                        ],
                        "qr_code_url" => "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=https://monapp.com/download",
                        "url_appstore" => "https://apps.apple.com/app/id123456789",
                        "url_playstore" => "https://play.google.com/store/apps/details?id=com.monapp"
                    ]
                );

                // Génération du token JWT
                $token = $jwtService->generateToken([
                    'id' => $user->getId(),
                    'login' => $user->getLogin(),
                    'roles' => $user->getRoles()
                ]);

                $activeSubscriptions = $subscriptionChecker->getActiveSubscription($user->getEntreprise());

                $response = $this->responseData([
                    'token' => $token,
                    'user' => [
                        'id' => $user->getId(),
                        'login' => $user->getLogin(),
                        'nom' => $user->getNom() ?? '',
                        'prenoms' => $user->getPrenoms() ?? '',
                        'fcm_token' => $user->getFcmToken() ?? '',
                        'type' => $user->getType() ?? null,
                        'logo' => $user->getLogo() ?? null,
                        'roles' => $user->getRoles(),
                        'is_active' => $user->isActive(),
                        'pays' => $user->getEntreprise()->getPays()->getId(),
                        'boutique' => $user->getBoutique() ? $user->getBoutique()->getId() : null,
                        'succursale' => $user->getSurccursale() ? $user->getSurccursale()->getId() : null,
                        'settings' => $settingRepository->findOneBy(['entreprise' => $user->getEntreprise()]),
                        'activeSubscriptions' => $activeSubscriptions
                    ],
                    'token_expires_in' => $jwtService->getTtl()
                ], 'group1', ['Content-Type' => 'application/json']);
            }
        } catch (Exception $th) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de l'inscription");
            $response = $this->response([]);
        }

        return $response;
    }

    /**
     * Création d'un utilisateur membre dans une entreprise existante
     */
    #[Route('/create/membre', methods: ['POST'])]
    #[OA\Post(
        path: "/api/user/create/membre",
        summary: "Créer un membre dans l'entreprise",
        description: "Permet de créer un nouvel utilisateur membre (employé) dans l'entreprise de l'utilisateur connecté. Le membre peut être assigné à une succursale et/ou une boutique spécifique. Nécessite un abonnement actif.",
        tags: ['user']
    )]
    #[OA\RequestBody(
        required: true,
        description: "Données du membre à créer",
        content: new OA\JsonContent(
            type: "object",
            required: ["nom", "prenoms", "email", "password", "confirmPassword", "type"],
            properties: [
                new OA\Property(
                    property: "nom",
                    type: "string",
                    example: "Traoré",
                    description: "Nom de famille du membre (obligatoire)"
                ),
                new OA\Property(
                    property: "prenoms",
                    type: "string",
                    example: "Aminata",
                    description: "Prénom(s) du membre (obligatoire)"
                ),
                new OA\Property(
                    property: "login",
                    type: "string",
                    format: "login",
                    example: "aminata.traore@fashionci.com",
                    description: "Email de connexion du membre (obligatoire, unique)"
                ),
                new OA\Property(
                    property: "password",
                    type: "string",
                    format: "password",
                    example: "SecurePass123!",
                    description: "Mot de passe (obligatoire)"
                ),
                new OA\Property(
                    property: "confirmPassword",
                    type: "string",
                    format: "password",
                    example: "SecurePass123!",
                    description: "Confirmation du mot de passe (obligatoire)"
                ),
                new OA\Property(
                    property: "succursale",
                    type: "integer",
                    nullable: true,
                    example: 1,
                    description: "ID de la succursale d'affectation (optionnel)"
                ),
                new OA\Property(
                    property: "boutique",
                    type: "integer",
                    nullable: true,
                    example: 2,
                    description: "ID de la boutique d'affectation (optionnel)"
                ),
                new OA\Property(
                    property: "type",
                    type: "integer",
                    example: 3,
                    description: "ID du type d'utilisateur (obligatoire: GESTIONNAIRE, VENDEUR, etc.)"
                )
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Membre créé avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 75),
                new OA\Property(property: "nom", type: "string", example: "Traoré"),
                new OA\Property(property: "prenoms", type: "string", example: "Aminata"),
                new OA\Property(property: "login", type: "string", example: "aminata.traore@fashionci.com"),
                new OA\Property(property: "type", type: "object"),
                new OA\Property(property: "succursale", type: "object", nullable: true),
                new OA\Property(property: "boutique", type: "object", nullable: true)
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Email déjà utilisé ou mots de passe non identiques")]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 403, description: "Abonnement requis pour cette fonctionnalité")]
    #[OA\Response(response: 404, description: "Succursale, boutique ou type utilisateur non trouvé")]
    public function createMembre(
        Request $request,
        BoutiqueRepository $boutiqueRepository,
        SubscriptionChecker $subscriptionChecker,
        SurccursaleRepository $surccursaleRepository,
        TypeUserRepository $typeUserRepository,
        UserRepository $userRepository,
        EntrepriseRepository $entrepriseRepository,
        SendMailService $sendMailService
    ): Response {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }

        try {
            $data = json_decode($request->getContent(), true);

            // Vérification unicité email
            if ($userRepository->findOneBy(['login' => $data['login']])) {
                return $this->errorResponse(null, "Cet email existe déjà, veuillez utiliser un autre");
            }

            // Création de l'utilisateur membre
            $user = new User();
            $user->setNom($data['nom']);
            $user->setPrenoms($data['prenoms']);
            $user->setLogin($data['login']);


            // Affectation à une succursale (optionnel)
            if (isset($data['succursale']) && $data['succursale'] != null) {
                $succursale = $surccursaleRepository->find($data['succursale']);
                if ($succursale) {
                    $user->setSurccursale($succursale);
                }
            }

            // Affectation à une boutique (optionnel)
            if (isset($data['boutique']) && $data['boutique'] != null) {
                $boutique = $boutiqueRepository->find($data['boutique']);
                if ($boutique) {
                    $user->setBoutique($boutique);
                }
            }

            $user->setIsActive($subscriptionChecker->getSettingByUser($this->getUser()->getEntreprise(), "user"));
            $user->setPassword($this->hasher->hashPassword($user, $data['password']));
            $user->setRoles(['ROLE_MEMBRE']);

            $typeUser = $typeUserRepository->find($data['type']);
            if (!$typeUser) {
                return $this->errorResponse(null, "Type d'utilisateur non trouvé", 404);
            }
            $user->setType($typeUser);
            $user->setEntreprise($this->getUser()->getEntreprise());
            //$user->setCreatedAtValue(new \DateTime());
            //$user->setUpdatedAt(new \DateTime());

            // Validation mot de passe
            /* if ($data['password'] !== $data['confirmPassword']) {
                return $this->errorResponse($user, "Les mots de passe ne sont pas identiques");
            } */

            $errorResponse = $this->errorResponse($user);
            if ($errorResponse !== null) {
                return $errorResponse;
            } else {
                $userRepository->add($user, true);

                // Notification de bienvenue
                $sendMailService->sendNotification([
                    'libelle' => "Bienvenue dans notre application",
                    'titre' => "Bienvenue",
                    'entreprise' => $this->getUser()->getEntreprise(),
                    'user' => $user,
                    'userUpdate' => $user
                ]);
            }

            $response = $this->responseData($user, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Throwable $th) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la création du membre");
            $response = $this->response($th->getMessage());
        }

        return $response;
    }

    #[Route('/update/membre/{id}', methods: ['PUT','POST'])]
    #[OA\Put(
        path: "/api/user/update/membre/{id}",
        summary: "Mettre à jour un membre de l'entreprise",
        description: "Permet de modifier les informations d’un utilisateur membre (employé) existant. Nécessite un abonnement actif.",
        tags: ['user']
    )]
    #[OA\Parameter(
        name: "id",
        in: "path",
        required: true,
        description: "ID du membre à mettre à jour",
        schema: new OA\Schema(type: "integer", example: 45)
    )]
    #[OA\RequestBody(
        required: true,
        description: "Données du membre à mettre à jour",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "nom", type: "string", example: "Koné"),
                new OA\Property(property: "prenoms", type: "string", example: "Mariam"),
                new OA\Property(property: "login", type: "string", example: "mariam.kone@entreprise.ci"),
                new OA\Property(property: "succursale", type: "integer", example: 2, nullable: true),
                new OA\Property(property: "boutique", type: "integer", example: 5, nullable: true),
                new OA\Property(property: "type", type: "integer", example: 3)
            ]
        )
    )]
    #[OA\Response(response: 200, description: "Membre mis à jour avec succès")]
    #[OA\Response(response: 400, description: "Erreur de validation ou mots de passe non identiques")]
    #[OA\Response(response: 404, description: "Membre non trouvé")]
    public function updateMembre(

        Request $request,
        UserRepository $userRepository,
        SurccursaleRepository $surccursaleRepository,
        BoutiqueRepository $boutiqueRepository,
        TypeUserRepository $typeUserRepository,
        SubscriptionChecker $subscriptionChecker,
        SendMailService $sendMailService,
        User $user
    ): Response {
        // Vérifie abonnement actif
       /*  if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        } */

        try {
              $data = json_decode($request->getContent(), true);

            if (!$user) {
                return $this->errorResponse(null, "Membre non trouvé", 404);
            }

            // Mise à jour des champs
           $user->setNom($data['nom']);
            $user->setPrenoms($data['prenoms']);
            //if (  isset($data['email'])) $user->setLogin($data['email']);

            // Affectation succursale
            if ($data['succursale'] != null) {
                $succursale = $surccursaleRepository->find($data['succursale']);
               $user->setSurccursale($succursale);
            } else {
                $user->setSurccursale(null);
            }

            if ($data['boutique'] != null) {
                $boutique = $boutiqueRepository->find($data['boutique']);
              $user->setBoutique($boutique);
            } else {
                $user->setBoutique(null);
            }

            if ($data['type'] != null) {
                $typeUser = $typeUserRepository->find($data['type']);
                if (!$typeUser) {
                    return $this->errorResponse(null, "Type d'utilisateur non trouvé", 404);
                }
                $user->setType($typeUser);
            }

            //$user->setUpdatedAt(new \DateTime());

            $errorResponse = $this->errorResponse($user);
            if ($errorResponse !== null) {
                return $errorResponse;
            }

            $userRepository->add($user, true);

            // Notification optionnelle
            $sendMailService->sendNotification([
                'libelle' => "Mise à jour de votre profil utilisateur",
                'titre' => "Profil modifié",
                'entreprise' => $this->getUser()->getEntreprise(),
                'user' => $user,
                'userUpdate' => $this->getUser()
            ]);

            return $this->responseData($user, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Throwable $th) {
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la mise à jour du membre");
            return $this->response([]);
        }
    }


    /**
     * Met à jour le profil d'un utilisateur
     */
    #[Route('/update/profil/{id}', methods: ['PUT', 'POST'])]
    #[OA\Put(
        path: "/api/user/update/profil/{id}",
        summary: "Mettre à jour le profil utilisateur",
        description: "Permet de mettre à jour les informations personnelles d'un utilisateur (nom, prénoms, email). L'email doit rester unique.",
        tags: ['user']
    )]
    #[OA\Parameter(
        name: "id",
        in: "path",
        required: true,
        description: "Identifiant unique de l'utilisateur à mettre à jour",
        schema: new OA\Schema(type: "integer", example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        description: "Nouvelles informations du profil",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(
                    property: "nom",
                    type: "string",
                    example: "Kouassi",
                    description: "Nouveau nom de famille"
                ),
                new OA\Property(
                    property: "prenoms",
                    type: "string",
                    example: "Jean-Baptiste",
                    description: "Nouveaux prénoms"
                ),
                new OA\Property(
                    property: "email",
                    type: "string",
                    format: "email",
                    example: "jean.kouassi@fashionci.com",
                    description: "Nouvel email (doit être unique)"
                )
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Utilisateur mis à jour avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "code", type: "integer", example: 200),
                new OA\Property(property: "message", type: "string", example: "Utilisateur mis à jour avec succès"),
                new OA\Property(
                    property: "data",
                    type: "object",
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "nom", type: "string", example: "Kouassi"),
                        new OA\Property(property: "prenoms", type: "string", example: "Jean-Baptiste"),
                        new OA\Property(property: "login", type: "string", example: "jean.kouassi@fashionci.com"),
                        new OA\Property(property: "updatedAt", type: "string", format: "date-time")
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Email déjà utilisé par un autre utilisateur")]
    #[OA\Response(response: 404, description: "Utilisateur non trouvé")]
    #[OA\Response(response: 500, description: "Erreur lors de la mise à jour")]
    public function update(
        Request $request,
        UserRepository $userRepository,
        User $user
    ): Response {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$user) {
                return $this->json([
                    'code' => 404,
                    'message' => "Utilisateur non trouvé"
                ], 404);
            }

            // Mise à jour des champs
            if (isset($data['nom'])) {
                $user->setNom($data['nom']);
            }

            if (isset($data['prenoms'])) {
                $user->setPrenoms($data['prenoms']);
            }
            /* 
            if (isset($data['login'])) {
                // Vérification unicité email
                $existingUser = $userRepository->findOneBy(['login' => $data['email']]);
                if ($existingUser && $existingUser->getId() !== $user->getId()) {
                    return $this->json([
                        'code' => 400,
                        'message' => "Cet email est déjà utilisé par un autre utilisateur"
                    ], 400);
                }
                $user->setLogin($data['email']);
            } */

            //$user->setUpdatedAt(new \DateTime());
            $userRepository->add($user, true);

            $context = [AbstractNormalizer::GROUPS => 'group_pro'];
            $json = $this->serializer->serialize($user, 'json', $context);

            return new JsonResponse([
                'code' => 200,
                'message' => 'Utilisateur mis à jour avec succès',
                'data' => json_decode($json)
            ]);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            return $this->json([
                'code' => 500,
                'message' => "Une erreur est survenue lors de la mise à jour"
            ], 500);
        }
    }

    /**
     * Met à jour la photo de profil d'un utilisateur
     */
    #[Route('/profil/logo/{id}', methods: ['PUT', 'POST'])]
    #[OA\Post(
        path: "/api/user/profil/logo/{id}",
        summary: "Mettre à jour la photo de profil",
        description: "Permet de télécharger et mettre à jour la photo de profil (logo) d'un utilisateur. Le fichier est sauvegardé sur le serveur et le chemin est enregistré en base de données.",
        tags: ['user']
    )]
    #[OA\Parameter(
        name: "id",
        in: "path",
        required: true,
        description: "Identifiant unique de l'utilisateur",
        schema: new OA\Schema(type: "integer", example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        description: "Fichier image de la photo de profil",
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                type: "object",
                required: ["logo"],
                properties: [
                    new OA\Property(
                        property: "logo",
                        type: "string",
                        format: "binary",
                        description: "Fichier image (JPG, PNG, formats acceptés)"
                    )
                ]
            )
        )
    )]
    #[OA\Response(
        response: 200,
        description: "Photo de profil mise à jour avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "id", type: "integer", example: 1),
                new OA\Property(property: "nom", type: "string", example: "Kouassi"),
                new OA\Property(property: "prenoms", type: "string", example: "Jean"),
                new OA\Property(property: "logo", type: "string", example: "/uploads/users/document_01_abc123.jpg", description: "Chemin de la nouvelle photo")
            ]
        )
    )]
    #[OA\Response(response: 400, description: "Fichier invalide ou format non supporté")]
    #[OA\Response(response: 404, description: "Utilisateur non trouvé")]
    #[OA\Response(response: 500, description: "Erreur lors du téléchargement")]
    public function updateLogo(Request $request, User $user, UserRepository $userRepository): Response
    {
        try {
            if ($user === null) {
                $this->setMessage("Utilisateur non trouvé");
                $this->setStatusCode(404);
                return $this->response('[]');
            }

            $names = 'document_' . '01';
            $filePrefix = str_slug($names);
            $filePath = $this->getUploadDir(self::UPLOAD_PATH, true);

            $uploadedFile = $request->files->get('logo');

            if ($uploadedFile) {
                if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedFile, self::UPLOAD_PATH)) {
                    $user->setLogo($fichier);
                }
            }

            // Vérification des erreurs
            if ($errorResponse = $this->errorResponse($user)) {
                return $errorResponse;
            }

            //$user->setUpdatedAt(new \DateTime());
            $userRepository->add($user, true);

            return $this->responseData($user, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setStatusCode(500);
            $this->setStatusCode(500);
            $this->setMessage("Erreur lors de la mise à jour du logo");
            $response = $this->response([]);
        }
        return $response;
    }
}
