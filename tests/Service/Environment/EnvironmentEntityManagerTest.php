<?php

namespace App\Tests\Service\Environment;

use App\Entity\Client;
use App\Entity\Boutique;
use App\Service\Environment\EnvironmentEntityManager;
use App\Service\EntityManagerProvider;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class EnvironmentEntityManagerTest extends TestCase
{
    private EnvironmentEntityManager $environmentEntityManager;
    private EntityManagerProvider $entityManagerProvider;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->entityManagerProvider = $this->createMock(EntityManagerProvider::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->entityManagerProvider->method('getEntityManager')->willReturn($this->entityManager);
        
        $this->environmentEntityManager = new EnvironmentEntityManager(
            $this->entityManagerProvider,
            $this->logger
        );
    }

    public function testEnsureEntityIsManagedWithManagedEntity(): void
    {
        $client = new Client();
        $client->setNom('Test');

        // Mock que l'entité est déjà gérée
        $this->entityManager->method('contains')->with($client)->willReturn(true);

        $result = $this->environmentEntityManager->ensureEntityIsManaged($client);

        $this->assertSame($client, $result);
    }

    public function testEnsureEntityIsManagedWithDetachedEntity(): void
    {
        $client = new Client();
        $client->setNom('Test');
        
        // Simuler un ID pour l'entité
        $reflection = new \ReflectionClass($client);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($client, 1);

        // Mock que l'entité n'est pas gérée
        $this->entityManager->method('contains')->with($client)->willReturn(false);
        
        // Mock la récupération de l'entité depuis la base
        $managedClient = new Client();
        $managedClient->setNom('Test Managed');
        $this->entityManager->method('find')->with(Client::class, 1)->willReturn($managedClient);

        $result = $this->environmentEntityManager->ensureEntityIsManaged($client);

        $this->assertSame($managedClient, $result);
        $this->assertEquals('Test Managed', $result->getNom());
    }

    public function testValidateEntityContextWithManagedEntity(): void
    {
        $client = new Client();
        
        $this->entityManager->method('contains')->with($client)->willReturn(true);

        $result = $this->environmentEntityManager->validateEntityContext($client);

        $this->assertTrue($result);
    }

    public function testValidateEntityContextWithDetachedEntity(): void
    {
        $client = new Client();
        
        $this->entityManager->method('contains')->with($client)->willReturn(false);

        $result = $this->environmentEntityManager->validateEntityContext($client);

        $this->assertFalse($result);
    }

    public function testIsEntityDetachedWithNewEntity(): void
    {
        $client = new Client(); // Pas d'ID, donc nouvelle entité

        $result = $this->environmentEntityManager->isEntityDetached($client);

        $this->assertFalse($result); // Une nouvelle entité n'est pas détachée
    }

    public function testIsEntityDetachedWithDetachedEntity(): void
    {
        $client = new Client();
        
        // Simuler un ID pour l'entité
        $reflection = new \ReflectionClass($client);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($client, 1);

        // Mock que l'entité n'est pas gérée
        $this->entityManager->method('contains')->with($client)->willReturn(false);

        $result = $this->environmentEntityManager->isEntityDetached($client);

        $this->assertTrue($result);
    }

    public function testMergeDetachedEntityWithManagedEntity(): void
    {
        $client = new Client();
        $client->setNom('Already Managed');

        // Mock que l'entité est déjà gérée
        $this->entityManager->method('contains')->with($client)->willReturn(true);

        $result = $this->environmentEntityManager->mergeDetachedEntity($client);

        // Si l'entité est déjà gérée, elle devrait être retournée telle quelle
        $this->assertSame($client, $result);
    }

    public function testMergeDetachedEntityWithNewEntity(): void
    {
        $client = new Client();
        $client->setNom('New Entity');

        // Mock que l'entité n'est pas gérée et n'a pas d'ID
        $this->entityManager->method('contains')->with($client)->willReturn(false);
        
        // Mock persist pour les nouvelles entités
        $this->entityManager->expects($this->once())->method('persist')->with($client);

        $result = $this->environmentEntityManager->mergeDetachedEntity($client);

        // Pour une nouvelle entité, elle devrait être persistée et retournée
        $this->assertSame($client, $result);
    }

    public function testDetachEntity(): void
    {
        $client = new Client();

        // Mock que l'entité est gérée
        $this->entityManager->method('contains')->with($client)->willReturn(true);
        
        // Vérifier que detach est appelé
        $this->entityManager->expects($this->once())->method('detach')->with($client);

        $this->environmentEntityManager->detachEntity($client);
    }

    public function testClearEntityCache(): void
    {
        // Vérifier que clear est appelé
        $this->entityManager->expects($this->once())->method('clear');

        $this->environmentEntityManager->clearEntityCache();
    }

    public function testResolveProxyEntity(): void
    {
        // Créer une entité avec un nom de classe proxy simulé
        $proxyClient = $this->createMock(Client::class);
        
        // Mock get_class pour retourner un nom de classe proxy
        $reflection = new \ReflectionClass(EnvironmentEntityManager::class);
        $method = $reflection->getMethod('getEntityClass');
        $method->setAccessible(true);

        // Pour ce test, on va juste vérifier que la méthode ne plante pas
        $result = $this->environmentEntityManager->resolveProxyEntity($proxyClient);

        $this->assertNotNull($result);
    }

    public function testHandleNullEntity(): void
    {
        $this->assertNull($this->environmentEntityManager->ensureEntityIsManaged(null));
        $this->assertNull($this->environmentEntityManager->refreshEntityInCurrentContext(null));
        $this->assertFalse($this->environmentEntityManager->validateEntityContext(null));
        $this->assertNull($this->environmentEntityManager->resolveProxyEntity(null));
        $this->assertFalse($this->environmentEntityManager->isEntityDetached(null));
        $this->assertNull($this->environmentEntityManager->mergeDetachedEntity(null));
        
        // detachEntity avec null ne devrait pas planter
        $this->environmentEntityManager->detachEntity(null);
    }
}