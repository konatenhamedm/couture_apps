<?php

namespace App\Service;

use App\Entity\Abonnement;
use App\Entity\Boutique;
use App\Entity\ModuleAbonnement;
use App\Entity\Paiement;
use App\Entity\PaiementAbonnement;
use App\Entity\Surccursale;
use App\Entity\User;
use App\Repository\AbonnementRepository;
use App\Repository\BoutiqueRepository;
use App\Repository\EntrepriseRepository;
use App\Repository\ModuleAbonnementRepository;
use App\Repository\PaiementAbonnementRepository;
use App\Repository\PaysRepository;
use App\Repository\SurccursaleRepository;
use App\Repository\UserRepository;
use App\Trait\DatabaseEnvironmentTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PaiementService
{
 use DatabaseEnvironmentTrait;
    private string $apiKey;
    private string $merchantId;
    private string $paiementUrl;
    private string $sendMail;
    private string $superAdmin;

    public function __construct(
        private ParameterBagInterface $params,
        private UrlGeneratorInterface $urlGenerator,
        private HttpClientInterface $httpClient,
        private EntityManagerProvider $em,
        private PaiementAbonnementRepository $paiementAbonnementRepository,
        private ModuleAbonnementRepository $moduleAbonnementRepository,
        private AbonnementRepository $abonnementRepository,
        private SendMailService $sendMailService,
        private AddCategorie $addCategorie,
        private PaysRepository $paysRepository,
        private UserRepository $userRepository,
        private BoutiqueRepository $boutiqueRepository,
        private SurccursaleRepository $surccursaleRepository,
        private EntrepriseRepository $entrepriseRepository
    ) {

        $this->apiKey = $params->get('API_KEY');
        $this->merchantId = $params->get('MERCHANT_ID');
        $this->paiementUrl = $params->get('PAIEMENT_URL');
        $this->sendMail = $params->get('SEND_MAIL');
        $this->superAdmin = $params->get('SUPER_ADMIN');
    }

    public function generateReference(string $code): string
    {
        $query = $this->createQueryBuilder();
        $query->select("count(a.id)")
            ->from(Paiement::class, 'a');

        $nb = $query->getQuery()->getSingleScalarResult();
        return ($code . date("y") . date("m") . date("d") . date("H") . date("i") . date("s") . str_pad($nb + 1, 3, '0', STR_PAD_LEFT));
    }

    public function traiterPaiement(User $user, ModuleAbonnement $moduleAbonnement, array $data = []): array
    {
        $paiement = new PaiementAbonnement();

        $entreprise = $this->entrepriseRepository->findInEnvironment($data['entrepriseId']);
        $paiement->setMontant($moduleAbonnement->getMontant());
        $paiement->setModuleAbonnement($moduleAbonnement);
        $paiement->setEntreprise($entreprise);
        $paiement->setIsActive(true);
        $paiement->setCreatedAtValue(new \DateTime());
        $reference = $this->generateReference('ABNT');
        $paiement->setReference($reference);
        $paiement->setPays('CI');
        $paiement->setChannel($data['operateur']);
        $paiement->setCreatedBy($user);
        $paiement->setUpdatedBy($user);
        $paiement->setType("Abonnement");
        $paiement->setDataBoutique($data['dataBoutique']);
        $paiement->setDataSuccursale($data['dataSuccursale']);
        $paiement->setDataUser($data['dataUser']);
        $paiement->setState(0);


        try {
            $client = new \SoapClient($this->paiementUrl . '?wsdl', [
                'cache_wsdl' => WSDL_CACHE_NONE,
                'trace' => 1,
                'exceptions' => true
            ]);

            $requestData = [
                'merchantId'           => $this->merchantId,
                'referenceNumber'      => $reference,
                'amount'               => 100,
                'channel'              => $data['operateur'], // ex: CARD, MOBILE
                'countryCurrencyCode'  => '952',
                'currency'             => 'XOF',
                'customerId'           => (string) $user->getId(),
                'hashcode'             => 'hashcode',
                'customerFirstName'    => $entreprise->getLibelle(),
                'customerLastname'     => $entreprise->getLibelle(),
                'customerEmail'        => $data['email'],
                'customerPhoneNumber'  => $data['numero'],
                'description'          => 'Abonnement ' . $moduleAbonnement->getCode(),
                'notificationURL'      => "https://backend.ateliya.com/api/paiement/webhook",
                'returnURL'            => $data['returnURL'] ?? 'ateliya://payment/success',
                'returnContext'        => http_build_query([
                    'paiement_id' => $paiement->getId(),
                    'user_id'     => $user->getId(),
                    'reference'   => $reference,
                    'status'      => 'pending'
                ]),
                /*  'api'                  => $this->apiKey,
                'service'              => 'PAIEMENT', */
            ];

            $response = $client->initTransact($requestData);


            if ($response->Code == 0) {
                $this->paiementAbonnementRepository->add($paiement, true);
            }

            $paiementProUrl = 'https://www.paiementpro.net/webservice/onlinepayment/processing_v2.php?sessionid=' . $response->Sessionid;
            $sessionId = $response->Sessionid;

            return [
                'code'        => 200,
                'reference'   => $reference,
                'transaction_id' => $sessionId,
                'redirectUrl' => $paiementProUrl,
                'sessionId'   => $sessionId
            ];
        } catch (\SoapFault $e) {
            return [
                'code' => 400,
                'error' => $e->getMessage(),
                'reference' => $reference
            ];
        }
    }



    public function methodeWebHook($data)
    {

      /*   $data = json_decode($request->getContent()); */
/* 
        $file= fopen(dirname(__FILE__).'/paiement.log', 'w+');
        $data = file_get_contents('php://input');
        fwrite($file, $data); */

     
        $paiement = $this->paiementAbonnementRepository->findOneByInEnvironment(['reference' => $data['referenceNumber']]);

        if ($data['responsecode'] == 0) {
            $paiement->setState(1);

            $paiement->setChannel($data->channel);
            //$transaction->setData(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $this->paiementAbonnementRepository->add($paiement, true);
            // JE dois mettre a jour l'abonnement
            $this->createAbonnement($data['referenceNumber']);

            $response = ['message' => 'OK', 'code' => 200];
        } else {
            $response = [
                'message' => 'Echec',
                'code' => 400
            ];
        }


        return $response;
    }

    public function createAbonnement($ref)
    {

        // je dois desactiver les user ,boutique et succursale
        //je dois recuperer les parametres de l'abonnement
        //je dois renseigner setting 

        $paiement = $this->paiementAbonnementRepository->findOneByInEnvironment(['reference' => $ref]);

        $abonnementActif = $this->abonnementRepository->findOneByInEnvironment(['moduleAbonnement' => $paiement->getModuleAbonnement(), 'entreprise' => $paiement->getEntreprise(), 'etat' => 'actif']);

        $abonnement = new Abonnement();
        $abonnement->setModuleAbonnement($paiement->getModuleAbonnement());
        $abonnement->setEntreprise($paiement->getEntreprise());
        $abonnement->setIsActive(true);
        $abonnement->setType('payant');
        $abonnement->setEtat('actif');

        if ($abonnementActif == null) {
            $abonnement->setDateFin((new \DateTime())->modify('+' . $paiement->getModuleAbonnement()->getDuree() . ' month'));
        } else {
            $abonnement->setDateFin($abonnementActif->getDateFin()->modify('+' . $paiement->getModuleAbonnement()->getDuree() . ' month'));
            $abonnementActif->setEtat('inactif');
            $this->abonnementRepository->add($abonnementActif, true);
        }
        $this->abonnementRepository->add($abonnement, true);


        $nombreSms = 0;
        $nombreUser = 0;
        $nombresuccursale = 0;

        foreach ($paiement->getModuleAbonnement()->getLigneModules() as  $ligneModule) {
            $nombreSms = $ligneModule->getLibelle() == "SMS" ? $ligneModule->getQuantite() : 0;
            $nombreUser = $ligneModule->getLibelle() == "USER" ? $ligneModule->getQuantite() : 0;
            $nombresuccursale = $ligneModule->getLibelle() == "SUCCURSALE" ? $ligneModule->getQuantite() : 0;
            $nombreBoutique = $ligneModule->getLibelle() == "BOUTIQUE" ? $ligneModule->getQuantite() : 0;
        }


        $this->addCategorie->setting($paiement->getEntreprise(), [
            'succursale' => $nombresuccursale,
            'user' => $nombreUser,
            'sms' => $nombreSms,
            'boutique' => $nombreBoutique
        ]);

        if ($paiement->getDataUser()) {
            foreach ($paiement->getDataUser() as $user) {

                $data = $this->userRepository->find($user);
                $data->setsetIsActive(false);
                $this->userRepository->add($data, true);
            }
        }
        if ($paiement->getDataSuccursale()) {
            foreach ($paiement->getDataSuccursale() as $succursale) {

                $data = $this->surccursaleRepository->find($succursale);
                $data->setsetIsActive(false);
                $this->surccursaleRepository->add($data, true);
            }
        }
        if ($paiement->getDataBoutique()) {
            foreach ($paiement->getDataBoutique() as $boutique) {

                $data = $this->boutiqueRepository->find($boutique);
                $data->setsetIsActive(false);
                $this->boutiqueRepository->add($data, true);
            }
        }


        $this->sendMailService->sendNotification([
            'libelle' => sprintf("🎉 Bienvenue %s dans notre application !", $paiement->getEntreprise()->getLibelle()),
            'titre'   => "Bienvenue parmi nous",
            'entreprise' => $paiement->getEntreprise(),
            'user' => $this->userRepository->getUserByCodeType($paiement->getEntreprise()->getId()),
            'userUpdate' => $this->userRepository->getUserByCodeType($paiement->getEntreprise()->getId())
        ]);

        $this->sendMailService->send(
            $this->sendMail,
            $this->superAdmin,
            "Nouveau abonnement - " . $paiement->getEntreprise(),
            "abonnement",
            [
                "entreprise" =>  $paiement->getEntreprise()->geLibelle(),
                "abonnement" => $paiement->getModuleAbonnement()->getCode(),
                "date" => (new \DateTime())->format('d/m/Y H:i'),
            ]
        );
    }
}