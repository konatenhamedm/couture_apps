<?php

namespace App\Tests\Controller\ApiClient\Fixtures;

use App\Entity\Client;
use App\Entity\Boutique;
use App\Entity\Surccursale;
use App\Entity\Entreprise;
use App\Entity\User;
use App\Entity\TypeUser;
use App\Entity\Abonnement;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Database seeder for consistent test data across test runs
 */
class DatabaseSeeder
{
    private EntityManagerInterface $entityManager;
    private array $seededData = [];
    
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    
    /**
     * Seed all test data
     */
    public function seedAll(): array
    {
        $this->seedEntreprises();
        $this->seedUserTypes();
        $this->seedBoutiques();
        $this->seedSuccursales();
        $this->seedUsers();
        $this->seedAbonnements();
        $this->seedClients();
        
        $this->entityManager->flush();
        
        return $this->seededData;
    }
    
    /**
     * Seed entreprises
     */
    public function seedEntreprises(): void
    {
        $entreprises = [
            [
                'nom' => 'Entreprise Test 1',
                'email' => 'test1@entreprise.com',
                'telephone' => '+225 0123456789',
                'adresse' => '123 Test Street, Abidjan'
            ],
            [
                'nom' => 'Entreprise Test 2',
                'email' => 'test2@entreprise.com',
                'telephone' => '+225 0987654321',
                'adresse' => '456 Business Avenue, Abidjan'
            ]
        ];
        
        $this->seededData['entreprises'] = [];
        
        foreach ($entreprises as $data) {
            $entreprise = new Entreprise();
            $entreprise->setNom($data['nom']);
            $entreprise->setEmail($data['email']);
            $entreprise->setTelephone($data['telephone']);
            $entreprise->setAdresse($data['adresse']);
            $entreprise->setIsActive(true);
            
            $this->entityManager->persist($entreprise);
            $this->seededData['entreprises'][] = $entreprise;
        }
    }
    
    /**
     * Seed user types
     */
    public function seedUserTypes(): void
    {
        $userTypes = [
            ['code' => 'SADM', 'libelle' => 'Super Administrateur'],
            ['code' => 'ADB', 'libelle' => 'Administrateur Boutique'],
            ['code' => 'REG', 'libelle' => 'Utilisateur Régulier'],
            ['code' => 'EMP', 'libelle' => 'Employé'],
            ['code' => 'CLI', 'libelle' => 'Client']
        ];
        
        $this->seededData['userTypes'] = [];
        
        foreach ($userTypes as $data) {
            foreach ($this->seededData['entreprises'] as $entreprise) {
                $userType = new TypeUser();
                $userType->setCode($data['code']);
                $userType->setLibelle($data['libelle']);
                $userType->setEntreprise($entreprise);
                $userType->setIsActive(true);
                
                $this->entityManager->persist($userType);
                $this->seededData['userTypes'][$data['code']][] = $userType;
            }
        }
    }
    
    /**
     * Seed boutiques
     */
    public function seedBoutiques(): void
    {
        $boutiques = [
            [
                'nom' => 'Boutique Centre-Ville',
                'adresse' => '789 Centre Street, Abidjan',
                'telephone' => '+225 0555111222'
            ],
            [
                'nom' => 'Boutique Cocody',
                'adresse' => '321 Cocody Boulevard, Abidjan',
                'telephone' => '+225 0555333444'
            ],
            [
                'nom' => 'Boutique Yopougon',
                'adresse' => '654 Yopougon Avenue, Abidjan',
                'telephone' => '+225 0555555666'
            ]
        ];
        
        $this->seededData['boutiques'] = [];
        
        foreach ($this->seededData['entreprises'] as $entreprise) {
            foreach ($boutiques as $data) {
                $boutique = new Boutique();
                $boutique->setNom($data['nom']);
                $boutique->setAdresse($data['adresse']);
                $boutique->setTelephone($data['telephone']);
                $boutique->setEntreprise($entreprise);
                $boutique->setIsActive(true);
                
                $this->entityManager->persist($boutique);
                $this->seededData['boutiques'][] = $boutique;
            }
        }
    }
    
    /**
     * Seed succursales
     */
    public function seedSuccursales(): void
    {
        $succursales = [
            [
                'nom' => 'Succursale A',
                'adresse' => '111 Succursale A Street',
                'telephone' => '+225 0666111222'
            ],
            [
                'nom' => 'Succursale B',
                'adresse' => '222 Succursale B Avenue',
                'telephone' => '+225 0666333444'
            ]
        ];
        
        $this->seededData['succursales'] = [];
        
        foreach ($this->seededData['boutiques'] as $boutique) {
            foreach ($succursales as $data) {
                $succursale = new Surccursale();
                $succursale->setNom($data['nom']);
                $succursale->setAdresse($data['adresse']);
                $succursale->setTelephone($data['telephone']);
                $succursale->setBoutique($boutique);
                $succursale->setEntreprise($boutique->getEntreprise());
                $succursale->setIsActive(true);
                
                $this->entityManager->persist($succursale);
                $this->seededData['succursales'][] = $succursale;
            }
        }
    }
    
