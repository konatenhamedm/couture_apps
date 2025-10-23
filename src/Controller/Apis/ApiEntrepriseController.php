<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\Entreprise;
use App\Repository\BoutiqueRepository;
use App\Repository\EntrepriseRepository;
use App\Repository\SurccursaleRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[Route('/api/entreprise')]
class ApiEntrepriseController extends ApiInterface
{
    #[Route('/surccursale/boutique', methods: ['GET'])]
    /**
     * Retourne la liste des boutiques et surccursales.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Returns the rewards of an user',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Entreprise::class, groups: ['full']))
        )
    )]
    #[OA\Tag(name: 'entreprise')]
    // #[Security(name: 'Bearer')]
    public function index(SurccursaleRepository $surccursaleRepository, BoutiqueRepository $boutiqueRepository): Response
    {
        try {

            $surccursales = $surccursaleRepository->findBy(['entreprise' => $this->getUser()->getEntreprise()]);
            $boutiques = $boutiqueRepository->findBy(['entreprise' => $this->getUser()->getEntreprise()]);

            $data = [
                    "surccursales" => $surccursales,
                    "boutiques" => $boutiques
            ];


            $response =  $this->responseData($data, 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $exception) {
            $this->setMessage("");
            $response = $this->response('[]');
        }

        // On envoie la réponse
        return $response;
    }
}
