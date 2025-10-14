<?php

namespace App\Service;

use App\Entity\Abonnement;
use App\Entity\Entreprise;
use App\Entity\Setting;
use App\Entity\User;
use App\Repository\AbonnementRepository;
use App\Repository\BoutiqueRepository;
use App\Repository\SettingRepository;
use App\Repository\SurccursaleRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SubscriptionChecker
{
    public function __construct(
        private AbonnementRepository $abonnementRepository,
        private SettingRepository $settingRepository,
        private BoutiqueRepository $boutiqueRepository,
        private UserRepository $userRepository,
        private SurccursaleRepository $surccursaleRepository

    ) {}

    public function getInactiveSubscriptions(Entreprise $entreprise)
    {
        return $this->abonnementRepository->findOneBy([
            'entreprise' => $entreprise,
            'etat' => 'inactif'
        ], ['dateFin' => 'DESC']);
    }

    public function checkInactiveSubscription(Entreprise $entreprise): array
    {
        $inactiveSubscriptions = $this->getInactiveSubscriptions($entreprise);

        if (empty($inactiveSubscriptions)) {
            return [",dnlkd,nd"];
        }

        // Formater les données pour la réponse
        /*  $formattedSubscriptions = array_map(function (Abonnement $abonnement) { */
        return [
            'id' => $inactiveSubscriptions->getId(),
            'type' => $inactiveSubscriptions->getType(),
            'dateFin' => $inactiveSubscriptions->getDateFin()->format('Y-m-d H:i:s'),
            'code' => $inactiveSubscriptions->getModuleAbonnement()?->getCode(),
            'daysSinceExpiration' => (new \DateTime())->diff($inactiveSubscriptions->getDateFin())->days
        ];
        /*  }, $inactiveSubscriptions); */

        return $formattedSubscriptions;
    }

    public function getActiveSubscription(Entreprise $entreprise): ?Abonnement
    {
        //dd("entreprise", $entreprise);
        $activeSubscriptions = $this->abonnementRepository->findActiveForEntreprise($entreprise);
        return $activeSubscriptions;
    }

    public function checkFeatureAccess(Entreprise $entreprise): void
    {
        $abonnement = $this->getActiveSubscription($entreprise);

        if (!$abonnement) {
            throw new HttpException(403, 'Abonnement requis pour cette fonctionnalité');
        }

        // Vérification des modules/fonctionnalités spécifiques
        /*  $module = $abonnement->getModuleAbonnement();
        if (!$module || !$module->hasFeature($feature)) {
            throw new HttpException(403, sprintf(
                'Votre abonnement "%s" ne donne pas accès à cette fonctionnalité',
                $abonnement->getType()
            ));
        } */
    }

    public function getSettingByUser(Entreprise $entreprise,$type)
     {

        $set = $this->settingRepository->findOneBy(['entreprise' => $entreprise]);

        $nombreBoutique = (int)$set->getNombreBoutique();
        $nombreUser = (int)$set->getNombreUser();
        $nombreSuccursale = (int)$set->getNombreSuccursale();
        $nombreSms = (int)$set->getNombreSms();
        $nombreUserActive =  count($this->userRepository->findBy(['entreprise' => $entreprise, 'isActive' => true]));
        $nombreBoutiqueActive =  count($this->boutiqueRepository->findBy(['entreprise' => $entreprise, 'isActive' => true]));
        $nombreSuccursaleActive =  count($this->surccursaleRepository->findBy(['entreprise' => $entreprise, 'isActive' => true]));


        if($type == "user"){
            if($nombreUser <= $nombreUserActive){
                return false;
            }else{
                return true;
            }
            
        }elseif($type == "boutique"){
            if($nombreBoutique <= $nombreBoutiqueActive){
                return false;
            }else{
                return true;
            }
        }elseif($type == "succursale"){
            if($nombreSuccursale <= $nombreSuccursaleActive){
                return false;
            }else{
                return true;
            }
        }
        
       // return $this->settingRepository->findOneBy(['entreprise' => $entreprise]);
    }
}
