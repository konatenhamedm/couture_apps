<?php


namespace App\Controller\Apis;


use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Paiement;
use App\Entity\Statistique;
use App\Repository\StatistiqueRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class ApiStatistiqueController extends ApiInterface
{
    #[Route('/statistique', methods: ['GET'])]
    /**
     * Retourne la liste des statistiques.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of an user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Paiement::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'statistique')]
    // #[Security(name: 'Bearer')]
    public function index(StatistiqueRepository $statistiqueRepository): Response
    {
        try {

            $statistiques = $statistiqueRepository->findAll();

            $response =  $this->responseData($statistiques, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setMessage("");
            $response = $this->response('[]');
        }

        // On envoie la réponse
        return $response;
    }
}