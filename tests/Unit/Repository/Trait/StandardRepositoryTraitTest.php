<?php

namespace App\Tests\Unit\Repository\Trait;

use App\Repository\Trait\StandardRepositoryTrait;
use App\Repository\Result\PaginationResult;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class StandardRepositoryTraitTest extends TestCase
{
    private MockObject $entityManager;
    private MockObject $queryBuilder;
    private MockObject $query;
    private TestRepository $repository;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(Query::class);
        
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->entityManager);
        
        $this->repository = new TestRepository($registry);
        $this->repository->setEntityManager($this->entityManager);
    }

    public function testSaveWithFlush(): void
    {
        $entity = new \stdClass();
        
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($entity);
            
        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->repository->save($entity, true);
    }

    public function testSaveWithoutFlush(): void
    {
        $entity = new \stdClass();
        
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($entity);
            
        $this->entityManager->expects($this->never())
            ->method('flush');

        $this->repository->save($entity, false);
    }

    public function testRemoveWithFlush(): void
    {
        $entity = new \stdClass();
        
        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($entity);
            
        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->repository->remove($entity, true);
    }

    public function testRemoveWithoutFlush(): void
    {
        $entity = new \stdClass();
        
        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($entity);
            
        $this->entityManager->expects($this->never())
            ->method('flush');

        $this->repository->remove($entity, false);
    }

    public function testGetEntityClass(): void
    {
        $this->assertEquals('TestEntity', $this->repository->getEntityClass());
    }

    public function testPaginateWithSorting(): void
    {
        $items = ['item1', 'item2'];
        $totalCount = 100;
        
        $this->queryBuilder->method('setFirstResult')->willReturnSelf();
        $this->queryBuilder->method('setMaxResults')->willReturnSelf();
        $this->queryBuilder->method('orderBy')->willReturnSelf();
        $this->queryBuilder->method('getQuery')->willReturn($this->query);
        $this->query->method('getResult')->willReturn($items);
        
        $this->repository->setQueryBuilder($this->queryBuilder);
        $this->repository->setTotalCount($totalCount);
        
        $criteria = ['_sort' => 'name', '_order' => 'DESC'];
        $result = $this->repository->paginate(2, 10, $criteria);
        
        $this->assertInstanceOf(PaginationResult::class, $result);
        $this->assertEquals($items, $result->getItems());
        $this->assertEquals($totalCount, $result->getTotalCount());
        $this->assertEquals(2, $result->getCurrentPage());
        $this->assertEquals(10, $result->getItemsPerPage());
    }

    public function testFindByWithOptions(): void
    {
        $this->queryBuilder->method('addOrderBy')->willReturnSelf();
        $this->queryBuilder->method('setMaxResults')->willReturnSelf();
        $this->queryBuilder->method('setFirstResult')->willReturnSelf();
        $this->queryBuilder->method('andWhere')->willReturnSelf();
        $this->queryBuilder->method('setParameter')->willReturnSelf();
        $this->queryBuilder->method('getQuery')->willReturn($this->query);
        $this->query->method('getResult')->willReturn(['result']);
        
        $this->repository->setQueryBuilder($this->queryBuilder);
        
        $result = $this->repository->findByWithOptions(
            ['status' => 'active'],
            ['name' => 'ASC'],
            10,
            5
        );
        
        $this->assertEquals(['result'], $result);
    }

    public function testFindWithFilters(): void
    {
        $this->queryBuilder->method('andWhere')->willReturnSelf();
        $this->queryBuilder->method('setParameter')->willReturnSelf();
        $this->queryBuilder->method('getQuery')->willReturn($this->query);
        $this->query->method('getResult')->willReturn(['filtered_result']);
        
        $this->repository->setQueryBuilder($this->queryBuilder);
        
        $filters = [
            'status' => ['operator' => 'eq', 'value' => 'active'],
            'age' => ['operator' => 'gt', 'value' => 18]
        ];
        
        $result = $this->repository->findWithFilters($filters);
        
        $this->assertEquals(['filtered_result'], $result);
    }

    public function testCountWithFilters(): void
    {
        $this->queryBuilder->method('select')->willReturnSelf();
        $this->queryBuilder->method('andWhere')->willReturnSelf();
        $this->queryBuilder->method('setParameter')->willReturnSelf();
        $this->queryBuilder->method('getQuery')->willReturn($this->query);
        $this->query->method('getSingleScalarResult')->willReturn(42);
        
        $this->repository->setQueryBuilder($this->queryBuilder);
        
        $filters = ['status' => 'active'];
        $result = $this->repository->countWithFilters($filters);
        
        $this->assertEquals(42, $result);
    }

    public function testValidateQueryParameters(): void
    {
        $parameters = [
            'validField' => 'value',
            'field_with_underscore' => 'value',
            'field.with.dot' => 'value',
            'invalid-field' => 'value', // Should be removed
            'field;DROP TABLE' => 'value' // Should be removed
        ];
        
        // Test the validation logic through reflection
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('validateQueryParameters');
        $method->setAccessible(true);
        
        $validated = $method->invoke($this->repository, $parameters);
        
        $expected = [
            'validField' => 'value',
            'field_with_underscore' => 'value',
            'field.with.dot' => 'value'
        ];
        
        $this->assertEquals($expected, $validated);
    }
}

// Test class that uses the trait
class TestRepository extends ServiceEntityRepository
{
    use StandardRepositoryTrait;
    
    private $testEntityManager;
    private $testQueryBuilder;
    private $testTotalCount = 0;

    public function __construct(ManagerRegistry $registry)
    {
        // Don't call parent constructor to avoid entity class issues in tests
    }

    public function setEntityManager($entityManager): void
    {
        $this->testEntityManager = $entityManager;
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->testEntityManager;
    }

    public function setQueryBuilder($queryBuilder): void
    {
        $this->testQueryBuilder = $queryBuilder;
    }

    public function createQueryBuilder($alias, $indexBy = null): QueryBuilder
    {
        return $this->testQueryBuilder;
    }

    public function getClassName(): string
    {
        return 'TestEntity';
    }

    public function setTotalCount(int $count): void
    {
        $this->testTotalCount = $count;
    }

    public function count(array $criteria = []): int
    {
        return $this->testTotalCount;
    }

    // Expose protected methods for testing
    public function testValidateQueryParametersPublic(array $parameters): array
    {
        return $this->validateQueryParameters($parameters);
    }
}