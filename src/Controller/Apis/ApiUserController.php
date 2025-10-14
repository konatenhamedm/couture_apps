<?php

namespace  App\Controller\Apis;

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
use App\Repository\EntrepriseRepository;
use App\Repository\ModuleAbonnementRepository;
use App\Repository\PaysRepository;
use App\Repository\ResetPasswordTokenRepository;
use App\Repository\SurccursaleRepository;
use App\Repository\TypeUserRepository;
use App\Repository\UserRepository;
use App\Service\AddCategorie;
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

#[Route('/api/user')]
class ApiUserController extends ApiInterface
{


   /*  #[Route('/qr-code', name: 'qr_code')]
    public function generateQrCode(): Response
    {
       
        
        return $this->render('emails/welcome_user.html.twig', [
           
        ]);
    } */

    #[Route('/', methods: ['GET'])]
    /**
     * Retourne la liste des users.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of an user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: User::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'user')]
    // #[Security(name: 'Bearer')]
    public function index(UserRepository $userRepository): Response
    {
        try {

            $users = $userRepository->findAll();

            $context = [AbstractNormalizer::GROUPS => 'group1'];
            $json = $this->serializer->serialize($users, 'json', $context);

            return new JsonResponse(['code' => 200, 'data' => json_decode($json)]);
        } catch (\Exception $exception) {
            $this->setMessage("");
            $response = $this->response('[]');
        }

        // On envoie la réponse
        return $response;
    }
    #[Route('/actif/entreprise', methods: ['GET'])]
    /**
     * Retourne la liste des users actifs d'une entreprise.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of an user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: User::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'user')]
    // #[Security(name: 'Bearer')]
    public function indexEntrepriseActive(UserRepository $userRepository): Response
    {
        try {

            $users = $userRepository->findBy(['entreprise' => $this->getUser()->getEntreprise(), 'isActive' => true], ['id' => 'ASC']);

            $context = [AbstractNormalizer::GROUPS => 'group1'];
            $json = $this->serializer->serialize($users, 'json', $context);

            return new JsonResponse(['code' => 200, 'data' => json_decode($json)]);
        } catch (\Exception $exception) {
            $this->setMessage("");
            $response = $this->response('[]');
        }

        // On envoie la réponse
        return $response;
    }

    #[Route('/entreprise', methods: ['GET'])]
    /**
     * Retourne la liste des users d'une entreprise.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of an user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: User::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'user')]
    // #[Security(name: 'Bearer')]
    public function indexEntreprise(UserRepository $userRepository): Response
    {
        try {

            $users = $userRepository->findBy(['entreprise' => $this->getUser()->getEntreprise()], ['id' => 'ASC']);

            $context = [AbstractNormalizer::GROUPS => 'group1'];
            $json = $this->serializer->serialize($users, 'json', $context);

            return new JsonResponse(['code' => 200, 'data' => json_decode($json)]);
        } catch (\Exception $exception) {
            $this->setMessage("");
            $response = $this->response('[]');
        }

        // On envoie la réponse
        return $response;
    }


    #[Route('/create',  methods: ['POST'])]
    /**
     * Permet de créer un(e) user.
     */
    #[OA\Post(
        summary: "Creation user memnbre",
        description: "Creation user memnbre",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [

                    new OA\Property(property: "email", type: "string"),
                    new OA\Property(property: "password", type: "string"),
                    new OA\Property(property: "confirmPassword", type: "string"),
                    new OA\Property(property: "denominationEntreprise", type: "string"),
                    new OA\Property(property: "emailEntreprise", type: "string"),
                    new OA\Property(property: "numeroEntreprise", type: "string"),
                    new OA\Property(property: "pays", type: "string"),

                ],
                type: "object"
            )
        ),
        responses: [
            new OA\Response(response: 401, description: "Invalid credentials")
        ]
    )]
    #[OA\Tag(name: 'user')]
    #[Security(name: 'Bearer')]
    public function create(Request $request, AddCategorie $addCategorie, PaysRepository $paysRepository, SurccursaleRepository $surccursaleRepository, AbonnementRepository $abonnementRepository, ModuleAbonnementRepository $moduleAbonnementRepository, TypeUserRepository $typeUserRepository, UserRepository $userRepository, EntrepriseRepository $entrepriseRepository, SendMailService $sendMailService): Response
    {

        try {

            /* $this->allParametres('user'); */

            $data = json_decode($request->getContent(), true);

            if ($userRepository->findOneBy(['login' => $data['email']])) {
                return $this->errorResponse(null, "Ce numéro existe déjà ,veuillez utiliser  un autre");
            }


            $entreprise = new Entreprise();
            $entreprise->setLibelle($data['denominationEntreprise']);
            $entreprise->setEmail($data['emailEntreprise']);
            $entreprise->setNumero($data['numeroEntreprise']);
            $entreprise->setPays($paysRepository->find($data['pays']));
            $entreprise->setCreatedAtValue(new \DateTime());
            $entreprise->setUpdatedAt(new \DateTime());

            $user = new User();
            $user->setLogin($data['email']);
            $user->setEntreprise($entreprise);
            $user->setIsActive(true);
            $user->setPassword($this->hasher->hashPassword($user,  $data['password']));
            $user->setRoles(['ROLE_ADMIN']);
            $user->setType($typeUserRepository->findOneBy(['code' => 'SADM']));


            /*   $entreprise->addUser($user); */
            $nombreSms = 0;
            $nombreUser = 0;
            $nombresuccursale = 0;
            $nombreBoutique = 0;


            $module = $moduleAbonnementRepository->findOneBy(['code' => 'FREE']);

            foreach ($module->getLigneModules() as  $ligneModule) {
                $nombreSms = $ligneModule->getLibelle() == "SMS" ? $ligneModule->getQuantite() : 0;
                $nombreUser = $ligneModule->getLibelle() == "USER" ? $ligneModule->getQuantite() : 0;
                $nombresuccursale = $ligneModule->getLibelle() == "SUCCURSALE" ? $ligneModule->getQuantite() : 0;
                $nombreBoutique = $ligneModule->getLibelle() == "BOUTIQUE" ? $ligneModule->getQuantite() : 0;
                /* $Nombresuccursale = $ligneModule->getNombreSuccursale(); */
            }

            $abonnement = new Abonnement();
            $abonnement->setEntreprise($entreprise);
            $abonnement->setCreatedAtValue(new \DateTime());
            $abonnement->setUpdatedAt(new \DateTime());
            $abonnement->setModuleAbonnement($module);
            $abonnement->setEtat("actif");
            $abonnement->setDateFin((new \DateTime())->modify('+' . $module->getDuree() . ' month'));
            $abonnement->setType('gratuit');

            $errorResponse = $data['password'] !== $data['confirmPassword'] ?  $this->errorResponse($user, "Les mots de passe ne sont pas identiques") :  $this->errorResponse($user);

            if ($errorResponse !== null) {
                return $errorResponse;
            } else {

                $entrepriseRepository->add($entreprise, true);
                $userRepository->add($user, true);
                $abonnementRepository->add($abonnement, true);
                // $entrepriseRepository->add($entreprise, true);
                $addCategorie->setParametreForEntreprise($user);
                $addCategorie->setting($entreprise, [
                    'succursale' => $nombresuccursale,
                    'user' => $nombreUser,
                    'sms' => $nombreSms,
                    'boutique' => $nombreBoutique,
                    'numero' => $module->getNumero()
                ]);

                $sendMailService->sendNotification([
                    'libelle' => "Bienvenue dans notre application",
                    'titre' => "Bienvenue",
                    'entreprise' => $entreprise,
                    'user' => $user,
                    'userUpdate' => $user
                ]);

                $this->sendMailService->send(
                    $this->sendMail,
                    $this->superAdmin,
                    "Nouvelle inscription - " . $entreprise->getLibelle(),
                    "nouvellesinscription",
                    [
                        "entreprise" =>   $entreprise->getLibelle(),
                        "abonnement" => $module->getCode(),
                        "date" => (new \DateTime())->format('d/m/Y H:i'),
                    ]
                );

                $this->sendMailService->send(
                    $this->sendMail,
                    $data['email'],
                    "Bienvenue dans notre application",
                    "welcome_user",
                    [
                        "user" => [
                            "nom" => $user->getNom() && $user->getPrenoms() ?  $user->getNom() . " " . $user->getPrenoms() : $user->getLogin(),
                        ],
                        "qr_code_url" => "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=https://monapp.com/download",
                        "url_appstore" => "https://apps.apple.com/app/id123456789",
                        "url_playstore" => "https://play.google.com/store/apps/details?id=com.monapp"
                    ]
                );
            }

            $response = $this->responseData($entreprise, 'group1', ['Content-Type' => 'application/json']);
        } catch (Exception $th) {

            // dd($th);
            $this->setMessage("");
            $response = $this->response('[]');
        }

        return $response;
    }


    #[Route('/create/membre',  methods: ['POST'])]
    /**
     * Permet de créer un(e) user.
     */
    #[OA\Post(
        summary: "Creation user memnbre",
        description: "Creation user memnbre",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [

                    new OA\Property(property: "numero", type: "string"),
                    new OA\Property(property: "password", type: "string"),
                    new OA\Property(property: "confirmPassword", type: "string"),
                    new OA\Property(property: "surccursale", type: "string"),
                    new OA\Property(property: "type", type: "string"),

                ],
                type: "object"
            )
        ),
        responses: [
            new OA\Response(response: 401, description: "Invalid credentials")
        ]
    )]
    #[OA\Tag(name: 'user')]
    #[Security(name: 'Bearer')]
    public function createMembre(Request $request,SubscriptionChecker $subscriptionChecker, SurccursaleRepository $surccursaleRepository, TypeUserRepository $typeUserRepository, UserRepository $userRepository, EntrepriseRepository $entrepriseRepository, SendMailService $sendMailService): Response
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }
        $this->allParametres('boutique');

        try {
            $data = json_decode($request->getContent(), true);

            if ($userRepository->findOneBy(['login' => $data['numero']])) {
                return $this->errorResponse(null, "Ce numéro existe déjà ,veuillez utiliser  un autre");
            }

            $user = new User();
            $user->setLogin($data['numero']);
            $user->setSurccursale($surccursaleRepository->find($data['surccursale']));
            $user->setIsActive($subscriptionChecker->getSettingByUser($this->getUser()->getEntreprise(), "user"));
            $user->setPassword($this->hasher->hashPassword($user,  $data['password']));
            $user->setRoles(['ROLE_MEMBRE']);
            $user->setType($typeUserRepository->find($data['type']));


            $sendMailService->sendNotification([
                'libelle' => "Bienvenue dans notre application",
                'titre' => "Bienvenue",
                'entreprise' => $this->getUser()->getEntreprise(),
                'user' => $user,
                'userUpdate' => $user
            ]);

            $errorResponse = $data['password'] !== $data['confirmPassword'] ?  $this->errorResponse($user, "Les mots de passe ne sont pas identiques") :  $this->errorResponse($user);
            if ($errorResponse !== null) {
                return $errorResponse;
            } else {

                $userRepository->add($user, true);
            }

            $response = $this->responseData($entrepriseRepository, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Throwable $th) {
            $this->setMessage("");
            $response = $this->response('[]');
        }

        return $response;
    }

    #[Route('/update/profil/{id}', methods: ['PUT'])]
    /**
     * Permet de mettre à jour les informations d'un utilisateur.
     */
    #[OA\Put(
        summary: "Mise à jour des informations utilisateur",
        description: "Permet de mettre à jour les informations d'un utilisateur",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "nom", type: "string"),
                    new OA\Property(property: "prenom", type: "string"),
                    new OA\Property(property: "email", type: "string"),
                    new OA\Property(property: "numero", type: "string"),
                ],
                type: "object"
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Utilisateur mis à jour avec succès"),
            new OA\Response(response: 404, description: "Utilisateur non trouvé")
        ]
    )]
    #[OA\Tag(name: 'user')]
    #[Security(name: 'Bearer')]
    public function update(
        Request $request,
        UserRepository $userRepository,
        User $user
    ): Response {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$user) {
                return $this->errorResponse(null, "Utilisateur non trouvé", 404);
            }

            // Mise à jour des champs
            if (isset($data['nom'])) {
                $user->setNom($data['nom']);
            }

            if (isset($data['prenom'])) {
                $user->setPrenoms($data['prenom']);
            }

            if (isset($data['numero'])) {

                $existingUser = $userRepository->findOneBy(['login' => $data['numero']]);
                if ($existingUser && $existingUser->getId() !== $user->getId()) {
                    return $this->errorResponse(null, "Ce numéro est déjà utilisé par un autre utilisateur");
                }
                $user->setLogin($data['numero']);
            }


            $userRepository->add($user, true);

            $context = [AbstractNormalizer::GROUPS => 'group_pro'];
            $json = $this->serializer->serialize($user, 'json', $context);

            return new JsonResponse([
                'code' => 200,
                'message' => 'Utilisateur mis à jour avec succès',
                'data' => json_decode($json)
            ]);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception->getMessage(), "Une erreur est survenue lors de la mise à jour");
        }
    }


    #[Route('/profil/logo/{id}', methods: ['PUT', 'POST'])]
    #[OA\Post(
        summary: "Modification user membre",
        description: "Permet de modifier un user MEMBRE.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    properties: [

                        new OA\Property(property: "logo", type: "string", format: "binary"),

                    ],
                    type: "object"
                )
            )

        ),
        responses: [
            new OA\Response(response: 401, description: "Invalid credentials")
        ]
    )]
    #[OA\Tag(name: 'user')]
    #[Security(name: 'Bearer')]
    public function updateLogo(Request $request, User $user, UserRepository $userRepository): Response
    {
        try {
            $data = json_decode($request->getContent());
            $names = 'document_' . '01';
            $filePrefix  = str_slug($names);
            $filePath = $this->getUploadDir(self::UPLOAD_PATH, true);

            $uploadedFile = $request->files->get('logo');


            if ($user !== null) {

                if ($uploadedFile) {
                    if ($fichier = $this->utils->sauvegardeFichier($filePath, $filePrefix, $uploadedFile, self::UPLOAD_PATH)) {
                        $user->setLogo($fichier);
                    }
                }

                // Vérification des erreurs
                if ($errorResponse = $this->errorResponse($user)) {
                    return $errorResponse;
                }

                $userRepository->add($user, true);

                // Retour de la réponse
                return $this->responseData($user, 'group1', ['Content-Type' => 'application/json']);
            } else {
                $this->setMessage("Cette ressource est inexsitante");
                $this->setStatusCode(300);
                $response = $this->response('[]');
            }
        } catch (\Exception $exception) {
            $this->setMessage("");
            $response = $this->response('[]');
        }
        return $response;
    }
}
