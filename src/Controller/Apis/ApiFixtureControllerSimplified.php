<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\ModeleBoutique;
use App\Entity\User;
use App\Repository\ModeleRepository;
use App\Repository\BoutiqueRepository;
use App\Repository\ModeleBoutiqueRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

/**
 * Exemple simplifié d'utilisation des transactions centralisées
 */
#[Route('/api/fixture-simple')]
#[OA\Tag(name: 'fixture-simple', description: 'Exemple de gestion centralisée des transactions')]
class ApiFixtureControllerSimplified extends ApiInterface
{
    /**
     * Exemple d'utilisation des transactions centralisées
     */
    #[Route('/modele-boutique-simple', methods: ['POST'])]
    public function createModeleBoutiqueSimple(
        ModeleRepository $modeleRepository,
        BoutiqueRepository $boutiqueRepository,
        ModeleBoutiqueRepository $modeleBoutiqueRepository
    ): Response {
        try {
            $modeles = $modeleRepository->findAllInEnvironment();
            $boutiques = $boutiqueRepository->findAllInEnvironment();
            $createdCount = 0;
            $createdModelesBoutique = [];

            $tailles = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
            $prixBase = [8000, 12000, 15000, 18000, 22000, 25000, 30000];

            foreach ($modeles as $modele) {
                foreach ($boutiques as $boutique) {
                    // Vérifier si l'association existe déjà
                    $existing = $modeleBoutiqueRepository->findOneByInEnvironment([
                        'modele' => $modele,
                        'boutique' => $boutique
                    ]);

                    if (!$existing) {
                        try {
                            // Utilisation de executeInTransaction pour une gestion automatique
                            $result = $this->executeInTransaction(function($em) use ($modele, $boutique, $prixBase, $tailles) {
                                $modeleBoutique = new ModeleBoutique();
                                $modeleBoutique->setPrix($prixBase[array_rand($prixBase)]);
                                $modeleBoutique->setQuantite(rand(10, 100));
                                $modeleBoutique->setBoutique($boutique);
                                $modeleBoutique->setModele($modele);
                                $modeleBoutique->setTaille($tailles[array_rand($tailles)]);
                                
                                // Get managed user for persistence
                                $managedUser = $this->getManagedUser();
                                if ($managedUser) {
                                    $modeleBoutique->setCreatedBy($managedUser);
                                    $modeleBoutique->setUpdatedBy($managedUser);
                                }
                                
                                $modeleBoutique->setCreatedAtValue();
                                $modeleBoutique->setUpdatedAt();

                                // Validate entity before persistence
                                if (!$this->validateEntityBeforePersist($modeleBoutique, $em)) {
                                    throw new \Exception('Validation failed for ModeleBoutique');
                                }

                                $em->persist($modeleBoutique);

                                // Mise à jour de la quantité globale du modèle
                                $modele->setQuantiteGlobale($modele->getQuantiteGlobale() + $modeleBoutique->getQuantite());
                                $em->persist($modele);

                                $em->flush();

                                return $modeleBoutique;
                            });

                            $createdModelesBoutique[] = $result;
                            $createdCount++;
                            
                        } catch (\Exception $e) {
                            // Log the error but continue with next iteration
                            continue;
                        }
                    }
                }
            }

            return $this->responseData([
                'message' => "$createdCount modèles de boutique créés avec succès (version simplifiée)",
                'count' => $createdCount,
                'modeles_boutique' => $createdModelesBoutique
            ], 'group1', ['Content-Type' => 'application/json']);

        } catch (\Exception $e) {
            return $this->createCustomErrorResponse("Erreur lors de la création des fixtures: " . $e->getMessage(), 500);
        }
    }

    /**
     * Exemple d'utilisation manuelle des transactions
     */
    #[Route('/modele-boutique-manual', methods: ['POST'])]
    public function createModeleBoutiqueManual(
        ModeleRepository $modeleRepository,
        BoutiqueRepository $boutiqueRepository,
        ModeleBoutiqueRepository $modeleBoutiqueRepository
    ): Response {
        try {
            $modeles = $modeleRepository->findAllInEnvironment();
            $boutiques = $boutiqueRepository->findAllInEnvironment();
            $createdCount = 0;

            foreach ($modeles as $modele) {
                foreach ($boutiques as $boutique) {
                    // Vérifier si l'association existe déjà
                    $existing = $modeleBoutiqueRepository->findOneByInEnvironment([
                        'modele' => $modele,
                        'boutique' => $boutique
                    ]);

                    if (!$existing) {
                        // Utilisation manuelle des transactions centralisées
                        $this->beginTransaction();
                        
                        try {
                            $modeleBoutique = new ModeleBoutique();
                            $modeleBoutique->setPrix(15000);
                            $modeleBoutique->setQuantite(50);
                            $modeleBoutique->setBoutique($boutique);
                            $modeleBoutique->setModele($modele);
                            $modeleBoutique->setTaille('M');
                            
                            // Get managed user for persistence
                            $managedUser = $this->getManagedUser();
                            if ($managedUser) {
                                $modeleBoutique->setCreatedBy($managedUser);
                                $modeleBoutique->setUpdatedBy($managedUser);
                            }
                            
                            $modeleBoutique->setCreatedAtValue();
                            $modeleBoutique->setUpdatedAt();

                            // Validate entity before persistence
                            if (!$this->validateEntityBeforePersist($modeleBoutique, $this->getEntityManager())) {
                                $this->rollback();
                                continue;
                            }

                            $this->getEntityManager()->persist($modeleBoutique);
                            $this->getEntityManager()->flush();
                            
                            $this->commit();
                            $createdCount++;
                            
                        } catch (\Exception $e) {
                            $this->rollback();
                            continue;
                        }
                    }
                }
            }

            return $this->responseData([
                'message' => "$createdCount modèles de boutique créés avec succès (version manuelle)",
                'count' => $createdCount
            ], 'group1', ['Content-Type' => 'application/json']);

        } catch (\Exception $e) {
            return $this->createCustomErrorResponse("Erreur lors de la création des fixtures: " . $e->getMessage(), 500);
        }
    }

    /**
     * Validate and prepare entity before persistence
     */
    private function validateEntityBeforePersist($entity, $entityManager = null): bool
    {
        // Validate the entity using Symfony validator
        $errors = $this->validator->validate($entity);
        
        if (count($errors) > 0) {
            return false;
        }

        return true;
    }


}