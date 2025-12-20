<?php

namespace App\Controller\Apis\Config;

use App\Controller\FileTrait;
use App\Trait\DatabaseEnvironmentTrait;
use App\Entity\Boutique;
use App\Entity\Client;
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
use App\Service\Validation\EntityValidationServiceInterface;
use App\Service\Environment\EnvironmentEntityManagerInterface;
use App\Service\Persistence\SafePersistenceHandlerInterface;
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
    protected EntityValidationServiceInterface $entityValidationService;
    protected EnvironmentEntityManagerInterface $environmentEntityManager;
    protected SafePersistenceHandlerInterface $safePersistenceHandler;

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
        EntityValidationServiceInterface $entityValidationService,
        EnvironmentEntityManagerInterface $environmentEntityManager,
        SafePersistenceHandlerInterface $safePersistenceHandler,
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
        $this->entityValidationService = $entityValidationService;
        $this->environmentEntityManager = $environmentEntityManager;
        $this->safePersistenceHandler = $safePersistenceHandler;

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

        // Pour éviter les problèmes de cascade persist, récupérer l'utilisateur directement par son ID
        // sans ses relations qui pourraient causer des problèmes
        try {
            $managedUser = $this->em->find('App\\Entity\\User', $currentUser->getId());
            return $managedUser;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Configure correctement une entité avec TraitEntity (dates et utilisateur)
     */
    protected function configureTraitEntity($entity): void
    {
        if (!$entity) {
            return;
        }

        // Configurer l'utilisateur géré - seulement si l'entité supporte TraitEntity
        if (method_exists($entity, 'setCreatedBy') && method_exists($entity, 'setUpdatedBy')) {
            $managedUser = $this->getManagedUser();
            if ($managedUser) {
                $entity->setCreatedBy($managedUser);
                $entity->setUpdatedBy($managedUser);
            }
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
            
            // Utiliser find() pour récupérer l'entité gérée
            $managedEntity = $this->em->find($entityClass, $entity->getId());
            return $managedEntity ?: $entity;
        }

        return $entity;
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
     * Méthode améliorée pour obtenir une entité gérée depuis findInEnvironment
     * Utilise le service EnvironmentEntityManager pour une gestion robuste
     */
    protected function getManagedEntityFromEnvironment($entity)
    {
        if (!$entity) {
            return null;
        }

        try {
            // Utiliser le service dédié pour gérer l'entité
            $managedEntity = $this->environmentEntityManager->ensureEntityIsManaged($entity);
            
            // Vérification supplémentaire : s'assurer que l'entité est bien gérée
            if (!$this->em->contains($managedEntity)) {
                // Si l'entité n'est toujours pas gérée, essayer de la récupérer à nouveau
                if (method_exists($managedEntity, 'getId') && $managedEntity->getId()) {
                    $entityClass = get_class($managedEntity);
                    // Enlever le préfixe Proxy si présent
                    if (strpos($entityClass, 'Proxies\\__CG__\\') === 0) {
                        $entityClass = substr($entityClass, strlen('Proxies\\__CG__\\'));
                    }
                    
                    $freshEntity = $this->em->find($entityClass, $managedEntity->getId());
                    if ($freshEntity) {
                        return $freshEntity;
                    }
                }
                
                // En dernier recours, si l'entité a un ID, la récupérer directement
                // Note: merge() n'existe plus dans Doctrine ORM 3.x
                if (method_exists($managedEntity, 'getId') && $managedEntity->getId()) {
                    $entityClass = get_class($managedEntity);
                    if (strpos($entityClass, 'Proxies\\__CG__\\') === 0) {
                        $entityClass = substr($entityClass, strlen('Proxies\\__CG__\\'));
                    }
                    return $this->em->getReference($entityClass, $managedEntity->getId());
                }
                
                return $managedEntity;
            }
            
            return $managedEntity;
        } catch (\Exception $e) {
            // Fallback vers l'ancienne méthode en cas d'erreur
            error_log("Erreur dans getManagedEntityFromEnvironment: " . $e->getMessage());
            return $this->getManagedEntity($entity);
        }
    }

    /**
     * Nouvelle méthode pour valider et corriger le contexte des entités
     */
    protected function validateAndFixEntityContext($entity)
    {
        if (!$entity) {
            return null;
        }

        // Vérifier si l'entité est dans le bon contexte
        if (!$this->environmentEntityManager->validateEntityContext($entity)) {
            // Si l'entité est détachée, la réattacher
            if ($this->environmentEntityManager->isEntityDetached($entity)) {
                return $this->environmentEntityManager->mergeDetachedEntity($entity);
            }
            
            // Sinon, s'assurer qu'elle est gérée
            return $this->environmentEntityManager->ensureEntityIsManaged($entity);
        }

        return $entity;
    }

    /**
     * Méthode pour résoudre les entités proxy
     */
    protected function resolveProxyEntity($entity)
    {
        if (!$entity) {
            return null;
        }

        return $this->environmentEntityManager->resolveProxyEntity($entity);
    }

    /**
     * Méthode pour nettoyer le cache des entités lors du changement d'environnement
     */
    protected function clearEntityCacheForEnvironmentSwitch(): void
    {
        $this->environmentEntityManager->clearEntityCache();
    }

    /**
     * Méthode améliorée pour forcer la gestion d'une entité
     * Utilise le service EnvironmentEntityManager pour une gestion robuste
     */
    protected function ensureEntityIsManaged($entity)
    {
        if (!$entity) {
            return null;
        }

        try {
            return $this->environmentEntityManager->ensureEntityIsManaged($entity);
        } catch (\Exception $e) {
            // Fallback vers l'ancienne méthode
            error_log("Erreur dans ensureEntityIsManaged: " . $e->getMessage());
            
            try {
                // Si l'entité n'est pas gérée, la récupérer à nouveau
                if (!$this->em->contains($entity) && method_exists($entity, 'getId') && $entity->getId()) {
                    $entityClass = get_class($entity);
                    // Enlever le préfixe Proxy si présent
                    if (strpos($entityClass, 'Proxies\\__CG__\\') === 0) {
                        $entityClass = substr($entityClass, strlen('Proxies\\__CG__\\'));
                    }
                    
                    $managedEntity = $this->em->find($entityClass, $entity->getId());
                    return $managedEntity ?: $entity;
                }
                
                return $entity;
            } catch (\Exception $fallbackException) {
                // En cas d'erreur, retourner l'entité originale
                error_log("Erreur dans le fallback ensureEntityIsManaged: " . $fallbackException->getMessage());
                return $entity;
            }
        }
    }

    /**
     * Méthode spécialisée pour gérer les entités Pays qui causent souvent des problèmes
     */
    protected function getManagedPays($pays)
    {
        if (!$pays) {
            return null;
        }

        try {
            // Forcer la récupération d'une nouvelle instance depuis la base
            if (method_exists($pays, 'getId') && $pays->getId()) {
                // Utiliser l'EntityManager de l'environnement si disponible
                try {
                    $currentEM = $this->entityManagerProvider->getEntityManager();
                    $managedPays = $currentEM->find('App\\Entity\\Pays', $pays->getId());
                    return $managedPays ?: $pays;
                } catch (\Exception $e) {
                    // Fallback vers l'EM principal
                    $managedPays = $this->em->find('App\\Entity\\Pays', $pays->getId());
                    return $managedPays ?: $pays;
                }
            }
            
            return $pays;
        } catch (\Exception $e) {
            return $pays;
        }
    }

    /**
     * S'assure que toutes les entités liées d'un client sont gérées par l'EntityManager
     * Ceci est nécessaire quand on n'utilise pas cascade persist
     */
    protected function ensureClientRelatedEntitiesAreManaged(Client $client): void
    {
        // Gérer l'entreprise
        if ($client->getEntreprise() && !$this->em->contains($client->getEntreprise())) {
            $managedEntreprise = $this->getManagedEntityFromEnvironment($client->getEntreprise());
            $client->setEntreprise($managedEntreprise);
        }
        
        // Gérer la boutique
        if ($client->getBoutique() && !$this->em->contains($client->getBoutique())) {
            $managedBoutique = $this->getManagedEntityFromEnvironment($client->getBoutique());
            $client->setBoutique($managedBoutique);
        }
        
        // Gérer la succursale
        if ($client->getSurccursale() && !$this->em->contains($client->getSurccursale())) {
            $managedSuccursale = $this->getManagedEntityFromEnvironment($client->getSurccursale());
            $client->setSurccursale($managedSuccursale);
        }
        
        // Gérer la photo si elle existe
        if ($client->getPhoto() && !$this->em->contains($client->getPhoto())) {
            // Pour les fichiers, on peut avoir besoin d'une gestion spéciale
            try {
                $managedPhoto = $this->getManagedEntityFromEnvironment($client->getPhoto());
                $client->setPhoto($managedPhoto);
            } catch (\Exception $e) {
                // Si la photo ne peut pas être gérée, on peut la laisser telle quelle
                // car elle a cascade persist
                error_log("Avertissement: impossible de gérer la photo: " . $e->getMessage());
            }
        }
    }

    /**
     * Valide une entité avant persistance avec le service de validation
     * 
     * @param object $entity L'entité à valider
     * @return JsonResponse|null Retourne une réponse d'erreur si la validation échoue, null sinon
     */
    protected function validateEntityForPersistence(object $entity): ?JsonResponse
    {
        $validationResult = $this->entityValidationService->validateForPersistence($entity);
        
        if (!$validationResult->isValid()) {
            $this->setStatusCode(400);
            $this->setMessage("Validation de l'entité échouée");
            
            return new JsonResponse([
                'code' => 400,
                'message' => 'Validation échouée',
                'errors' => $validationResult->getErrors(),
                'warnings' => $validationResult->getWarnings()
            ], 400);
        }
        
        // Log les avertissements s'il y en a
        if ($validationResult->hasWarnings()) {
            error_log("Avertissements de validation: " . $validationResult->getFormattedWarnings());
        }
        
        return null;
    }

    /**
     * Persiste une entité de manière sécurisée avec validation complète
     * 
     * @param object $entity L'entité à persister
     * @param bool $flush Effectuer un flush immédiatement
     * @return JsonResponse|null Retourne une réponse d'erreur si la persistance échoue, null sinon
     */
    protected function safePersistEntity(object $entity, bool $flush = false): ?JsonResponse
    {
        $result = $this->safePersistenceHandler->safePersist($entity, $flush);
        
        if (!$result->isSuccess()) {
            $this->setStatusCode(500);
            $this->setMessage($result->getMessage());
            
            return new JsonResponse([
                'code' => 500,
                'message' => $result->getMessage(),
                'errors' => $result->getErrors(),
                'warnings' => $result->getWarnings()
            ], 500);
        }
        
        // Log les avertissements s'il y en a
        if ($result->hasWarnings()) {
            error_log("Avertissements de persistance: " . implode(', ', $result->getWarnings()));
        }
        
        return null;
    }

    /**
     * Met à jour une entité de manière sécurisée
     * 
     * @param object $entity L'entité à mettre à jour
     * @param bool $flush Effectuer un flush immédiatement
     * @return JsonResponse|null Retourne une réponse d'erreur si la mise à jour échoue, null sinon
     */
    protected function safeUpdateEntity(object $entity, bool $flush = false): ?JsonResponse
    {
        $result = $this->safePersistenceHandler->safeUpdate($entity, $flush);
        
        if (!$result->isSuccess()) {
            $this->setStatusCode(500);
            $this->setMessage($result->getMessage());
            
            return new JsonResponse([
                'code' => 500,
                'message' => $result->getMessage(),
                'errors' => $result->getErrors(),
                'warnings' => $result->getWarnings()
            ], 500);
        }
        
        // Log les avertissements s'il y en a
        if ($result->hasWarnings()) {
            error_log("Avertissements de mise à jour: " . implode(', ', $result->getWarnings()));
        }
        
        return null;
    }

    /**
     * Supprime une entité de manière sécurisée
     * 
     * @param object $entity L'entité à supprimer
     * @param bool $flush Effectuer un flush immédiatement
     * @return JsonResponse|null Retourne une réponse d'erreur si la suppression échoue, null sinon
     */
    protected function safeRemoveEntity(object $entity, bool $flush = false): ?JsonResponse
    {
        $result = $this->safePersistenceHandler->safeRemove($entity, $flush);
        
        if (!$result->isSuccess()) {
            $this->setStatusCode(500);
            $this->setMessage($result->getMessage());
            
            return new JsonResponse([
                'code' => 500,
                'message' => $result->getMessage(),
                'errors' => $result->getErrors(),
                'warnings' => $result->getWarnings()
            ], 500);
        }
        
        // Log les avertissements s'il y en a
        if ($result->hasWarnings()) {
            error_log("Avertissements de suppression: " . implode(', ', $result->getWarnings()));
        }
        
        return null;
    }
}
