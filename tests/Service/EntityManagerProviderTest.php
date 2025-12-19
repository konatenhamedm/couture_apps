<?php

namespace App\Tests\Service;

use App\Service\EntityManagerProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\DBAL\Connection;

class EntityManagerProviderTest extends TestCase
{
    private EntityManagerProvider $entityManagerProvider;
    private ManagerRegistry|MockObject $doctrine;
    private RequestStack|MockObject $requestStack;
    private EntityManagerInterface|MockObject $entityManager;
    private Connection|MockObject $connection;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->connection = $this->createMock(Connection::class);

        $this->entityManagerProvider = new EntityManagerProvider($this->doctrine, $this->requestStack);

        // Mock the entity manager to return our mocked connection
        $this->entityManager->method('getConnection')->willReturn($this->connection);
        
        // Mock doctrine to return our mocked entity manager
        $this->doctrine->method('getManager')->willReturn($this->entityManager);
    }

    public function testBeginTransaction(): void
    {
        // Expect beginTransaction to be called on the entity manager
        $this->entityManager->expects($this->once())
            ->method('beginTransaction');

        $this->entityManagerProvider->beginTransaction();
    }

    public function testCommit(): void
    {
        // Expect commit to be called on the entity manager
        $this->entityManager->expects($this->once())
            ->method('commit');

        $this->entityManagerProvider->commit();
    }

    public function testRollback(): void
    {
        // Expect rollback to be called on the entity manager
        $this->entityManager->expects($this->once())
            ->method('rollback');

        $this->entityManagerProvider->rollback();
    }

    public function testPersist(): void
    {
        $entity = new \stdClass();

        // Expect persist to be called on the entity manager with the entity
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($entity);

        $this->entityManagerProvider->persist($entity);
    }

    public function testFlush(): void
    {
        // Expect flush to be called on the entity manager
        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->entityManagerProvider->flush();
    }

    public function testIsTransactionActive(): void
    {
        // Mock connection to return true for transaction active
        $this->connection->expects($this->once())
            ->method('isTransactionActive')
            ->willReturn(true);

        $result = $this->entityManagerProvider->isTransactionActive();
        $this->assertTrue($result);
    }

    public function testExecuteInTransactionSuccess(): void
    {
        // Setup expectations for successful transaction
        $this->entityManager->expects($this->once())->method('beginTransaction');
        $this->entityManager->expects($this->once())->method('commit');
        $this->entityManager->expects($this->never())->method('rollback');

        $expectedResult = 'success';
        $operation = function($em) use ($expectedResult) {
            $this->assertSame($this->entityManager, $em);
            return $expectedResult;
        };

        $result = $this->entityManagerProvider->executeInTransaction($operation);
        $this->assertEquals($expectedResult, $result);
    }

    public function testExecuteInTransactionFailure(): void
    {
        // Setup expectations for failed transaction
        $this->entityManager->expects($this->once())->method('beginTransaction');
        $this->entityManager->expects($this->never())->method('commit');
        $this->entityManager->expects($this->once())->method('rollback');

        $expectedException = new \Exception('Test exception');
        $operation = function($em) use ($expectedException) {
            throw $expectedException;
        };

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Test exception');

        $this->entityManagerProvider->executeInTransaction($operation);
    }

    /**
     * Test property: Transaction operations are properly delegated to EntityManager
     * 
     * This property-based test verifies that all transaction operations
     * are correctly delegated to the underlying EntityManager.
     */
    public function testTransactionOperationsDelegationProperty(): void
    {
        // Test multiple transaction scenarios
        $scenarios = [
            'begin' => function() { $this->entityManagerProvider->beginTransaction(); },
            'commit' => function() { $this->entityManagerProvider->commit(); },
            'rollback' => function() { $this->entityManagerProvider->rollback(); },
            'flush' => function() { $this->entityManagerProvider->flush(); },
        ];

        foreach ($scenarios as $operation => $callable) {
            // Reset mocks for each scenario
            $this->setUp();

            // Set expectation based on operation
            switch ($operation) {
                case 'begin':
                    $this->entityManager->expects($this->once())->method('beginTransaction');
                    break;
                case 'commit':
                    $this->entityManager->expects($this->once())->method('commit');
                    break;
                case 'rollback':
                    $this->entityManager->expects($this->once())->method('rollback');
                    break;
                case 'flush':
                    $this->entityManager->expects($this->once())->method('flush');
                    break;
            }

            // Execute the operation
            $callable();
        }
    }

    /**
     * Test property: executeInTransaction provides proper error handling
     * 
     * This test verifies that executeInTransaction always rolls back
     * on exceptions and re-throws them.
     */
    public function testExecuteInTransactionErrorHandlingProperty(): void
    {
        // Test 10 different exception scenarios
        for ($i = 0; $i < 10; $i++) {
            $this->setUp();

            $exceptionMessage = "Test exception $i";
            $expectedException = new \Exception($exceptionMessage);

            // Setup expectations
            $this->entityManager->expects($this->once())->method('beginTransaction');
            $this->entityManager->expects($this->never())->method('commit');
            $this->entityManager->expects($this->once())->method('rollback');

            $operation = function($em) use ($expectedException) {
                throw $expectedException;
            };

            try {
                $this->entityManagerProvider->executeInTransaction($operation);
                $this->fail('Expected exception was not thrown');
            } catch (\Exception $e) {
                $this->assertEquals($exceptionMessage, $e->getMessage());
            }
        }
    }
}