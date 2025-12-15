<?php

namespace App\Controller\Apis;

use App\Service\DynamicDatabaseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/test')]
class ApiDatabaseTestController extends AbstractController
{
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