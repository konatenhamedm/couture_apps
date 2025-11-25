<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ApiEndpointsTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testStatisticsEndpointsWithoutAuth(): void
    {
        $endpoints = [
            '/api/statistique/dashboard',
            '/api/statistique/revenus/evolution',
            '/api/statistique/commandes/evolution',
            '/api/statistique/revenus/par-type',
            '/api/statistique/top-clients',
            '/api/statistique/comparatif'
        ];

        foreach ($endpoints as $endpoint) {
            $this->client->request('POST', $endpoint, [], [], [], json_encode(['periode' => '30j']));
            
            $this->assertContains(
                $this->client->getResponse()->getStatusCode(),
                [Response::HTTP_UNAUTHORIZED, Response::HTTP_FORBIDDEN, Response::HTTP_BAD_REQUEST]
            );
        }
    }

    public function testApiDocumentation(): void
    {
        $this->client->request('GET', '/api/doc');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('swagger', strtolower($content));
    }

    public function testHealthCheck(): void
    {
        $this->client->request('GET', '/');
        $response = $this->client->getResponse();
        
        $this->assertNotEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }
}