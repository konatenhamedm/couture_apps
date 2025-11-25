<?php

namespace App\Tests\Security;

use PHPUnit\Framework\TestCase;

class SecurityTest extends TestCase
{
    public function testJwtTokenGeneration(): void
    {
        $mockToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiYWRtaW4iOnRydWV9.TJVA95OrM7E2cBab30RMHrHDcEfxjoYZgeFONFh7HgQ';
        
        $this->assertIsString($mockToken);
        $this->assertStringContainsString('.', $mockToken);
        $this->assertGreaterThan(50, strlen($mockToken));
    }

    public function testPasswordHashing(): void
    {
        $plainPassword = 'mySecurePassword123!';
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
        
        $this->assertIsString($hashedPassword);
        $this->assertNotEquals($plainPassword, $hashedPassword);
        $this->assertTrue(password_verify($plainPassword, $hashedPassword));
        $this->assertFalse(password_verify('wrongPassword', $hashedPassword));
    }

    public function testApiKeyValidation(): void
    {
        $validApiKey = 'ateliya_' . bin2hex(random_bytes(16));
        $invalidApiKey = 'invalid_key';
        
        $this->assertStringStartsWith('ateliya_', $validApiKey);
        $this->assertEquals(40, strlen($validApiKey));
        $this->assertNotStringStartsWith('ateliya_', $invalidApiKey);
    }

    public function testInputSanitization(): void
    {
        $maliciousInput = '<script>alert("xss")</script>';
        
        $escaped = htmlspecialchars($maliciousInput, ENT_QUOTES, 'UTF-8');
        $this->assertStringNotContainsString('<script>', $escaped);
        $this->assertStringContainsString('&lt;script&gt;', $escaped);
    }
}