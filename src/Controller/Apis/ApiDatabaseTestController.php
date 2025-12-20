<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Service\DynamicDatabaseService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

use App\Trait\DatabaseEnvironmentTrait;
#[Route('/api/test')]
class ApiDatabaseTestController extends ApiInterface
{
    use DatabaseEnvironmentTrait;

    #[Route('/database-info', name: 'api_test_database_info', methods: ['GET'])]
    public function testDatabaseInfo(DynamicDatabaseService $dynamicDb): JsonResponse
    {
        $env = $dynamicDb->getCurrentEnvironment();
        $connection = $dynamicDb->getConnection();
        
        $params = $connection->getParams();
        
        return $this->json([
            'environment' => $env,
            'database' => $params['dbname'] ?? 'unknown',
            'host' => $params['host'] ?? 'unknown',
            'message' => "Vous êtes connecté à la base de données: {$params['dbname']}"
        ]);
    }
}