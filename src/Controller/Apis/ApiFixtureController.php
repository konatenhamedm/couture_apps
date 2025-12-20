<?php

namespace App\Controller\Apis;

use App\Controller\Apis\Config\ApiInterface;
use App\Entity\ModeleBoutique;
use App\Entity\Reservation;
use App\Entity\LigneReservation;
use App\Entity\PaiementReservation;
use App\Entity\EntreStock;
use App\Entity\LigneEntre;
use App\Entity\User;
use App\Entity\Entreprise;
use App\Entity\Pays;
use App\Entity\TypeUser;
use App\Entity\Boutique;
use App\Entity\Surccursale;
use App\Entity\Client;
use App\Entity\Modele;
use App\Entity\Operateur;
use App\Entity\Caisse;
use App\Entity\CaisseBoutique;
use App\Entity\CaisseSuccursale;
use App\Entity\Setting;
use App\Entity\Abonnement;
use App\Entity\ModuleAbonnement;
use App\Repository\ModeleRepository;
use App\Repository\BoutiqueRepository;
use App\Repository\ModeleBoutiqueRepository;
use App\Repository\ClientRepository;
use App\Repository\CaisseBoutiqueRepository;
use App\Repository\EntrepriseRepository;
use App\Repository\EntreStockRepository;
use App\Repository\UserRepository;
use App\Repository\PaysRepository;
use App\Repository\TypeUserRepository;
use App\Repository\SurccursaleRepository;
use App\Repository\OperateurRepository;
use App\Repository\CaisseRepository;
use App\Repository\CaisseSuccursaleRepository;
use App\Repository\SettingRepository;
use App\Repository\AbonnementRepository;
use App\Repository\ModuleAbonnementRepository;
use App\Service\EntityManagerProvider;
use App\Service\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;


/**
 * Contrôleur pour les fixtures de données de test
 */
#[Route('/api/fixture')]
#[OA\Tag(name: 'fixture', description: 'Génération de données de test pour le développement')]
class ApiFixtureController extends ApiInterface
{


