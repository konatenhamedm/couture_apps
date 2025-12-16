<?php

namespace App\Controller;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Setting;
use App\Repository\SettingRepository;
use App\Repository\UserRepository;
use App\Service\JwtService;
use App\Service\SubscriptionChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use OpenApi\Attributes as OA;


class AuthController extends ApiInterface
{


    #[Route('/api/login', methods: ['POST'])]
    #[OA\Post(
        summary: "Permet d'authentifier un utilisateur",
        description: "Permet d'authentifier un utilisateur",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "object",
                properties: [
                    new OA\Property(
                        property: "login",
                        type: "string",
                        default: "konatenhamed@gmail.com"
                    ),
                    new OA\Property(
                        property: "password",
                        type: "string",
                        default: "admin93K"
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 401, description: "Invalid credentials"),
            new OA\Response(response: 200, description: "Success")
        ]
    )]
    #[OA\Tag(name: 'auth')]
    public function login(
        Request $request,
        JwtService $jwtService,
        UserPasswordHasherInterface $hasher,
        UserRepository $userRepo,
        SubscriptionChecker $subscriptionChecker,
        SettingRepository $settingRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $user = $userRepo->findOneByInEnvironment(['login' => $data['login']]);

        if(!$user){
            return $this->json(['error' => 'Ce utilisateur n\'existe pas'], Response::HTTP_UNAUTHORIZED);
        }else{
            if(!$hasher->isPasswordValid($user, $data['password'])){
                return $this->json(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
            }elseif(!$user->isActive()){
                return $this->json(['error' => 'Ce compte est désactivé'], Response::HTTP_UNAUTHORIZED);
            }
        }

       
        $token = $jwtService->generateToken([
            'id' => $user->getId(),
            'login' => $user->getLogin(),
            'roles' => $user->getRoles()
        ]);

        $activeSubscriptions = $subscriptionChecker->getActiveSubscription($user->getEntreprise());

        return $this->responseData([
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
                'entreprise' => $user->getEntreprise(),
                /* 'inactiveSubscriptions' => $inactiveSubscriptions, */
                'pays' => $user->getEntreprise()->getPays()->getId(),
                'boutique' => $user->getBoutique() ? $user->getBoutique()->getId() : null,
                'succursale' => $user->getSurccursale() ? $user->getSurccursale()->getId() : null,
                'settings' =>  $settingRepository->findOneByInEnvironment(['entreprise' => $user->getEntreprise()]),
                'activeSubscriptions' => $activeSubscriptions
            ],
            'token_expires_in' => $jwtService->getTtl()
        ], 'group1', ['Content-Type' => 'application/json']);
    }

    #[Route('/api/logout', methods: ['POST'])]
    #[OA\Post(
        summary: "Permet de déconnecter un utilisateur",
        description: "Permet de déconnecter un utilisateur en invalidant le token JWT",
        responses: [
            new OA\Response(response: 200, description: "Déconnexion réussie"),
            new OA\Response(response: 401, description: "Non authentifié")
        ]
    )]
    #[OA\Tag(name: 'auth')]
    public function logout(
        Request $request,
        JwtService $jwtService
    ): JsonResponse {
        $authHeader = $request->headers->get('Authorization');

        if (!$authHeader || !preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $this->json([
                'error' => 'Token manquant',
                'success' => false
            ], Response::HTTP_UNAUTHORIZED);
        }

        $token = $matches[1];

        try {
            $invalidated = $jwtService->invalidateToken($token);

            if ($invalidated) {
                return $this->json([
                    'message' => 'Déconnexion réussie',
                    'success' => true,
                    'timestamp' => time()
                ]);
            } else {
                return $this->json([
                    'error' => 'Erreur lors de la déconnexion',
                    'success' => false
                ], Response::HTTP_BAD_REQUEST);
            }
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la déconnexion',
                'success' => false
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
