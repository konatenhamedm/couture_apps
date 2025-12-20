<?php

namespace App\Tests\Controller\ApiClient\Helpers;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use App\Entity\User;
use App\Entity\TypeUser;
use App\Entity\Entreprise;
use App\Entity\Boutique;
use App\Entity\Surccursale;

/**
 * Helper class for managing authentication in API Client tests
 */
class AuthenticationTestHelper
{
    /**
     * Create a Super Admin JWT token
     */
    public static function createSuperAdminToken(User $user = null): string
    {
        $payload = [
            'user_id' => $user ? $user->getId() : 1,
            'user_type' => 'SADM',
            'email' => $user ? $user->getEmail() : 'sadm@test.com',
            'has_subscription' => true,
            'exp' => time() + 3600,
            'iat' => time()
        ];
        
        return self::encodeToken($payload);
    }
    
    /**
     * Create a Boutique Admin JWT token
     */
    public static function createBoutiqueAdminToken(User $user = null): string
    {
        $payload = [
            'user_id' => $user ? $user->getId() : 2,
            'user_type' => 'ADB',
            'email' => $user ? $user->getEmail() : 'adb@test.com',
            'boutique_id' => $user && $user->getBoutique() ? $user->getBoutique()->getId() : 1,
            'has_subscription' => true,
            'exp' => time() + 3600,
            'iat' => time()
        ];
        
        return self::encodeToken($payload);
    }
    
    /**
     * Create a Regular User JWT token
     */
    public static function createRegularUserToken(User $user = null): string
    {
        $payload = [
            'user_id' => $user ? $user->getId() : 3,
            'user_type' => 'REG',
            'email' => $user ? $user->getEmail() : 'user@test.com',
            'boutique_id' => $user && $user->getBoutique() ? $user->getBoutique()->getId() : 1,
            'succursale_id' => $user && $user->getSurccursale() ? $user->getSurccursale()->getId() : 1,
            'has_subscription' => true,
            'exp' => time() + 3600,
            'iat' => time()
        ];
        
        return self::encodeToken($payload);
    }
    
    /**
     * Create a token for user without active subscription
     */
    public static function createUserWithoutSubscription(string $userType = 'REG'): string
    {
        $payload = [
            'user_id' => 999,
            'user_type' => $userType,
            'email' => 'no-sub@test.com',
            'has_subscription' => false,
            'exp' => time() + 3600,
            'iat' => time()
        ];
        
        return self::encodeToken($payload);
    }
    
    /**
     * Create an expired token
     */
    public static function createExpiredToken(): string
    {
        $payload = [
            'user_id' => 1,
            'user_type' => 'SADM',
            'email' => 'expired@test.com',
            'has_subscription' => true,
            'exp' => time() - 3600, // Expired 1 hour ago
            'iat' => time() - 7200
        ];
        
        return self::encodeToken($payload);
    }
    
    /**
     * Create an invalid token
     */
    public static function createInvalidToken(): string
    {
        return 'invalid.jwt.token';
    }
    
    /**
     * Authenticate a client with the given token
     */
    public static function authenticateClient(KernelBrowser $client, string $token): void
    {
        $client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer ' . $token);
    }
    
    /**
     * Remove authentication from client
     */
    public static function removeAuthentication(KernelBrowser $client): void
    {
        $client->setServerParameter('HTTP_AUTHORIZATION', null);
    }
    
    /**
     * Create authentication headers array
     */
    public static function createAuthHeaders(string $token): array
    {
        return [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json'
        ];
    }
    
    /**
     * Create multipart form headers with authentication
     */
    public static function createMultipartAuthHeaders(string $token): array
    {
        return [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'HTTP_ACCEPT' => 'application/json'
            // Note: Don't set CONTENT_TYPE for multipart, let Symfony handle it
        ];
    }
    
    /**
     * Encode a token payload (mock JWT encoding)
     */
    private static function encodeToken(array $payload): string
    {
        // This is a simplified mock JWT encoding for testing
        // In a real implementation, you would use a proper JWT library
        $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payload = base64_encode(json_encode($payload));
        $signature = base64_encode(hash_hmac('sha256', $header . '.' . $payload, 'test-secret', true));
        
        return $header . '.' . $payload . '.' . $signature;
    }
    
    /**
     * Decode a token payload (for testing purposes)
     */
    public static function decodeToken(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new \InvalidArgumentException('Invalid token format');
        }
        
        return json_decode(base64_decode($parts[1]), true);
    }
    
    /**
     * Create a token with custom payload
     */
    public static function createCustomToken(array $customPayload): string
    {
        $defaultPayload = [
            'user_id' => 1,
            'user_type' => 'SADM',
            'email' => 'custom@test.com',
            'has_subscription' => true,
            'exp' => time() + 3600,
            'iat' => time()
        ];
        
        $payload = array_merge($defaultPayload, $customPayload);
        return self::encodeToken($payload);
    }
    
    /**
     * Verify token structure for testing
     */
    public static function verifyTokenStructure(string $token): bool
    {
        try {
            $payload = self::decodeToken($token);
            
            $requiredFields = ['user_id', 'user_type', 'email', 'exp', 'iat'];
            foreach ($requiredFields as $field) {
                if (!isset($payload[$field])) {
                    return false;
                }
            }
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}