    /**
     * Initialise la base de données avec les données de base (sans authentification)
     */
    #[Route('/init-database', methods: ['POST'])]
    #[OA\Post(
        path: "/api/fixture/init-database",
        summary: "Initialiser la base de données",
        description: "Crée les données de base nécessaires pour démarrer l'application (pays, entreprise, utilisateur admin, etc.)",
        tags: ['fixture']
    )]
    public function initDatabase(): Response
    {
        try {
            $results = [];
            
            // 1. Créer les pays
            $paysResult = $this->createPaysFixtures();
            $results['pays'] = $paysResult;
            
            // 2. Créer l'entreprise
            $entrepriseResult = $this->createEntrepriseFixtures();
            $results['entreprise'] = $entrepriseResult;
            
            // 3. Créer les types d'utilisateurs
            $typeUserResult = $this->createTypeUserFixtures();
            $results['type_users'] = $typeUserResult;
            
            // 4. Créer les utilisateurs (admin)
            $userResult = $this->createUserFixtures();
            $results['users'] = $userResult;

            return $this->responseData([
                'message' => 'Base de données initialisée avec succès',
                'results' => $results,
                'credentials' => [
                    'admin' => ['login' => 'admin@ateliya.com', 'password' => 'admin123'],
                    'manager' => ['login' => 'manager@ateliya.com', 'password' => 'manager123']
                ]
            ], 'group1', ['Content-Type' => 'application/json']);

        } catch (\Exception $e) {
            error_log("Erreur lors de l'initialisation de la base de données: " . $e->getMessage());
            return $this->createCustomErrorResponse("Erreur lors de l'initialisation: " . $e->getMessage(), 500);
        }
    }

    /**
     * Crée toutes les fixtures nécessaires pour remplir la base de données
     */
    #[Route('/create-all', methods: ['POST'])]
    #[OA\Post(
        path: "/api/fixture/create-all",
        summary: "Créer toutes les fixtures",
        description: "Crée un jeu complet de données de test : pays, entreprise, utilisateurs, boutiques, succursales, modèles, clients, etc.",
        tags: ['fixture']
    )]
    public function createAllFixtures(): Response
    {
        try {
            $results = [];
            
            // 1. Créer les pays
            $paysResult = $this->createPaysFixtures();
            $results['pays'] = $paysResult;
            
            // 2. Créer l'entreprise
            $entrepriseResult = $this->createEntrepriseFixtures();
            $results['entreprise'] = $entrepriseResult;
            
            // 3. Créer les types d'utilisateurs
            $typeUserResult = $this->createTypeUserFixtures();
            $results['type_users'] = $typeUserResult;
            
            // 4. Créer les utilisateurs
            $userResult = $this->createUserFixtures();
            $results['users'] = $userResult;
            
            // 5. Créer les boutiques
            $boutiqueResult = $this->createBoutiqueFixtures();
            $results['boutiques'] = $boutiqueResult;
            
            // 6. Créer les succursales
            $succursaleResult = $this->createSuccursaleFixtures();
            $results['succursales'] = $succursaleResult;
            
            // 7. Créer les opérateurs
            $operateurResult = $this->createOperateurFixtures();
            $results['operateurs'] = $operateurResult;
            
            // 8. Créer les caisses
            $caisseResult = $this->createCaisseFixtures();
            $results['caisses'] = $caisseResult;
            
            // 9. Créer les modèles
            $modeleResult = $this->createModeleFixtures();
            $results['modeles'] = $modeleResult;
            
            // 10. Créer les clients
            $clientResult = $this->createClientFixtures();
            $results['clients'] = $clientResult;
            
            // 11. Créer les settings
            $settingResult = $this->createSettingFixtures();
            $results['settings'] = $settingResult;
            
            // 12. Créer les abonnements
            $abonnementResult = $this->createAbonnementFixtures();
            $results['abonnements'] = $abonnementResult;

            return $this->responseData([
                'message' => 'Toutes les fixtures ont été créées avec succès',
                'results' => $results
            ], 'group1', ['Content-Type' => 'application/json']);

        } catch (\Exception $e) {
            error_log("Erreur lors de la création de toutes les fixtures: " . $e->getMessage());
            return $this->createCustomErrorResponse("Erreur lors de la création des fixtures: " . $e->getMessage(), 500);
        }
    }

    /**
     * Crée les pays de base
     */
    private function createPaysFixtures(): array
    {
        try {
            $paysRepository = $this->em->getRepository(Pays::class);
            $createdCount = 0;
            
            $paysData = [
                ['libelle' => 'Côte d\'Ivoire', 'code' => 'CI', 'indicatif' => '+225'],
                ['libelle' => 'France', 'code' => 'FR', 'indicatif' => '+33'],
                ['libelle' => 'Sénégal', 'code' => 'SN', 'indicatif' => '+221'],
                ['libelle' => 'Mali', 'code' => 'ML', 'indicatif' => '+223'],
                ['libelle' => 'Burkina Faso', 'code' => 'BF', 'indicatif' => '+226']
            ];

            foreach ($paysData as $data) {
                $existing = $paysRepository->findOneBy(['code' => $data['code']]);
                if (!$existing) {
                    $pays = new Pays();
                    $pays->setLibelle($data['libelle']);
                    $pays->setCode($data['code']);
                    $pays->setIndicatif($data['indicatif']);
                    $pays->setActif(true); // Ajouter cette ligne
                    
                    $this->configureTraitEntity($pays);
                    $this->em->persist($pays);
                    $this->em->flush();
                    $createdCount++;
                }
            }

            return ['count' => $createdCount, 'message' => "$createdCount pays créés"];
        } catch (\Exception $e) {
            error_log("Erreur création pays: " . $e->getMessage());
            return ['count' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Crée l'entreprise de base
     */
    private function createEntrepriseFixtures(): array
    {
        try {
            $entrepriseRepository = $this->em->getRepository(Entreprise::class);
            $paysRepository = $this->em->getRepository(Pays::class);
            $createdCount = 0;
            
            $existing = $entrepriseRepository->findOneBy(['libelle' => 'Ateliya Couture']);
            if (!$existing) {
                $pays = $paysRepository->findOneBy(['code' => 'CI']);
                
                $entreprise = new Entreprise();
                $entreprise->setLibelle('Ateliya Couture');
                $entreprise->setNumero('+225 0123456789');
                $entreprise->setEmail('contact@ateliya.com');
                if ($pays) {
                    $entreprise->setPays($this->getManagedPays($pays));
                }
                
                $this->configureTraitEntity($entreprise);
                $this->em->persist($entreprise);
                $this->em->flush();
                $createdCount++;
            }

            return ['count' => $createdCount, 'message' => "$createdCount entreprise créée"];
        } catch (\Exception $e) {
            error_log("Erreur création entreprise: " . $e->getMessage());
            return ['count' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Crée les types d'utilisateurs
     */
    private function createTypeUserFixtures(): array
    {
        try {
            $typeUserRepository = $this->em->getRepository(TypeUser::class);
            $createdCount = 0;
            
            $typesData = [
                ['libelle' => 'Super Administrateur', 'code' => 'SADM'],
                ['libelle' => 'Administrateur Boutique', 'code' => 'ADB'],
                ['libelle' => 'Employé Succursale', 'code' => 'EMP'],
                ['libelle' => 'Caissier', 'code' => 'CAIS']
            ];

            foreach ($typesData as $data) {
                $existing = $typeUserRepository->findOneBy(['code' => $data['code']]);
                if (!$existing) {
                    $typeUser = new TypeUser();
                    $typeUser->setLibelle($data['libelle']);
                    $typeUser->setCode($data['code']);
                    
                    $this->configureTraitEntity($typeUser);
                    $this->em->persist($typeUser);
                    $this->em->flush();
                    $createdCount++;
                }
            }

            return ['count' => $createdCount, 'message' => "$createdCount types d'utilisateurs créés"];
        } catch (\Exception $e) {
            error_log("Erreur création types utilisateurs: " . $e->getMessage());
            return ['count' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Crée les utilisateurs de base
     */
    private function createUserFixtures(): array
    {
        try {
            $userRepository = $this->em->getRepository(User::class);
            $entrepriseRepository = $this->em->getRepository(Entreprise::class);
            $typeUserRepository = $this->em->getRepository(TypeUser::class);
            $createdCount = 0;
            
            $entreprise = $entrepriseRepository->findOneBy(['libelle' => 'Ateliya Couture']);
            $typeSuperAdmin = $typeUserRepository->findOneBy(['code' => 'SADM']);
            $typeAdmin = $typeUserRepository->findOneBy(['code' => 'ADB']);
            
            $usersData = [
                [
                    'login' => 'admin@ateliya.com',
                    'nom' => 'Admin',
                    'prenoms' => 'Super',
                    'password' => 'admin123',
                    'type' => $typeSuperAdmin
                ],
                [
                    'login' => 'manager@ateliya.com',
                    'nom' => 'Manager',
                    'prenoms' => 'Boutique',
                    'password' => 'manager123',
                    'type' => $typeAdmin
                ]
            ];

            foreach ($usersData as $data) {
                $existing = $userRepository->findOneBy(['login' => $data['login']]);
                if (!$existing) {
                    $user = new User();
                    $user->setLogin($data['login']);
                    $user->setNom($data['nom']);
                    $user->setPrenoms($data['prenoms']);
                    $user->setPassword($this->hasher->hashPassword($user, $data['password']));
                    $user->setRoles(['ROLE_USER']);
                    $user->setIsActive(true);
                    
                    if ($entreprise) {
                        $user->setEntreprise($this->getManagedEntity($entreprise));
                    }
                    if ($data['type']) {
                        $user->setType($this->getManagedEntity($data['type']));
                    }
                    
                    $this->em->persist($user);
                    $this->em->flush();
                    $createdCount++;
                }
            }

            return ['count' => $createdCount, 'message' => "$createdCount utilisateurs créés"];
        } catch (\Exception $e) {
            error_log("Erreur création utilisateurs: " . $e->getMessage());
            return ['count' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Crée les boutiques
     */
    private function createBoutiqueFixtures(): array
    {
        try {
            $boutiqueRepository = $this->em->getRepository(Boutique::class);
            $entrepriseRepository = $this->em->getRepository(Entreprise::class);
            $createdCount = 0;
            
            $entreprise = $entrepriseRepository->findOneBy(['libelle' => 'Ateliya Couture']);
            
            $boutiquesData = [
                ['libelle' => 'Boutique Cocody', 'contact' => '+225 0123456789', 'situation' => 'Cocody, Riviera'],
                ['libelle' => 'Boutique Plateau', 'contact' => '+225 0123456790', 'situation' => 'Plateau, Centre-ville'],
                ['libelle' => 'Boutique Yopougon', 'contact' => '+225 0123456791', 'situation' => 'Yopougon, Marché']
            ];

            foreach ($boutiquesData as $data) {
                $existing = $boutiqueRepository->findOneBy(['libelle' => $data['libelle']]);
                if (!$existing) {
                    $boutique = new Boutique();
                    $boutique->setLibelle($data['libelle']);
                    $boutique->setContact($data['contact']);
                    $boutique->setSituation($data['situation']);
                    
                    if ($entreprise) {
                        $boutique->setEntreprise($this->getManagedEntity($entreprise));
                    }
                    
                    $this->configureTraitEntity($boutique);
                    $this->em->persist($boutique);
                    $this->em->flush();
                    $createdCount++;
                }
            }

            return ['count' => $createdCount, 'message' => "$createdCount boutiques créées"];
        } catch (\Exception $e) {
            error_log("Erreur création boutiques: " . $e->getMessage());
            return ['count' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Crée les succursales
     */
    private function createSuccursaleFixtures(): array
    {
        try {
            $succursaleRepository = $this->em->getRepository(Surccursale::class);
            $entrepriseRepository = $this->em->getRepository(Entreprise::class);
            $createdCount = 0;
            
            $entreprise = $entrepriseRepository->findOneBy(['libelle' => 'Ateliya Couture']);
            
            $succursalesData = [
                ['libelle' => 'Succursale Adjamé', 'contact' => '+225 0123456792'],
                ['libelle' => 'Succursale Treichville', 'contact' => '+225 0123456793'],
                ['libelle' => 'Succursale Marcory', 'contact' => '+225 0123456794']
            ];

            foreach ($succursalesData as $data) {
                $existing = $succursaleRepository->findOneBy(['libelle' => $data['libelle']]);
                if (!$existing) {
                    $succursale = new Surccursale();
                    $succursale->setLibelle($data['libelle']);
                    $succursale->setContact($data['contact']);
                    
                    if ($entreprise) {
                        $succursale->setEntreprise($this->getManagedEntity($entreprise));
                    }
                    
                    $this->configureTraitEntity($succursale);
                    $this->em->persist($succursale);
                    $this->em->flush();
                    $createdCount++;
                }
            }

            return ['count' => $createdCount, 'message' => "$createdCount succursales créées"];
        } catch (\Exception $e) {
            error_log("Erreur création succursales: " . $e->getMessage());
            return ['count' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Crée les opérateurs
     */
    private function createOperateurFixtures(): array
    {
        try {
            $operateurRepository = $this->em->getRepository(Operateur::class);
            $paysRepository = $this->em->getRepository(Pays::class);
            $createdCount = 0;
            
            $pays = $paysRepository->findOneBy(['code' => 'CI']);
            
            $operateursData = [
                ['libelle' => 'Orange Money', 'code' => 'OM'],
                ['libelle' => 'MTN Mobile Money', 'code' => 'MOMO'],
                ['libelle' => 'Moov Money', 'code' => 'MM'],
                ['libelle' => 'Wave', 'code' => 'WAVE']
            ];

            foreach ($operateursData as $data) {
                $existing = $operateurRepository->findOneBy(['code' => $data['code']]);
                if (!$existing) {
                    $operateur = new Operateur();
                    $operateur->setLibelle($data['libelle']);
                    $operateur->setCode($data['code']);
                    $operateur->setActif(true); // Ajouter cette ligne
                    
                    if ($pays) {
                        $operateur->setPays($this->getManagedPays($pays));
                    }
                    
                    $this->configureTraitEntity($operateur);
                    $this->em->persist($operateur);
                    $this->em->flush();
                    $createdCount++;
                }
            }

            return ['count' => $createdCount, 'message' => "$createdCount opérateurs créés"];
        } catch (\Exception $e) {
            error_log("Erreur création opérateurs: " . $e->getMessage());
            return ['count' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Crée les caisses pour les boutiques et succursales
     */
    private function createCaisseFixtures(): array
    {
        try {
            $caisseRepository = $this->em->getRepository(Caisse::class);
            $caisseBoutiqueRepository = $this->em->getRepository(CaisseBoutique::class);
            $caisseSuccursaleRepository = $this->em->getRepository(CaisseSuccursale::class);
            $boutiqueRepository = $this->em->getRepository(Boutique::class);
            $succursaleRepository = $this->em->getRepository(Surccursale::class);
            $createdCount = 0;

            // Créer une caisse principale
            $existing = $caisseRepository->findOneBy(['reference' => 'CAISSE_PRINCIPALE']);
            if (!$existing) {
                $caisse = new Caisse();
                $caisse->setReference('CAISSE_PRINCIPALE');
                $caisse->setMontant('100000'); // 100,000 FCFA de départ
                $caisse->setType('principale');
                
                $this->configureTraitEntity($caisse);
                $this->em->persist($caisse);
                $this->em->flush();
                $createdCount++;
            }

            // Créer des caisses pour chaque boutique
            $boutiques = $boutiqueRepository->findAllInEnvironment();
            foreach ($boutiques as $boutique) {
                $existing = $caisseBoutiqueRepository->findOneBy(['boutique' => $boutique]);
                if (!$existing) {
                    $caisseBoutique = new CaisseBoutique();
                    $caisseBoutique->setBoutique($this->getManagedEntity($boutique));
                    $caisseBoutique->setReference('CAISSE_BOUTIQUE_' . $boutique->getId());
                    $caisseBoutique->setType('boutique');
                    $caisseBoutique->setMontant((string)rand(50000, 200000)); // Montant aléatoire
                    
                    $this->configureTraitEntity($caisseBoutique);
                    $this->em->persist($caisseBoutique);
                    $this->em->flush();
                    $createdCount++;
                }
            }

            // Créer des caisses pour chaque succursale
            $succursales = $succursaleRepository->findAllInEnvironment();
            foreach ($succursales as $succursale) {
                $existing = $caisseSuccursaleRepository->findOneBy(['succursale' => $succursale]);
                if (!$existing) {
                    $caisseSuccursale = new CaisseSuccursale();
                    $caisseSuccursale->setSuccursale($this->getManagedEntity($succursale));
                    $caisseSuccursale->setReference('CAISSE_SUCCURSALE_' . $succursale->getId());
                    $caisseSuccursale->setType('succursale');
                    $caisseSuccursale->setMontant((string)rand(30000, 150000)); // Montant aléatoire
                    
                    $this->configureTraitEntity($caisseSuccursale);
                    $this->em->persist($caisseSuccursale);
                    $this->em->flush();
                    $createdCount++;
                }
            }

            return ['count' => $createdCount, 'message' => "$createdCount caisses créées"];
        } catch (\Exception $e) {
            error_log("Erreur création caisses: " . $e->getMessage());
            return ['count' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Crée les modèles de vêtements
     */
    private function createModeleFixtures(): array
    {
        try {
            $modeleRepository = $this->em->getRepository(Modele::class);
            $entrepriseRepository = $this->em->getRepository(Entreprise::class);
            $createdCount = 0;
            
            $entreprise = $entrepriseRepository->findOneBy(['libelle' => 'Ateliya Couture']);
            
            $modelesData = [
                ['libelle' => 'Robe Africaine Traditionnelle'],
                ['libelle' => 'Costume Homme Moderne'],
                ['libelle' => 'Boubou Grand Boubou'],
                ['libelle' => 'Robe de Soirée Élégante'],
                ['libelle' => 'Ensemble Pagne Wax'],
                ['libelle' => 'Chemise Brodée'],
                ['libelle' => 'Pantalon Tailleur'],
                ['libelle' => 'Veste Blazer']
            ];

            foreach ($modelesData as $data) {
                $existing = $modeleRepository->findOneBy(['libelle' => $data['libelle']]);
                if (!$existing) {
                    $modele = new Modele();
                    $modele->setLibelle($data['libelle']);
                    $modele->setQuantiteGlobale(0); // Sera mis à jour lors de la création des ModeleBoutique
                    
                    if ($entreprise) {
                        $modele->setEntreprise($this->getManagedEntity($entreprise));
                    }
                    
                    $this->configureTraitEntity($modele);
                    $this->em->persist($modele);
                    $this->em->flush();
                    $createdCount++;
                }
            }

            return ['count' => $createdCount, 'message' => "$createdCount modèles créés"];
        } catch (\Exception $e) {
            error_log("Erreur création modèles: " . $e->getMessage());
            return ['count' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Crée les clients
     */
    private function createClientFixtures(): array
    {
        try {
            $clientRepository = $this->em->getRepository(Client::class);
            $boutiqueRepository = $this->em->getRepository(Boutique::class);
            $succursaleRepository = $this->em->getRepository(Surccursale::class);
            $entrepriseRepository = $this->em->getRepository(Entreprise::class);
            $createdCount = 0;
            
            $entreprise = $entrepriseRepository->findOneBy(['libelle' => 'Ateliya Couture']);
            $boutiques = $boutiqueRepository->findAll();
            $succursales = $succursaleRepository->findAll();
            
            $clientsData = [
                ['nom' => 'Kouassi', 'prenom' => 'Yao Jean', 'numero' => '+225 0701234567'],
                ['nom' => 'Traoré', 'prenom' => 'Aminata', 'numero' => '+225 0701234568'],
                ['nom' => 'Bamba', 'prenom' => 'Seydou', 'numero' => '+225 0701234569'],
                ['nom' => 'Ouattara', 'prenom' => 'Fatoumata', 'numero' => '+225 0701234570'],
                ['nom' => 'Koné', 'prenom' => 'Ibrahim', 'numero' => '+225 0701234571'],
                ['nom' => 'Diabaté', 'prenom' => 'Mariam', 'numero' => '+225 0701234572'],
                ['nom' => 'Sanogo', 'prenom' => 'Moussa', 'numero' => '+225 0701234573'],
                ['nom' => 'Doumbia', 'prenom' => 'Aïcha', 'numero' => '+225 0701234574'],
                ['nom' => 'Camara', 'prenom' => 'Mamadou', 'numero' => '+225 0701234575'],
                ['nom' => 'Fofana', 'prenom' => 'Kadiatou', 'numero' => '+225 0701234576']
            ];

            foreach ($clientsData as $data) {
                $existing = $clientRepository->findOneBy(['numero' => $data['numero']]);
                if (!$existing) {
                    $client = new Client();
                    $client->setNom($data['nom']);
                    $client->setPrenom($data['prenom']);
                    $client->setNumero($data['numero']);
                    
                    if ($entreprise) {
                        $client->setEntreprise($this->getManagedEntity($entreprise));
                    }
                    
                    // Assigner aléatoirement à une boutique ou succursale
                    if (rand(0, 1) && !empty($boutiques)) {
                        $boutique = $boutiques[array_rand($boutiques)];
                        $client->setBoutique($this->getManagedEntity($boutique));
                    } elseif (!empty($succursales)) {
                        $succursale = $succursales[array_rand($succursales)];
                        $client->setSurccursale($this->getManagedEntity($succursale));
                    }
                    
                    $this->configureTraitEntity($client);
                    $this->em->persist($client);
                    $this->em->flush();
                    $createdCount++;
                }
            }

            return ['count' => $createdCount, 'message' => "$createdCount clients créés"];
        } catch (\Exception $e) {
            error_log("Erreur création clients: " . $e->getMessage());
            return ['count' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Crée les paramètres de l'entreprise
     */
    private function createSettingFixtures(): array
    {
        try {
            $settingRepository = $this->em->getRepository(Setting::class);
            $entrepriseRepository = $this->em->getRepository(Entreprise::class);
            $createdCount = 0;
            
            $entreprise = $entrepriseRepository->findOneBy(['libelle' => 'Ateliya Couture']);
            
            $existing = $settingRepository->findOneBy(['entreprise' => $entreprise]);
            if (!$existing) {
                $setting = new Setting();
                $setting->setNombreUser(50);
                $setting->setNombreBoutique(10);
                $setting->setNombreSuccursale(20);
                
                if ($entreprise) {
                    $setting->setEntreprise($this->getManagedEntity($entreprise));
                }
                
                $this->configureTraitEntity($setting);
                $this->em->persist($setting);
                $this->em->flush();
                $createdCount++;
            }

            return ['count' => $createdCount, 'message' => "$createdCount paramètre créé"];
        } catch (\Exception $e) {
            error_log("Erreur création settings: " . $e->getMessage());
            return ['count' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Crée les abonnements
     */
    private function createAbonnementFixtures(): array
    {
        try {
            $abonnementRepository = $this->em->getRepository(Abonnement::class);
            $moduleAbonnementRepository = $this->em->getRepository(ModuleAbonnement::class);
            $entrepriseRepository = $this->em->getRepository(Entreprise::class);
            $createdCount = 0;
            
            $entreprise = $entrepriseRepository->findOneBy(['libelle' => 'Ateliya Couture']);
            
            // Créer les modules d'abonnement
            $modulesData = [
                ['description' => 'Gestion des Stocks', 'code' => 'STOCK', 'montant' => '15000', 'duree' => '12'],
                ['description' => 'Gestion des Clients', 'code' => 'CLIENT', 'montant' => '10000', 'duree' => '12'],
                ['description' => 'Gestion des Réservations', 'code' => 'RESERVATION', 'montant' => '12000', 'duree' => '12'],
                ['description' => 'Gestion Financière', 'code' => 'FINANCE', 'montant' => '20000', 'duree' => '12']
            ];

            foreach ($modulesData as $data) {
                $existing = $moduleAbonnementRepository->findOneBy(['code' => $data['code']]);
                if (!$existing) {
                    $module = new ModuleAbonnement();
                    $module->setDescription($data['description']);
                    $module->setCode($data['code']);
                    $module->setMontant($data['montant']);
                    $module->setDuree($data['duree']);
                    $module->setEtat(true);
                    
                    $this->configureTraitEntity($module);
                    $this->em->persist($module);
                    $this->em->flush();
                    $createdCount++;
                }
            }

            // Créer un abonnement actif pour l'entreprise
            $existing = $abonnementRepository->findOneBy(['entreprise' => $entreprise]);
            if (!$existing) {
                $moduleStock = $moduleAbonnementRepository->findOneBy(['code' => 'STOCK']);
                
                $abonnement = new Abonnement();
                $abonnement->setEtat('actif');
                $abonnement->setType('premium');
                $abonnement->setDateFin(new \DateTime('+11 months'));
                
                if ($entreprise) {
                    $abonnement->setEntreprise($this->getManagedEntity($entreprise));
                }
                if ($moduleStock) {
                    $abonnement->setModuleAbonnement($this->getManagedEntity($moduleStock));
                }
                
                $this->configureTraitEntity($abonnement);
                $this->em->persist($abonnement);
                $this->em->flush();
                $createdCount++;
            }

            return ['count' => $createdCount, 'message' => "$createdCount abonnements/modules créés"];
        } catch (\Exception $e) {
            error_log("Erreur création abonnements: " . $e->getMessage());
            return ['count' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Validate and prepare entity before persistence
     */
    private function validateEntityBeforePersist($entity): bool
    {
        // Validate the entity using Symfony validator
        $errors = $this->validator->validate($entity);

        if (count($errors) > 0) {
            return false;
        }

        return true;
    }


    /**
     * Génère des modèles de boutique de test
     */
    #[Route('/modele-boutique', methods: ['POST'])]
    #[OA\Post(
        path: "/api/fixture/modele-boutique",
        summary: "Générer des modèles de boutique de test",
        description: "Crée automatiquement des associations modèle-boutique avec des données de test pour le développement. Associe tous les modèles existants à toutes les boutiques avec des prix et quantités aléatoires.",
        tags: ['fixture']
    )]
    #[OA\Response(
        response: 201,
        description: "Fixtures créées avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "message", type: "string", example: "25 modèles de boutique créés avec succès"),
                new OA\Property(property: "count", type: "integer", example: 25),
                new OA\Property(property: "modeles_boutique", type: "array", items: new OA\Items(type: "object"))
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 500, description: "Erreur lors de la création")]
    public function createModeleBoutiqueFixtures(
        ModeleRepository $modeleRepository,
        BoutiqueRepository $boutiqueRepository,
        ModeleBoutiqueRepository $modeleBoutiqueRepository,
        EntityManagerProvider $entityManagerProvider
    ): Response {
        try {
            $modeles = $modeleRepository->findAllInEnvironment();
            $boutiques = $boutiqueRepository->findAllInEnvironment();
            $createdCount = 0;
            $createdModelesBoutique = [];

            if (empty($modeles) || empty($boutiques)) {
                return $this->createCustomErrorResponse("Aucun modèle ou boutique trouvé pour créer les fixtures", 400);
            }

            $tailles = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
            $prixBase = [8000, 12000, 15000, 18000, 22000, 25000, 30000];

            $entityManager = $entityManagerProvider->getEntityManager();
            $batchSize = 20; // Traiter par batch pour optimiser les performances

            foreach ($modeles as $modele) {
                foreach ($boutiques as $boutique) {
                    // Vérifier si l'association existe déjà
                    $existing = $modeleBoutiqueRepository->findOneByInEnvironment([
                        'modele' => $modele,
                        'boutique' => $boutique
                    ]);

                    if ($existing == null) {
                        try {
                            $modeleBoutique = new ModeleBoutique();
                            $modeleBoutique->setPrix($prixBase[array_rand($prixBase)]);
                            $modeleBoutique->setQuantite(rand(10, 100));
                            $modeleBoutique->setBoutique($boutique);
                            $modeleBoutique->setModele($modele);
                            $modeleBoutique->setIsActive(true);
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
                            if (!$this->validateEntityBeforePersist($modeleBoutique)) {
                                error_log("Validation échouée pour ModeleBoutique");
                                continue;
                            }

                            // Mise à jour de la quantité globale du modèle
                            $modele->setQuantiteGlobale((int)$modele->getQuantiteGlobale() + (int)$modeleBoutique->getQuantite());

                            // Utiliser saveInEnvironment qui prend en compte l'environnement dev/prod
                            // Sauvegarder sans flush pour optimiser les performances
                            $modeleRepository->saveInEnvironment($modele, false);
                            $modeleBoutiqueRepository->saveInEnvironment($modeleBoutique, false);

                            $createdModelesBoutique[] = $modeleBoutique;
                            $createdCount++;

                            // Flush par batch pour optimiser les performances
                            if ($createdCount % $batchSize === 0) {
                                $entityManager->flush();
                                error_log("Batch de $batchSize ModeleBoutique sauvegardés (total: $createdCount)");
                            }
                        } catch (\Exception $e) {
                            // Log the error for debugging
                            error_log("Erreur lors de la création du ModeleBoutique: " . $e->getMessage());
                            error_log("Stack trace: " . $e->getTraceAsString());
                            continue;
                        }
                    }
                }
            }

            // Flush final pour sauvegarder les entités restantes
            if ($createdCount % $batchSize !== 0) {
                $entityManager->flush();
                error_log("Flush final - Total ModeleBoutique créés: $createdCount");
            }

            return $this->responseData([
                'message' => "$createdCount modèles de boutique créés avec succès",
                'count' => $createdCount,
                'modeles_boutique' => $createdModelesBoutique
            ], 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $e) {
            error_log("Erreur générale dans createModeleBoutiqueFixtures: " . $e->getMessage());
            return $this->createCustomErrorResponse("Erreur lors de la création des fixtures: " . $e->getMessage(), 500);
        }
    }

    /**
     * Génère des réservations et paiements de test
     */
    #[Route('/reservations', methods: ['POST'])]
    #[OA\Post(
        path: "/api/fixture/reservations",
        summary: "Générer des réservations de test",
        description: "Crée automatiquement des réservations avec leurs paiements pour le développement. Génère des données réalistes avec différents montants d'avance.",
        tags: ['fixture']
    )]
    #[OA\Response(
        response: 201,
        description: "Fixtures créées avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "message", type: "string", example: "10 réservations créées avec succès"),
                new OA\Property(property: "count", type: "integer", example: 10),
                new OA\Property(property: "reservations", type: "array", items: new OA\Items(type: "object"))
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 500, description: "Erreur lors de la création")]
    public function createReservationFixtures(
        ClientRepository $clientRepository,
        BoutiqueRepository $boutiqueRepository,
        ModeleBoutiqueRepository $modeleBoutiqueRepository,
        CaisseBoutiqueRepository $caisseBoutiqueRepository,
        \App\Repository\ReservationRepository $reservationRepository,
        Utils $utils,
        EntityManagerProvider $entityManagerProvider
    ): Response {
        try {
            $clients = $clientRepository->findAllInEnvironment();
            $boutiques = $boutiqueRepository->findAllInEnvironment();
            $createdCount = 0;
            $createdReservations = [];

            // Debug: Vérifier les données de base
            error_log("=== DEBUG createReservationFixtures ===");
            error_log("Nombre de clients trouvés: " . count($clients));
            error_log("Nombre de boutiques trouvées: " . count($boutiques));

            if (empty($clients) || empty($boutiques)) {
                error_log("ERREUR: Pas assez de données de base - Clients: " . count($clients) . ", Boutiques: " . count($boutiques));
                return $this->createCustomErrorResponse("Aucun client ou boutique trouvé pour créer les fixtures", 400);
            }

            $entityManager = $entityManagerProvider->getEntityManager();

            // Créer 10 réservations de test
            for ($i = 0; $i < 10; $i++) {
                $client = $clients[array_rand($clients)];
                $boutique = $boutiques[array_rand($boutiques)];

                // Récupérer des modèles disponibles pour cette boutique
                $modeleBoutiques = $modeleBoutiqueRepository->findByInEnvironment(['boutique' => $boutique]);
                
                error_log("Tentative $i - Boutique ID: " . $boutique->getId() . ", Modèles trouvés: " . count($modeleBoutiques));
                
                if (empty($modeleBoutiques)) {
                    error_log("Aucun modèle trouvé pour la boutique ID: " . $boutique->getId());
                    continue;
                }

                try {
                    // Montants aléatoires
                    $montant = rand(15000, 50000);
                    $avance = rand(5000, $montant);
                    $reste = $montant - $avance;

                    // Date de retrait future
                    $dateRetrait = new \DateTime();
                    $dateRetrait->add(new \DateInterval('P' . rand(7, 30) . 'D'));

                    // Créer la réservation
                    $reservation = new Reservation();
                    $reservation->setAvance($avance);
                    $reservation->setDateRetrait($dateRetrait);
                    $reservation->setClient($client);
                    $reservation->setBoutique($boutique);
                    /** @var User $user */
                    $user = $this->getUser();
                    if ($user && $user->getEntreprise()) {
                        $reservation->setEntreprise($user->getEntreprise());
                        error_log("Entreprise assignée: " . $user->getEntreprise()->getId());
                    } else {
                        error_log("ATTENTION: Pas d'entreprise pour l'utilisateur");
                    }
                    $reservation->setMontant($montant);
                    $reservation->setReste($reste);
                    $reservation->setCreatedAtValue();
                    $reservation->setUpdatedAt();

                    // Get managed user for persistence
                    $managedUser = $this->getManagedUser();
                    if ($managedUser) {
                        $reservation->setCreatedBy($managedUser);
                        $reservation->setUpdatedBy($managedUser);
                        error_log("Utilisateur géré assigné: " . $managedUser->getId());
                    } else {
                        error_log("ERREUR: Pas d'utilisateur géré trouvé");
                    }

                    // Validate entity before persistence
                    if (!$this->validateEntityBeforePersist($reservation)) {
                        error_log("ERREUR: Validation échouée pour Reservation");
                        $errors = $this->validator->validate($reservation);
                        foreach ($errors as $error) {
                            error_log("Erreur de validation: " . $error->getPropertyPath() . " - " . $error->getMessage());
                        }
                        continue;
                    }

                    error_log("Validation réussie pour la réservation");

                    // Utiliser saveInEnvironment pour la réservation (sans flush)
                    $reservationRepository->saveInEnvironment($reservation, false);
                    error_log("Réservation sauvegardée (sans flush)");

                    // Ajouter 1-3 lignes de réservation
                    $nbLignes = rand(1, 3);
                    $lignesCreees = 0;
                    
                    for ($j = 0; $j < $nbLignes; $j++) {
                        $modeleBoutique = $modeleBoutiques[array_rand($modeleBoutiques)];
                        if ($modeleBoutique->getQuantite() <= 0) {
                            error_log("Modèle sans stock: " . $modeleBoutique->getId());
                            continue;
                        }

                        $quantite = rand(1, min(3, $modeleBoutique->getQuantite()));
                        $avanceModele = rand(2000, 8000);

                        $ligne = new LigneReservation();
                        $ligne->setQuantite($quantite);
                        $ligne->setModele($modeleBoutique);
                        $ligne->setIsActive(true);
                        $ligne->setAvanceModele($avanceModele);
                        $ligne->setCreatedAtValue();
                        $ligne->setUpdatedAt();

                        // Use the same managed user for ligne entities
                        if ($managedUser) {
                            $ligne->setCreatedBy($managedUser);
                            $ligne->setUpdatedBy($managedUser);
                        }

                        $reservation->addLigneReservation($ligne);
                        $entityManager->persist($ligne);

                        // Réduire le stock - utiliser saveInEnvironment
                        $modeleBoutique->setQuantite($modeleBoutique->getQuantite() - $quantite);
                        $modele = $modeleBoutique->getModele();
                        if ($modele && $modele->getQuantiteGlobale() >= $quantite) {
                            $modele->setQuantiteGlobale($modele->getQuantiteGlobale() - $quantite);
                        }

                        // Sauvegarder les modifications de stock (sans flush)
                        $modeleBoutiqueRepository->saveInEnvironment($modeleBoutique, false);
                        $lignesCreees++;
                    }

                    error_log("Lignes de réservation créées: $lignesCreees");

                    // Créer le paiement si avance > 0
                    if ($avance > 0) {
                        $paiementReservation = new PaiementReservation();
                        $paiementReservation->setReservation($reservation);
                        $paiementReservation->setType('paiementReservation');
                        $paiementReservation->setMontant($avance);
                                $paiementReservation->setIsActive(true);
                        $paiementReservation->setReference($utils->generateReference('PMT'));
                        $paiementReservation->setCreatedAtValue();
                        $paiementReservation->setUpdatedAt();

                        // Use the same managed user for paiement entities
                        if ($managedUser) {
                            $paiementReservation->setCreatedBy($managedUser);
                            $paiementReservation->setUpdatedBy($managedUser);
                        }

                        $entityManager->persist($paiementReservation);
                        error_log("Paiement créé: " . $paiementReservation->getReference());

                        // Mettre à jour la caisse - utiliser saveInEnvironment
                        $caisseBoutique = $caisseBoutiqueRepository->findOneByInEnvironment(['boutique' => $boutique]);
                        if ($caisseBoutique) {
                            $caisseBoutique->setMontant($caisseBoutique->getMontant() + $avance);
                            if ($managedUser) {
                                $caisseBoutique->setUpdatedBy($managedUser);
                            }
                            $caisseBoutique->setUpdatedAt();

                            // Sauvegarder la caisse (sans flush)
                            $caisseBoutiqueRepository->saveInEnvironment($caisseBoutique, false);
                            error_log("Caisse mise à jour");
                        } else {
                            error_log("ATTENTION: Pas de caisse trouvée pour la boutique ID: " . $boutique->getId());
                        }
                    }

                    // Flush final pour cette réservation
                    $entityManager->flush();
                    error_log("Flush effectué pour la réservation");

                    $createdReservations[] = $reservation;
                    $createdCount++;

                    error_log("✅ Réservation $createdCount créée avec succès (ID: " . $reservation->getId() . ")");
                } catch (\Exception $e) {
                    error_log("❌ Erreur lors de la création de la réservation $i: " . $e->getMessage());
                    error_log("Stack trace: " . $e->getTraceAsString());
                    continue;
                }
            }

            error_log("=== FIN DEBUG - Total créé: $createdCount ===");

            return $this->responseData([
                'message' => "$createdCount réservations créées avec succès",
                'count' => $createdCount,
                'reservations' => $createdReservations
            ], 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $e) {
            error_log("❌ Erreur générale dans createReservationFixtures: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return $this->createCustomErrorResponse("Erreur lors de la création des fixtures: " . $e->getMessage(), 500);
        }
    }

    /**
     * Génère des entrées de stock de test
     */
    #[Route('/entrees-stock', methods: ['POST'])]
    #[OA\Post(
        path: "/api/fixture/entrees-stock",
        summary: "Générer des entrées de stock de test",
        description: "Crée automatiquement des entrées de stock avec leurs lignes pour le développement. Génère des mouvements d'entrée réalistes avec quantités aléatoires.",
        tags: ['fixture']
    )]
    #[OA\Response(
        response: 201,
        description: "Fixtures créées avec succès",
        content: new OA\JsonContent(
            type: "object",
            properties: [
                new OA\Property(property: "message", type: "string", example: "8 entrées de stock créées avec succès"),
                new OA\Property(property: "count", type: "integer", example: 8),
                new OA\Property(property: "entrees_stock", type: "array", items: new OA\Items(type: "object"))
            ]
        )
    )]
    #[OA\Response(response: 401, description: "Non authentifié")]
    #[OA\Response(response: 500, description: "Erreur lors de la création")]
    public function createEntreeStockFixtures(
        BoutiqueRepository $boutiqueRepository,
        ModeleBoutiqueRepository $modeleBoutiqueRepository,
        EntityManagerProvider $entityManagerProvider,
        EntreStockRepository $entreStockRepository
    ): Response {
        try {
            $boutiques = $boutiqueRepository->findAllInEnvironment();
            $createdCount = 0;
            $createdEntrees = [];

            if (empty($boutiques)) {
                return $this->createCustomErrorResponse("Aucune boutique trouvée pour créer les fixtures", 400);
            }

            $entityManager = $entityManagerProvider->getEntityManager();

            // Créer 8 entrées de stock de test
            for ($i = 0; $i < 8; $i++) {
                $boutique = $boutiques[array_rand($boutiques)];

                // Récupérer des modèles disponibles pour cette boutique
                $modeleBoutiques = $modeleBoutiqueRepository->findByInEnvironment(['boutique' => $boutique]);
                if (empty($modeleBoutiques)) continue;

                try {
                    // Créer l'entrée de stock
                    $entreStock = new EntreStock();
                    $entreStock->setBoutique($boutique);
                    $entreStock->setType('Entree');
                    /** @var User $user */
                    $user = $this->getUser();
                    if ($user && $user->getEntreprise()) {
                        $entreStock->setEntreprise($user->getEntreprise());
                    }

                    // Get managed user for persistence
                    $managedUser = $this->getManagedUser();
                    if ($managedUser) {
                        $entreStock->setCreatedBy($managedUser);
                        $entreStock->setUpdatedBy($managedUser);
                    }
                    $entreStock->setCreatedAtValue();
                    $entreStock->setUpdatedAt();

                    // Validate entity before persistence
                    if (!$this->validateEntityBeforePersist($entreStock)) {
                        error_log("Validation échouée pour EntreStock");
                        continue;
                    }

                    // Persister l'entrée de stock
                    $entreStockRepository->saveInEnvironment($entreStock,false);

                    // Ajouter 2-5 lignes d'entrée
                    $nbLignes = rand(2, 5);
                    $totalQuantite = 0;

                    for ($j = 0; $j < $nbLignes; $j++) {
                        $modeleBoutique = $modeleBoutiques[array_rand($modeleBoutiques)];
                        $quantite = rand(20, 100);
                        $totalQuantite += $quantite;

                        $ligneEntre = new LigneEntre();
                        $ligneEntre->setQuantite($quantite);
                        $ligneEntre->setModele($modeleBoutique);
                        $ligneEntre->setEntreStock($entreStock);

                        $entityManager->persist($ligneEntre);
                        $entreStock->addLigneEntre($ligneEntre);

                        // Mettre à jour les stocks - utiliser saveInEnvironment
                        $modeleBoutique->setQuantite($modeleBoutique->getQuantite() + $quantite);
                        $modele = $modeleBoutique->getModele();
                        $modele->setQuantiteGlobale($modele->getQuantiteGlobale() + $quantite);

                        // Sauvegarder les modifications de stock (sans flush)
                        $modeleBoutiqueRepository->saveInEnvironment($modeleBoutique, false);
                    }

                    $entreStock->setQuantite($totalQuantite);

                    // Flush final pour cette entrée de stock
                    $entityManager->flush();

                    $createdEntrees[] = $entreStock;
                    $createdCount++;

                    error_log("EntreStock $createdCount créée avec succès (quantité: $totalQuantite)");
                } catch (\Exception $e) {
                    error_log("Erreur lors de la création de l'EntreStock: " . $e->getMessage());
                    error_log("Stack trace: " . $e->getTraceAsString());
                    continue;
                }
            }

            return $this->responseData([
                'message' => "$createdCount entrées de stock créées avec succès",
                'count' => $createdCount,
                'entrees_stock' => $createdEntrees
            ], 'group1', ['Content-Type' => 'application/json']);
        } catch (\Exception $e) {
            error_log("Erreur générale dans createEntreeStockFixtures: " . $e->getMessage());
            return $this->createCustomErrorResponse("Erreur lors de la création des fixtures: " . $e->getMessage(), 500);
        }
}

    /**
     * Vérifie les données de base nécessaires pour les fixtures
     */
    #[Route('/check-data', methods: ['GET'])]
    #[OA\Get(
        path: "/api/fixture/check-data",
        summary: "Vérifier les données de base",
        description: "Vérifie la présence des données nécessaires pour créer les fixtures (clients, boutiques, modèles, etc.)",
        tags: ['fixture']
    )]
    public function checkData(
        ClientRepository $clientRepository,
        BoutiqueRepository $boutiqueRepository,
        ModeleBoutiqueRepository $modeleBoutiqueRepository,
        \App\Repository\ModeleRepository $modeleRepository
    ): Response {
        try {
            $clients = $clientRepository->findAllInEnvironment();
            $boutiques = $boutiqueRepository->findAllInEnvironment();
            $modeles = $modeleRepository->findAllInEnvironment();
            $modeleBoutiques = $modeleBoutiqueRepository->findAllInEnvironment();

            $data = [
                'clients' => [
                    'count' => count($clients),
                    'sample' => array_slice($clients, 0, 3)
                ],
                'boutiques' => [
                    'count' => count($boutiques),
                    'sample' => array_slice($boutiques, 0, 3)
                ],
                'modeles' => [
                    'count' => count($modeles),
                    'sample' => array_slice($modeles, 0, 3)
                ],
                'modele_boutiques' => [
                    'count' => count($modeleBoutiques),
                    'sample' => array_slice($modeleBoutiques, 0, 3)
                ]
            ];

            // Vérifier les modèles par boutique
            $modelesParBoutique = [];
            foreach ($boutiques as $boutique) {
                $modelesForBoutique = $modeleBoutiqueRepository->findByInEnvironment(['boutique' => $boutique]);
                $modelesParBoutique[$boutique->getId()] = count($modelesForBoutique);
            }
            $data['modeles_par_boutique'] = $modelesParBoutique;

            return $this->responseData($data, 'group1', ['Content-Type' => 'application/json']);

        } catch (\Exception $e) {
            return $this->createCustomErrorResponse("Erreur lors de la vérification: " . $e->getMessage(), 500);
        }
    }
}
