<?php

namespace App\Service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class JwtService
{
    private string $secret;
    private int $ttl;

  public function __construct(ParameterBagInterface $params)
    {
        $this->secret = $params->get('jwt.secret');
        $this->ttl = $params->get('jwt.ttl');
        
        if (empty($this->secret)) {
            throw new \RuntimeException('JWT secret must be configured');
        }
    }

    public function generateToken(array $payload): string
    {
        $payload = array_merge($payload, [
            'iat' => time(),
            'exp' => time() + $this->ttl,
            'iss' => 'le nom de lapplication'  
        ]);

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    public function validateToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
            return (array) $decoded;
        } catch (\Exception $e) {
$this->setStatusCode(500);
            return null;
        }
    }

    public function getTtl(): int
    {
        return $this->ttl;
    }
}