<?php

// src/Controller/Api/ResetPasswordController.php

namespace App\Controller;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\User;
use App\Service\SendMailService;
use App\Service\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/reset-password')]
class ResetPasswordController extends AbstractController
{
    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private ValidatorInterface $validator
    ) {}

    /*  #[Route('/requests', name: 'api_forgot_password_request', methods: ['POST'])]  */
    #[OA\Post(
        summary: "Request pour reset le mot de passe",
        description: "Request pour reset le mot de passe",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [

                    new OA\Property(property: "email", type: "string"),

                ],
                type: "object"
            )
        ),
        responses: [
            new OA\Response(response: 401, description: "Invalid credentials")
        ]
    )]
    #[OA\Tag(name: 'auth')]
    public function request(Request $request, SendMailService $sendMailService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (empty($email)) {
            return $this->json(
                ['message' => 'Email is required'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['login' => $email]);

        if (!$user) {
            return $this->json(
                ['message' => 'Vous avez demandé une réinitialisation de mot de passe. Si l\'email existe, un code de réinitialisation a été envoyé.'],
                Response::HTTP_OK
            );
        }

        try {

            $this->cleanExpiredTokens($user);

            $sixDigitCode = $this->generateSixDigitCode();

            $user->setPlainResetToken($sixDigitCode);
            $user->setPlainTokenExpiresAt(new \DateTimeImmutable('+15 minutes'));
            $this->entityManager->flush();

            $sendMailService->send(
                "supports@ateliya.com",
                $data['email'],
                "Réinitialisation du mot de passe",
                "otp",
                [
                    'otp_code' => $sixDigitCode,
                    "info_user" => [
                        "login" => $data['email'],
                        "nom" =>   $user->getLogin(),
                    ]
                ]
            );
        } catch (\Exception $e) {

            return $this->json(
                ['message' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        }

        return $this->json([
            'message' => 'Vous avez demandé une réinitialisation de mot de passe. Si l\'email existe, un code de réinitialisation a été envoyé.',
            'token' => $sixDigitCode
        ]);
    }

    private function generateSixDigitCode(): string
    {
        return sprintf('%06d', random_int(0, 999999));
    }

    private function cleanExpiredTokens(User $user): void
    {
        if ($user->getPlainTokenExpiresAt() && $user->getPlainTokenExpiresAt() < new \DateTimeImmutable()) {
            $user->setPlainResetToken(null);
            $user->setPlainTokenExpiresAt(null);
        }
    }

    #[Route('/reset', name: 'api_reset_password', methods: ['POST'])]
    #[OA\Post(
        summary: "Reset user password",
        description: "Reset user password with token and new password",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "email", type: "string"),
                    new OA\Property(property: "token", type: "string"),
                    new OA\Property(property: "newPassword", type: "string")
                ],
                type: "object"
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Password reset successfully"),
            new OA\Response(response: 400, description: "Invalid token or missing data")
        ]
    )]
    #[OA\Tag(name: 'auth')]
    public function resetPassword(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $token = $data['token'] ?? null;
        $newPassword = $data['newPassword'] ?? null;

        if (empty($email) || empty($token) || empty($newPassword)) {
            return $this->json(
                ['message' => 'Email, token et nouveau mot de passe sont requis'],
                Response::HTTP_BAD_REQUEST
            );
        }
        if (strlen($newPassword) < 6) {
            return $this->json(
                ['message' => 'Le mot de passe doit contenir au moins 6 caractères'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['login' => $email]);

        if (!$user) {
            return $this->json(
                ['message' => 'Utilisateur non trouvé'],
                Response::HTTP_NOT_FOUND
            );
        }

        // Vérifier le token
        if (!$this->isSimpleTokenValid($user, $token)) {
            return $this->json(
                ['message' => 'Token invalide ou expiré'],
                Response::HTTP_BAD_REQUEST
            );
        }

        try {

            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);

            $user->setPlainResetToken(null);
            $user->setPlainTokenExpiresAt(null);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Mot de passe réinitialisé avec succès',
                'success' => true
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['message' => 'Erreur lors de la réinitialisation du mot de passe'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

        public function errorResponse($DTO,string $customMessage = ''): ?JsonResponse
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
        }elseif ($customMessage != '') {
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

    #[Route('/reset/in/application', name: 'api_reset_password_in_application', methods: ['POST'])]
    #[OA\Post(
        summary: "Reset user password",
        description: "Reset user password with old password and new password",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "oldPassword", type: "string"),
                    new OA\Property(property: "confirmPassword", type: "string"),
                    new OA\Property(property: "newPassword", type: "string")
                ],
                type: "object"
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Password reset successfully"),
            new OA\Response(response: 400, description: "Invalid token or missing data")
        ]
    )]
    #[OA\Tag(name: 'auth')]
    public function resetPasswordInApplication(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        TokenStorageInterface $tokenStorage
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $newPassword = $data['newPassword'] ?? null;
        $oldPassword = $data['oldPassword'] ?? null;

        $token = $tokenStorage->getToken();

        if (!$token) {
            return $this->json(
                ['message' => 'Utilisateur non authentifié'],
                Response::HTTP_UNAUTHORIZED
            );
        }
        $user = $token->getUser();

           $errorResponse = $request->get('confirmPassword') !== $request->get('newPassword') ?   $this->errorResponse($user, "Les mots de passe ne sont pas identiques") :  $this->errorResponse($user);
        if ($errorResponse !== null) {
            return $errorResponse; 
        }

        if (!$user instanceof User) {
            return $this->json(
                ['message' => 'Utilisateur non trouvé'],
                Response::HTTP_NOT_FOUND
            );
        }

        if (empty($oldPassword) || empty($newPassword)) {
            return $this->json(
                ['message' => 'Ancien mot de passe et nouveau mot de passe sont requis'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (strlen($newPassword) < 6) {
            return $this->json(
                ['message' => 'Le mot de passe doit contenir au moins 6 caractères'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (!$passwordHasher->isPasswordValid($user, $oldPassword)) {
            return $this->json(
                ['message' => 'Ancien mot de passe incorrect'],
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Mot de passe modifié avec succès',
                'success' => true
            ]);
        } catch (\Exception $e) {
            return $this->json(
                ['message' => 'Erreur lors de la modification du mot de passe'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }



    private function isSimpleTokenValid(User $user, string $token): bool
    {
        $currentToken = $user->getPlainResetToken();
        $expiresAt = $user->getPlainTokenExpiresAt();
        if ($currentToken === $token && $expiresAt && $expiresAt > new \DateTimeImmutable()) {
            return true;
        }
        if ($expiresAt && $expiresAt <= new \DateTimeImmutable()) {
            $user->setPlainResetToken(null);
            $user->setPlainTokenExpiresAt(null);
            $this->entityManager->flush();
        }

        return false;
    }


    #[Route('/verify-token-expired', name: 'api_verify_token_expired', methods: ['POST'])]
    #[OA\Post(
        summary: "verification si le token existe et s'il est expiré",
        description: "Verify if the reset token has expired",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "email", type: "string"),
                    new OA\Property(property: "token", type: "string")
                ],
                type: "object"
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Token expiration status"),
            new OA\Response(response: 400, description: "Missing required data")
        ]
    )]
    #[OA\Tag(name: 'auth')]
    public function verificationTokenExpiere(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $token = $data['token'] ?? null;

        if (empty($email) || empty($token)) {
            return $this->json(
                [
                    'message' => 'Email et token sont requis',
                    'expired' => true
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['login' => $email]);

        if (!$user) {
            return $this->json(
                [
                    'message' => 'Utilisateur non trouvé',
                    'expired' => true
                ],
                Response::HTTP_OK
            );
        }
        $isExpired = $this->isTokenExpired($user, $token);

        return $this->json([
            'expired' => $isExpired,
            'message' => $isExpired ? 'Le token a expiré' : 'Le token est valide'
        ]);
    }

    private function isTokenExpired(User $user, string $token): bool
    {
        $currentToken = $user->getPlainResetToken();
        $expiresAt = $user->getPlainTokenExpiresAt();

        if ($currentToken !== $token) {
            return true;
        }
        if (!$expiresAt) {
            return true;
        }

        $isExpired = $expiresAt <= new \DateTimeImmutable();
        if ($isExpired) {
            $user->setPlainResetToken(null);
            $user->setPlainTokenExpiresAt(null);
            $this->entityManager->flush();
        }

        return $isExpired;
    }
}