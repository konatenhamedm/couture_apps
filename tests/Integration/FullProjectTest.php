<?php

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class FullProjectTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    public function testApplicationBootstrap(): void
    {
        $this->assertNotNull($this->client);
        $this->assertTrue($this->client->getContainer()->has('doctrine'));
        $this->assertTrue($this->client->getContainer()->has('serializer'));
    }

    public function testDatabaseConnection(): void
    {
        $entityManager = $this->client->getContainer()->get('doctrine')->getManager();
        $connection = $entityManager->getConnection();
        
        $this->assertTrue($connection->isConnected() || $connection->connect());
    }

    public function testApiDocumentationEndpoint(): void
    {
        $this->client->request('GET', '/api/doc');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testEntityMappings(): void
    {
        $entityManager = $this->client->getContainer()->get('doctrine')->getManager();
        $metadataFactory = $entityManager->getMetadataFactory();

        $entities = [
            'App\Entity\User',
            'App\Entity\Client',
            'App\Entity\Facture',
            'App\Entity\Reservation',
            'App\Entity\Paiement'
        ];

        foreach ($entities as $entityClass) {
            $metadata = $metadataFactory->getMetadataFor($entityClass);
            $this->assertNotNull($metadata);
        }
    }

    public function testServicesConfiguration(): void
    {
        $container = $this->client->getContainer();

        $services = [
            'App\Service\StatistiquesService',
            'App\Service\PaiementService',
            'App\Service\SendMailService',
            'App\Service\Utils'
        ];

        foreach ($services as $serviceClass) {
            $this->assertTrue($container->has($serviceClass));
        }
    }
}