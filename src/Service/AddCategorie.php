<?php



namespace App\Service;

use App\Entity\CategorieMesure;
use App\Entity\CategorieTypeMesure;
use App\Entity\Entreprise;
use App\Entity\LigneModule;
use App\Entity\Setting;
use App\Entity\TypeMesure;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AddCategorie
{


    public function __construct(private EntityManagerInterface $entityManager) {}

    public function setParametreForEntreprise(User $user): void
    {
        $entreprise = $user->getEntreprise();


        $typeLibelles = ['veste', 'pantalon'];
        $types = [];

        foreach ($typeLibelles as $libelle) {
            $type = new TypeMesure();
            $type->setLibelle($libelle);
            $type->setEntreprise($entreprise);
            $type->setCreatedAtValue(new \DateTime());
            $type->setCreatedBy($user);
            $this->entityManager->persist($type);
            $types[$libelle] = $type;
        }

        $categorieLibelles = ['largeur', 'longueur', 'ceinture'];
        $categories = [];

        foreach ($categorieLibelles as $libelle) {
            $categorie = new CategorieMesure();
            $categorie->setLibelle($libelle);
            $categorie->setEntreprise($entreprise);
            $categorie->setCreatedAtValue(new \DateTime());
            $categorie->setCreatedBy($user);
            $this->entityManager->persist($categorie);
            $categories[$libelle] = $categorie;
        }

        $categorieType = new CategorieTypeMesure();
        $categorieType->setTypeMesure($types['veste']);
        $categorieType->setCreatedAtValue(new \DateTime());
        $categorieType->setCreatedBy($user);
        $categorieType->setCategorieMesure($categories['largeur']);
        $this->entityManager->persist($categorieType);

        $this->entityManager->flush();
    }

    public function initializeCategorieTypeMesureForEntreprise(Entreprise $entreprise, User $user): void
    {
        $typesMesures = $this->entityManager->getRepository(TypeMesure::class)->findAll();

        $categoriesMesures = $this->entityManager->getRepository(CategorieMesure::class)->findAll();

        $associations = [
            // Costume
            'costume' => ['épaule', 'poitrine', 'taille', 'longueur dos', 'longueur manche', 'longueur pantalon', 'ceinture'],

            // Chemise homme
            'chemise homme' => ['épaule', 'poitrine', 'taille', 'longueur manche', 'encolure', 'tour de cou'],

            // Pantalon homme
            'pantalon homme' => ['taille', 'hanches', 'longueur pantalon', 'tour de cuisse', 'fourche', 'ceinture', 'longueur entrejambe'],

            // Robe
            'robe' => ['épaule', 'poitrine', 'taille', 'hanches', 'longueur jupe', 'hauteur poitrine'],

            // Jupe
            'jupe' => ['taille', 'hanches', 'longueur jupe', 'tour de jupe', 'ceinture'],

            // Boubou homme
            'boubou homme' => ['épaule', 'poitrine', 'longueur dos', 'longueur manche', 'largeur dos'],

            // Boubou femme
            'boubou femme' => ['épaule', 'poitrine', 'longueur dos', 'longueur manche', 'hauteur poitrine'],

            // Kaba
            'kaba' => ['épaule', 'poitrine', 'taille', 'hanches', 'longueur jupe', 'hauteur poitrine'],

            // T-shirt
            't-shirt' => ['épaule', 'poitrine', 'longueur manche', 'encolure'],

            // Jean
            'jean' => ['taille', 'hanches', 'longueur pantalon', 'tour de cuisse', 'fourche', 'ceinture']
        ];

        $associationsCreated = 0;

        // Créer les associations CategorieTypeMesure
        foreach ($associations as $typeLibelle => $categorieLibelles) {
            $typeMesure = $this->findTypeByLibelle($typesMesures, $typeLibelle);
            if (!$typeMesure) continue;

            foreach ($categorieLibelles as $categorieLibelle) {
                $categorieMesure = $this->findCategorieByLibelle($categoriesMesures, $categorieLibelle);
                if (!$categorieMesure) continue;

                // Vérifier si l'association existe déjà
                $existing = $this->entityManager->getRepository(CategorieTypeMesure::class)
                    ->findOneBy([
                        'typeMesure' => $typeMesure,
                        'categorieMesure' => $categorieMesure,
                        'entreprise' => $entreprise
                    ]);

                if (!$existing) {
                    $categorieType = new CategorieTypeMesure();
                    $categorieType->setTypeMesure($typeMesure);
                    $categorieType->setCategorieMesure($categorieMesure);
                    $categorieType->setEntreprise($entreprise);
                    $categorieType->setCreatedAtValue(new \DateTime());
                    $categorieType->setCreatedBy($user);
                    $categorieType->setIsActive(true);

                    $this->entityManager->persist($categorieType);
                    $associationsCreated++;
                }
            }
        }

        $this->entityManager->flush();

    
    }

    private function findTypeByLibelle(array $types, string $libelle): ?TypeMesure
    {
        foreach ($types as $type) {
            if ($type->getLibelle() === $libelle) {
                return $type;
            }
        }
        return null;
    }

    private function findCategorieByLibelle(array $categories, string $libelle): ?CategorieMesure
    {
        foreach ($categories as $categorie) {
            if ($categorie->getLibelle() === $libelle) {
                return $categorie;
            }
        }
        return null;
    }


    public function setting(Entreprise $entreprise, $data = []): void
    {


        $setting = new Setting();
        $setting->setEntreprise($entreprise);
        $setting->setNombreSms($data['sms']);
        $setting->setNombreSuccursale($data['succursale']);
        $setting->setNombreBoutique($data['boutique']);
        $setting->setNombreUser($data['user']);
        $setting->setNumeroAbonnement($data['numero']);
        $setting->setNombreJourRestantPourEnvoyerSms(10);
        $setting->setModeleMessageEnvoyerPourRendezVousProche("Bonjour, ceci est un rappel pour votre rendez-vous prévu prochainement dans 10 jours, merci de vous présenter à l’heure.");
        $setting->isSendMesssageAutomaticIfRendezVousProche(false);
        $this->entityManager->persist($setting);
        $this->entityManager->flush();
    }
}