    /**
     * Seed users
     */
    public function seedUsers(): void
    {
        $this->seededData['users'] = [];
        
        foreach ($this->seededData['entreprises'] as $entrepriseIndex => $entreprise) {
            // Super Admin
            $sadmType = $this->seededData['userTypes']['SADM'][$entrepriseIndex];
            $superAdmin = new User();
            $superAdmin->setEmail("sadm{$entrepriseIndex}@test.com");
            $superAdmin->setNom('Super');
            $superAdmin->setPrenom('Admin');
            $superAdmin->setType($sadmType);
            $superAdmin->setEntreprise($entreprise);
            $superAdmin->setIsActive(true);
            
            $this->entityManager->persist($superAdmin);
            $this->seededData['users']['sadm'][] = $superAdmin;
            
            // Boutique Admins
            $adbType = $this->seededData['userTypes']['ADB'][$entrepriseIndex];
            foreach ($this->seededData['boutiques'] as $boutiqueIndex => $boutique) {
                if ($boutique->getEntreprise() === $entreprise) {
                    $boutiqueAdmin = new User();
                    $boutiqueAdmin->setEmail("adb{$entrepriseIndex}_{$boutiqueIndex}@test.com");
                    $boutiqueAdmin->setNom('Boutique');
                    $boutiqueAdmin->setPrenom("Admin{$boutiqueIndex}");
                    $boutiqueAdmin->setType($adbType);
                    $boutiqueAdmin->setBoutique($boutique);
                    $boutiqueAdmin->setEntreprise($entreprise);
                    $boutiqueAdmin->setIsActive(true);
                    
                    $this->entityManager->persist($boutiqueAdmin);
                    $this->seededData['users']['adb'][] = $boutiqueAdmin;
                }
            }
            
            // Regular Users
            $regType = $this->seededData['userTypes']['REG'][$entrepriseIndex];
            foreach ($this->seededData['succursales'] as $succursaleIndex => $succursale) {
                if ($succursale->getEntreprise() === $entreprise) {
                    $regularUser = new User();
                    $regularUser->setEmail("reg{$entrepriseIndex}_{$succursaleIndex}@test.com");
                    $regularUser->setNom('Regular');
                    $regularUser->setPrenom("User{$succursaleIndex}");
                    $regularUser->setType($regType);
                    $regularUser->setSurccursale($succursale);
                    $regularUser->setBoutique($succursale->getBoutique());
                    $regularUser->setEntreprise($entreprise);
                    $regularUser->setIsActive(true);
                    
                    $this->entityManager->persist($regularUser);
                    $this->seededData['users']['reg'][] = $regularUser;
                }
            }
        }
    }
    
    /**
     * Seed abonnements
     */
    public function seedAbonnements(): void
    {
        $this->seededData['abonnements'] = [];
        
        foreach ($this->seededData['entreprises'] as $index => $entreprise) {
            $abonnement = new Abonnement();
            $abonnement->setEntreprise($entreprise);
            $abonnement->setIsActive($index === 0); // First entreprise has active subscription
            $abonnement->setDateDebut(new \DateTime('-1 month'));
            $abonnement->setDateFin(new \DateTime('+1 year'));
            
            $this->entityManager->persist($abonnement);
            $this->seededData['abonnements'][] = $abonnement;
        }
    }
    
    /**
     * Seed clients
     */
    public function seedClients(): void
    {
        $clientsData = [
            ['nom' => 'Kouassi', 'prenom' => 'Yao', 'numero' => '+225 0701234567'],
            ['nom' => 'Kone', 'prenom' => 'Fatou', 'numero' => '+225 0702345678'],
            ['nom' => 'Traore', 'prenom' => 'Moussa', 'numero' => '+225 0703456789'],
            ['nom' => 'Ouattara', 'prenom' => 'Aminata', 'numero' => '+225 0704567890'],
            ['nom' => 'Bamba', 'prenom' => 'Ibrahim', 'numero' => '+225 0705678901']
        ];
        
        $this->seededData['clients'] = [];
        
        foreach ($this->seededData['succursales'] as $succursale) {
            foreach ($clientsData as $index => $data) {
                $client = new Client();
                $client->setNom($data['nom']);
                $client->setPrenom($data['prenom']);
                $client->setNumero($data['numero'] . $index);
                $client->setSurccursale($succursale);
                $client->setBoutique($succursale->getBoutique());
                $client->setEntreprise($succursale->getEntreprise());
                $client->setIsActive(true);
                
                $this->entityManager->persist($client);
                $this->seededData['clients'][] = $client;
            }
        }
    }
    
    /**
     * Get seeded data
     */
    public function getSeededData(): array
    {
        return $this->seededData;
    }
    
    /**
     * Get random client
     */
    public function getRandomClient(): ?Client
    {
        if (empty($this->seededData['clients'])) {
            return null;
        }
        
        return $this->seededData['clients'][array_rand($this->seededData['clients'])];
    }
    
    /**
     * Get random boutique
     */
    public function getRandomBoutique(): ?Boutique
    {
        if (empty($this->seededData['boutiques'])) {
            return null;
        }
        
        return $this->seededData['boutiques'][array_rand($this->seededData['boutiques'])];
    }
    
    /**
     * Get random succursale
     */
    public function getRandomSuccursale(): ?Surccursale
    {
        if (empty($this->seededData['succursales'])) {
            return null;
        }
        
        return $this->seededData['succursales'][array_rand($this->seededData['succursales'])];
    }
    
    /**
     * Get user by type
     */
    public function getUserByType(string $type): ?User
    {
        if (empty($this->seededData['users'][$type])) {
            return null;
        }
        
        return $this->seededData['users'][$type][0];
    }
    
    /**
     * Clear all seeded data
     */
    public function clear(): void
    {
        $this->seededData = [];
    }
}