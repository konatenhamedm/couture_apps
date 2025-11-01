<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\SubscriptionChecker;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;


class UserController extends AbstractController
{
    #[Route('/api/user/{id}/toggle-active', methods: ['PATCH'])]
    #[OA\Patch(
        path: '/api/users/{id}/toggle-active',
        summary: 'Activer ou désactiver un utilisateur',
        tags: ['user'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(response: 200, description: 'Statut modifié'),
            new OA\Response(response: 404, description: 'Utilisateur non trouvé')
        ]
    )]
    #[OA\Tag(name: 'user')]
    public function toggleActive(User $user, UserRepository $repository,SubscriptionChecker $subscriptionChecker): JsonResponse
    {

        if($subscriptionChecker->getSettingByUser($this->getUser()->getEntreprise(), "user")){
             return $this->json(['message'=>"Vous avez atteint le nombre user actif pour votre pack d'abonnement"]);
        }
        $repository->updateActiveStatus($user, !$user->isActive());
        return $this->json(['message' => 'Status updated']);
    }

   /*  #[Route('/create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/users/create',
        summary: 'Créer un utilisateur',
        tags: ['Users'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['login', 'password'],
                properties: [
                    new OA\Property(property: 'login', type: 'string', example: 'jane.doe'),
                    new OA\Property(property: 'password', type: 'string', example: '123456'),
                    new OA\Property(property: 'isActive', type: 'boolean', example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Utilisateur créé avec succès',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 42),
                        new OA\Property(property: 'login', type: 'string', example: 'jane.doe'),
                        new OA\Property(property: 'isActive', type: 'boolean', example: true),
                        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), example: ['ROLE_USER']),
                        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', example: '2025-08-06T12:00:00Z')
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Requête invalide')
        ]
    )]
    public function create(
        Request $request,
        UserRepository $repository,
        UserPasswordHasherInterface $hasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $user = new User();
        $user->setLogin($data['login']);
        $user->setPassword($hasher->hashPassword($user, $data['password']));
        $user->setIsActive($data['isActive'] ?? true);

        $repository->save($user, true);

        return $this->json([
            'id' => $user->getId(),
            'login' => $user->getLogin(),
            'isActive' => $user->isActive(),
            'roles' => $user->getRoles(),
            'createdAt' => $user->getCreatedAt()?->format('Y-m-d\TH:i:s\Z'),
        ], 201);
    } */


/*     #[Route('/api/user/test-token', methods: ['GET'])]
    #[OA\Get(
        path: '/api/users/test-token',
        summary: 'Tester le token JWT',
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Retourne l’utilisateur et ses rôles',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'user', type: 'string'),
                        new OA\Property(property: 'roles', type: 'string')
                    ]
                )
            )
        ]
    )]
    public function testToken(): JsonResponse
    {
        return new JsonResponse([
            'user' => "ddsdd",
            'roles' => "sddd",
        ]);
    } */
}
