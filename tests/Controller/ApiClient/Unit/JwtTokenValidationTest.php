<?php

namespace App\Tests\Controller\ApiClient\Unit;

use App\Tests\Controller\ApiClient\Helpers\AuthenticationTestHelper;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for JWT token validation and structure
 */
class JwtTokenValidationTest extends TestCase
{
    /**
     * Test Super Admin token generation
     */
    public function testSuperAdminTokenGeneration(): void
    {
        $token = AuthenticationTestHelper::createSuperAdminToken();
        
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        
        // Verify token structure (3 parts separated by dots)
        $parts = explode('.', $token);
        $this->assertCount(3, $parts, 'JWT token should have 3 parts');
        
        // Decode and verify payload
        $payload = AuthenticationTestHelper::decodeToken($token);
        
        $this->assertEquals('SADM', $payload['user_type']);
        $this->assertTrue($payload['has_subscription']);
        $this->assertArrayHasKey('user_id', $payload);
        $this->assertArrayHasKey('email', $payload);
        $this->assertArrayHasKey('exp', $payload);
        $this->assertArrayHasKey('iat', $payload);
    }
    
    /**
     * Test Boutique Admin token generation
     */
    public function testBoutiqueAdminTokenGeneration(): void
    {
        $token = AuthenticationTestHelper::createBoutiqueAdminToken();
        $payload = AuthenticationTestHelper::decodeToken($token);
        
        $this->assertEquals('ADB', $payload['user_type']);
        $this->assertTrue($payload['has_subscription']);
        $this->assertArrayHasKey('boutique_id', $payload);
    }
    
    /**
     * Test Regular User token generation
     */
    public function testRegularUserTokenGeneration(): void
    {
        $token = AuthenticationTestHelper::createRegularUserToken();
        $payload = AuthenticationTestHelper::decodeToken($token);
        
        $this->assertEquals('REG', $payload['user_type']);
        $this->assertTrue($payload['has_subscription']);
        $this->assertArrayHasKey('boutique_id', $payload);
        $this->assertArrayHasKey('succursale_id', $payload);
    }
    
    /**
     * Test user without subscription token
     */
    public function testUserWithoutSubscriptionToken(): void
    {
        $token = AuthenticationTestHelper::createUserWithoutSubscription();
        $payload = AuthenticationTestHelper::decodeToken($token);
        
        $this->assertFalse($payload['has_subscription']);
        $this->assertArrayHasKey('user_type', $payload);
    }
    
    /**
     * Test expired token generation
     */
    public function testExpiredTokenGeneration(): void
    {
        $token = AuthenticationTestHelper::createExpiredToken();
        $payload = AuthenticationTestHelper::decodeToken($token);
        
        $this->assertLessThan(time(), $payload['exp'], 'Token should be expired');
    }
    
    /**
     * Test custom token generation
     */
    public function testCustomTokenGeneration(): void
    {
        $customData = [
            'user_id' => 12345,
            'user_type' => 'CUSTOM',
            'email' => 'custom@example.com',
            'custom_field' => 'custom_value',
            'has_subscription' => false
        ];
        
        $token = AuthenticationTestHelper::createCustomToken($customData);
        $payload = AuthenticationTestHelper::decodeToken($token);
        
        $this->assertEquals(12345, $payload['user_id']);
        $this->assertEquals('CUSTOM', $payload['user_type']);
        $this->assertEquals('custom@example.com', $payload['email']);
        $this->assertEquals('custom_value', $payload['custom_field']);
        $this->assertFalse($payload['has_subscription']);
    }
    
    /**
     * Test token structure validation
     */
    public function testTokenStructureValidation(): void
    {
        // Valid tokens
        $validTokens = [
            AuthenticationTestHelper::createSuperAdminToken(),
            AuthenticationTestHelper::createBoutiqueAdminToken(),
            AuthenticationTestHelper::createRegularUserToken(),
            AuthenticationTestHelper::createUserWithoutSubscription(),
            AuthenticationTestHelper::createExpiredToken()
        ];
        
        foreach ($validTokens as $token) {
            $this->assertTrue(
                AuthenticationTestHelper::verifyTokenStructure($token),
                'Token should have valid structure'
            );
        }
        
        // Invalid tokens
        $invalidTokens = [
            'invalid.token',
            'not.a.jwt.token.at.all',
            'invalid',
            '',
            'header.payload' // Missing signature
        ];
        
        foreach ($invalidTokens as $token) {
            $this->assertFalse(
                AuthenticationTestHelper::verifyTokenStructure($token),
                "Token '{$token}' should be invalid"
            );
        }
    }
    
    /**
     * Test token expiration times
     */
    public function testTokenExpirationTimes(): void
    {
        $token = AuthenticationTestHelper::createSuperAdminToken();
        $payload = AuthenticationTestHelper::decodeToken($token);
        
        $currentTime = time();
        $expirationTime = $payload['exp'];
        $issuedTime = $payload['iat'];
        
        // Token should be issued now (within 5 seconds tolerance)
        $this->assertLessThanOrEqual(5, abs($currentTime - $issuedTime));
        
        // Token should expire in the future (around 1 hour)
        $this->assertGreaterThan($currentTime, $expirationTime);
        $this->assertLessThanOrEqual(3700, $expirationTime - $currentTime); // Max 1 hour + 100 seconds tolerance
        $this->assertGreaterThanOrEqual(3500, $expirationTime - $currentTime); // Min 1 hour - 100 seconds tolerance
    }
    
    /**
     * Test token decoding with invalid tokens
     */
    public function testTokenDecodingWithInvalidTokens(): void
    {
        $invalidTokens = [
            'invalid.token.format',
            'not.a.jwt',
            '',
            'header.payload' // Missing signature part
        ];
        
        foreach ($invalidTokens as $invalidToken) {
            $this->expectException(\InvalidArgumentException::class);
            AuthenticationTestHelper::decodeToken($invalidToken);
        }
    }
    
    /**
     * Test multiple token generations are unique
     */
    public function testMultipleTokenGenerationsAreUnique(): void
    {
        $tokens = [];
        
        // Generate multiple tokens
        for ($i = 0; $i < 5; $i++) {
            $tokens[] = AuthenticationTestHelper::createSuperAdminToken();
            // Small delay to ensure different timestamps
            usleep(1000);
        }
        
        // All tokens should be unique (due to different iat timestamps)
        $uniqueTokens = array_unique($tokens);
        $this->assertCount(
            count($tokens),
            $uniqueTokens,
            'All generated tokens should be unique'
        );
    }
    
    /**
     * Test token payload completeness
     */
    public function testTokenPayloadCompleteness(): void
    {
        $requiredFields = ['user_id', 'user_type', 'email', 'has_subscription', 'exp', 'iat'];
        
        $tokens = [
            'sadm' => AuthenticationTestHelper::createSuperAdminToken(),
            'adb' => AuthenticationTestHelper::createBoutiqueAdminToken(),
            'regular' => AuthenticationTestHelper::createRegularUserToken(),
            'no_sub' => AuthenticationTestHelper::createUserWithoutSubscription()
        ];
        
        foreach ($tokens as $tokenType => $token) {
            $payload = AuthenticationTestHelper::decodeToken($token);
            
            foreach ($requiredFields as $field) {
                $this->assertArrayHasKey(
                    $field,
                    $payload,
                    "Token type '{$tokenType}' should contain field '{$field}'"
                );
            }
        }
    }
}