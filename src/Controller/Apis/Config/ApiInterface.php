<?php

namespace App\Controller\Apis\Config;

use App\Controller\FileTrait;
use App\Trait\DatabaseEnvironmentTrait;
use App\Entity\Boutique;
use App\Entity\Entreprise;
use App\Repository\BoutiqueRepository;
use App\Repository\SettingRepository;
use App\Repository\SurccursaleRepository;
use App\Repository\UserRepository;
use App\Service\EntityManagerProvider;
use App\Service\Menu;
use App\Service\PaginationService;
use App\Service\SendMailService;
use App\Service\StatistiquesService;
use App\Service\SubscriptionChecker;
use App\Service\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ApiInterface extends AbstractController
{
    use FileTrait;
    use DatabaseEnvironmentTrait;


    protected const UPLOAD_PATH = 'media_deeps';
    protected $security;
    protected $validator;
    protected $slugger;
    protected $userInterface;
    protected $subscriptionChecker;
    protected  $hasher;
    protected  $userRepository;
    protected  $boutiqueRepository;
    protected  $succursaleRe;
    protected $settingRepository;
    protected  $utils;
    //protected  $utils;
    protected $em;

    protected $client;

    protected $serializer;

    protected $sendMail;
    protected $superAdmin;

    public function __construct(
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        protected SendMailService $sendMailService,
        SubscriptionChecker $subscriptionChecker,
        Utils $utils,
        UserPasswordHasherInterface $hasher,
        BoutiqueRepository $boutiqueRepository,
        SurccursaleRepository $surccursaleRepository,
        SettingRepository $settingRepository,
        HttpClientInterface $client,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        UserRepository $userRepository,
        protected StatistiquesService $statistiquesService,
        protected PaginationService $paginationService,
        EntityManagerProvider $entityManagerProvider,
        #[Autowire(param: 'SEND_MAIL')] string $sendMail,
        #[Autowire(param: 'SUPER_ADMIN')] string $superAdmin
    ) {

        $this->client = $client;
        $this->em = $em;
        $this->serializer = $serializer;
        $this->validator = $validator;
        $this->userRepository = $userRepository;
        $this->utils = $utils;
        $this->hasher = $hasher;
        $this->subscriptionChecker = $subscriptionChecker;
        $this->slugger = $slugger;
        $this->boutiqueRepository = $boutiqueRepository;
        $this->succursaleRe = $surccursaleRepository;
        $this->settingRepository = $settingRepository;
        $this->sendMail = $sendMail;
        $this->superAdmin = $superAdmin;

        // Injecter l'EntityManagerProvider dans le trait
        $this->setEntityManagerProvider($entityManagerProvider);
    }

    public function allParametres($type)
    {
        $entreprise = $this->getUser()->getEntreprise();
        $setting = $this->settingRepository->findOneByInEnvironment(
            ['entreprise' => $entreprise],
            ['id' => 'DESC']
        );

        if (!$setting) {
            $this->errorResponse("Aucun paramètre trouvé");
            return;
        }
        $limits = [
            'user' => $setting->getNombreUser(),
            'boutique' => $setting->getNombreBoutique(),
            'succursale' => $setting->getNombreSuccursale()
        ];

        if (!array_key_exists($type, $limits)) {
            $this->errorResponse("Type invalide");
            return;
        }

        $counts = [
            'user' => $this->userRepository->countActiveByEntreprise($entreprise),
            'boutique' => $this->boutiqueRepository->countActiveByEntreprise($entreprise),
            'succursale' => $this->succursaleRe->countActiveByEntreprise($entreprise)
        ];

        if ($counts[$type] >= $limits[$type]) {
            $this->errorResponse("Limite atteinte");
        }
    }


    /**
     * @var integer HTTP status code - 200 (OK) by default
     */
    protected $statusCode = 200;
    protected $message = "Operation effectuée avec succes";

    /**
     * Gets the value of statusCode.
     *
     * @return integer
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Sets the value of statusCode.
     *
     * @param integer $statusCode the status code
     *
     * @return self
     */
    protected function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    public function getMessage()
    {
        return $this->message;
    }
    protected function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    public function response($data, $headers = [])
    {
        // On spécifie qu'on utilise l'encodeur JSON
        $encoders = [new JsonEncoder()];

        // On instancie le "normaliseur" pour convertir la collection en tableau
        $normalizers = [new ObjectNormalizer()];

        // On instancie le convertisseur
        $serializer = new Serializer($normalizers, $encoders);


        if ($data == null) {
            $arrayData = [
                'data' => '[]',
                'message' => $this->getMessage(),
                'status' => $this->getStatusCode()
            ];
            $response = $this->json([
                'data' => $data,
                'message' => $this->getMessage(),
                'status' => $this->getStatusCode(),
                'errors' => []

            ], 200);
            $response->headers->set('Access-Control-Allow-Origin', '*');
        } else {
            $arrayData = [
                'data' => $data,
                'message' => $this->getMessage(),
                'status' => $this->getStatusCode()
            ];
            $jsonContent = $serializer->serialize($arrayData, 'json', [
                'circular_reference_handler' => function ($object) {
                    return  $object->getId();
                },

            ]);
            // On instancie la réponse
            $response = new Response($jsonContent, $this->getStatusCode());
            //$response->headers->set('Content-Type', 'application/json');
            $response->headers->set('Access-Control-Allow-Origin', '*');
        }

        return $response;
        //return new JsonResponse($response, $this->getStatusCode(), $headers);
    }
    public function responseTrue($data, $headers = [])
    {

        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);


        if ($data == null) {
            $arrayData = [
                'data' => '[]',
                'message' => $this->getMessage(),
                'status' => $this->getStatusCode()
            ];
            $response = $this->json([
                'data' => $data,
                'message' => $this->getMessage(),
                'status' => $this->getStatusCode(),
                'errors' => []

            ], 200);
            $response->headers->set('Access-Control-Allow-Origin', '*');
        } else {
            $arrayData = [
                'data' => $data,
                'message' => $this->getMessage(),
                'status' => $this->getStatusCode(),
                'errors' => []
            ];
            $jsonContent = $serializer->serialize($arrayData, 'json', [
                'circular_reference_handler' => function ($object) {
                    return  $object->getId();
                },

            ]);
            // On instancie la réponse
            $response = new Response($jsonContent);
            //$response->headers->set('Content-Type', 'application/json');
            $response->headers->set('Access-Control-Allow-Origin', '*');
        }
        return $response;
    }



    public function responseAdd($data, $headers = [])
    {
        return $this->json([
            'data' => $data,
            'message' => $this->getMessage(),
            'status' => $this->getStatusCode()

        ], 200);
    }

    /*  public function responseData($data = [], $group = null, $headers = [])
    {
        try {

            $finalHeaders = empty($headers) ? ['Content-Type' => 'application/json'] : $headers;
            if ($data) {
                $context = [AbstractNormalizer::GROUPS => $group];
                $json = $this->serializer->serialize($data, 'json', $context);
                $response = new JsonResponse([
                    'code' => 200,
                    'message' => $this->getMessage(),
                    'data' => json_decode($json),
                    'errors' => []
                ], 200, $finalHeaders);
                $response->headers->set('Access-Control-Allow-Origin', '*');
            } else {
                $response = new JsonResponse([
                    'code' => 200,
                    'message' => $this->getMessage(),
                    'data' => [],
                    'errors' => []
                ], 200, $finalHeaders);
                $response->headers->set('Access-Control-Allow-Origin', '*');
            }
        } catch (\Exception $e) {
$this->setStatusCode(500);
            $response = new JsonResponse([
                'code' => 500,
                'message' => $e->getMessage(),
                'data' => []
            ], 500, $finalHeaders);
        }

        return $response;
    } */

    public function responseData(
        $data = [],
        $group = null,
        $headers = [],
        bool $paginate = false
    ): JsonResponse {
        try {
            $finalHeaders = empty($headers) ? ['Content-Type' => 'application/json'] : $headers;

            $context = [AbstractNormalizer::GROUPS => $group];

            // Cas paginé (KnpPaginator ou PaginationInterface)
            if ($paginate && $data instanceof PaginationInterface) {
                $items = $this->serializer->serialize($data->getItems(), 'json', $context);

                $response = new JsonResponse([
                    'code' => 200,
                    'message' => $this->getMessage(),
                    'data' => json_decode($items),
                    'pagination' => [
                        'currentPage' => $data->getCurrentPageNumber(),
                        'totalItems'  => $data->getTotalItemCount(),
                        'itemsPerPage' => $data->getItemNumberPerPage(),
                        'totalPages'  => ceil($data->getTotalItemCount() / $data->getItemNumberPerPage())
                    ],
                    'errors' => []
                ], 200, $finalHeaders);
            } else {
                // Cas normal (array ou collection simple)
                $json = $this->serializer->serialize($data, 'json', $context);

                $response = new JsonResponse([
                    'code' => 200,
                    'message' => $this->getMessage(),
                    'data' => json_decode($json),
                    'errors' => []
                ], 200, $finalHeaders);
            }

            $response->headers->set('Access-Control-Allow-Origin', '*');
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            $response = new JsonResponse([
                'code' => 500,
                'message' => $e->getMessage(),
                'data' => []
            ], 500, $finalHeaders);
        }

        return $response;
    }

    public function responseDataWith_($data = [], $group = null, $headers = [])
    {
        try {

            $finalHeaders = empty($headers) ? ['Content-Type' => 'application/json'] : $headers;
            if ($data) {
                $context = [AbstractNormalizer::GROUPS => $group];
                $json = $this->serializer->serialize($data['data'], 'json', $context);
                $response = new JsonResponse([
                    'code' => 200,
                    'message' => $this->getMessage(),
                    'data' => json_decode($json),
                    'errors' => []
                ], 200, $finalHeaders);
                $response->headers->set('Access-Control-Allow-Origin', '*');
            } else {
                $response = new JsonResponse([
                    'code' => 200,
                    'message' => $this->getMessage(),
                    'data' => [],
                    'errors' => []
                ], 200, $finalHeaders);
                $response->headers->set('Access-Control-Allow-Origin', '*');
            }
        } catch (\Exception $e) {
            $this->setStatusCode(500);
            $response = new JsonResponse([
                'code' => 500,
                'message' => $e->getMessage(),
                'data' => []
            ], 500, $finalHeaders);
        }

        return $response;
    }

    public function errorResponse($DTO, string $customMessage = ''): ?JsonResponse
    {
        $errors = $this->validator->validate($DTO);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            //array_push($arerrorMessagesray, 4)

            $response = [
                'code' => 400,
                'message' => 'Validation failed',
                'errors' => $errorMessages
            ];

            return new JsonResponse($response, 400);
        } elseif ($customMessage != '') {
            $errorMessages[] = $customMessage;
            $response = [
                'code' => 400,
                'message' => 'Validation failed',
                'errors' => $errorMessages
            ];

            return new JsonResponse($response, 400);
        }

        return null; // Pas d'erreurs, donc pas de réponse d'erreur
    }
    public function errorResponseWithoutAbonnement(string $customMessage = ''): ?JsonResponse
    {
        $response = [
            'code' => 400,
            'message' => $customMessage,
            'errors' => $customMessage
        ];

        return new JsonResponse($response, 400);
    }

    public function setMessageErrorAbonnement()
    {
        if ($this->subscriptionChecker->getActiveSubscription($this->getUser()->getEntreprise()) == null) {
            return $this->errorResponseWithoutAbonnement('Abonnement requis pour cette fonctionnalité');
        }
    }

    /**
     * Create a custom error response without entity validation
     * This method should be used instead of errorResponse(null, message) to avoid
     * "Cannot validate values of type 'null' automatically" errors
     */
    protected function createCustomErrorResponse(string $message, int $statusCode = 400): JsonResponse
    {
        return new JsonResponse([
            'code' => $statusCode,
            'message' => $message,
            'errors' => [$message]
        ], $statusCode);
    }

    /**
     * Obtient un utilisateur géré par Doctrine pour éviter les problèmes de cascade persist
     */
    protected function getManagedUser(): ?\App\Entity\User
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return null;
        }

        // Utiliser la méthode générique
        return $this->getManagedEntity($currentUser);
    }

    /**
     * Configure correctement une entité avec TraitEntity (dates et utilisateur)
     */
    protected function configureTraitEntity($entity): void
    {
        if (!$entity) {
            return;
        }

        // Configurer l'utilisateur géré
        $managedUser = $this->getManagedUser();
        if ($managedUser && method_exists($entity, 'setCreatedBy')) {
            $entity->setCreatedBy($managedUser);
        }
        if ($managedUser && method_exists($entity, 'setUpdatedBy')) {
            $entity->setUpdatedBy($managedUser);
        }

        // Configurer les dates correctement
        if (method_exists($entity, 'setCreatedAtValue')) {
            $entity->setCreatedAtValue(); // Pas de paramètre - la méthode gère elle-même
        }
        if (method_exists($entity, 'setUpdatedAt')) {
            $entity->setUpdatedAt(); // Pas de paramètre - la méthode gère elle-même
        }

        // S'assurer que isActive est défini
        if (method_exists($entity, 'setIsActive')) {
            $entity->setIsActive(true);
        }
    }

    /**
     * Obtient une instance gérée de n'importe quelle entité
     * Résout le problème des entités détachées (Proxies) de manière générique
     */
    protected function getManagedEntity($entity)
    {
        if (!$entity) {
            return null;
        }

        // Si l'entité est déjà gérée, la retourner
        if ($this->em->contains($entity)) {
            return $entity;
        }

        // Si l'entité a un ID, la récupérer depuis la base
        if (method_exists($entity, 'getId') && $entity->getId()) {
            $entityClass = get_class($entity);
            // Enlever le préfixe Proxy si présent
            if (strpos($entityClass, 'Proxies\\__CG__\\') === 0) {
                $entityClass = substr($entityClass, strlen('Proxies\\__CG__\\'));
            }
            
            // Utiliser merge() au lieu de find() pour s'assurer que l'entité est gérée
            try {
                $managedEntity = $this->em->merge($entity);
                return $managedEntity;
            } catch (\Exception $e) {
                // Si merge échoue, essayer find()
                $managedEntity = $this->em->find($entityClass, $entity->getId());
                return $managedEntity;
            }
        }

        return null;
    }

    /**
     * Obtient une instance gérée de l'entreprise de l'utilisateur connecté
     * Résout le problème des entités détachées (Proxies)
     */
    protected function getManagedEntreprise(): ?\App\Entity\Entreprise
    {
        $currentUser = $this->getUser();
        if (!$currentUser || !$currentUser->getEntreprise()) {
            return null;
        }

        // Utiliser la méthode générique
        return $this->getManagedEntity($currentUser->getEntreprise());
    }

    /**
     * Configure une entité avec l'entreprise gérée de l'utilisateur connecté
     */
    protected function setManagedEntreprise($entity): void
    {
        if (!$entity || !method_exists($entity, 'setEntreprise')) {
            return;
        }

        $managedEntreprise = $this->getManagedEntreprise();
        if ($managedEntreprise) {
            $entity->setEntreprise($managedEntreprise);
        }
    }

    /**
     * Méthode spécialisée pour obtenir une entité gérée depuis findInEnvironment
     * Utilise l'EntityManager de l'environnement actuel pour s'assurer que l'entité est correctement gérée
     */
    protected function getManagedEntityFromEnvironment($entity)
    {
        if (!$entity) {
            return null;
        }

        // Si l'entité a un ID, la récupérer depuis la base avec l'EM de l'environnement
        if (method_exists($entity, 'getId') && $entity->getId()) {
            $entityClass = get_class($entity);
            // Enlever le préfixe Proxy si présent
            if (strpos($entityClass, 'Proxies\\__CG__\\') === 0) {
                $entityClass = substr($entityClass, strlen('Proxies\\__CG__\\'));
            }
            
            try {
                // Utiliser l'EntityManager de l'environnement pour récupérer l'entité
                $currentEM = $this->entityManagerProvider->getEntityManager();
                if ($currentEM->contains($entity)) {
                    return $entity;
                }
                $managedEntity = $currentEM->find($entityClass, $entity->getId());
                return $managedEntity ?: $entity;
            } catch (\Exception $e) {
                // Fallback vers l'EM principal
                return $this->getManagedEntity($entity);
            }
        }

        return $entity;
    }
}
