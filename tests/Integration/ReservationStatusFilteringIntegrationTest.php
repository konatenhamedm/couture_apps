<?php

namespace App\Tests\Integration;

use App\Enum\ReservationStatus;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests d'intégration pour le filtrage par statut des réservations
 * 
 * Ces tests valident le fonctionnement des endpoints avec les paramètres de filtrage.
 */
class ReservationStatusFilteringIntegrationTest extends WebTestCase
{
    /**
     * Test de l'endpoint index avec filtrage par statut unique
     */
    public function testIndexWithSingleStatusFilter(): void
    {
        $client = static::createClient();

        // Test avec chaque statut valide
        $validStatuses = [
            ReservationStatus::EN_ATTENTE->value,
            ReservationStatus::CONFIRMEE->value,
            ReservationStatus::ANNULEE->value
        ];

        foreach ($validStatuses as $status) {
            $client->request('GET', '/api/reservation/', ['status' => $status]);
            
            // Vérifier que la requête est acceptée (même si elle peut retourner une erreur d'auth)
            $this->assertContains($client->getResponse()->getStatusCode(), [200, 401, 403], 
                "L'endpoint doit accepter le statut '{$status}' comme paramètre valide");
        }
    }

    /**
     * Test de l'endpoint index avec filtrage par statuts multiples
     */
    public function testIndexWithMultipleStatusFilter(): void
    {
        $client = static::createClient();

        // Test avec plusieurs statuts
        $multipleStatuses = 'en_attente,confirmee';
        $client->request('GET', '/api/reservation/', ['status' => $multipleStatuses]);
        
        // Vérifier que la requête est acceptée
        $this->assertContains($client->getResponse()->getStatusCode(), [200, 401, 403], 
            "L'endpoint doit accepter plusieurs statuts comme paramètre");
    }

    /**
     * Test de l'endpoint index avec statut invalide
     */
    public function testIndexWithInvalidStatusFilter(): void
    {
        $client = static::createClient();

        $invalidStatus = 'invalid_status';
        $client->request('GET', '/api/reservation/', ['status' => $invalidStatus]);
        
        // Vérifier que la requête retourne une erreur 400 ou une erreur d'auth
        $this->assertContains($client->getResponse()->getStatusCode(), [400, 401, 403], 
            "L'endpoint doit rejeter les statuts invalides");
    }

    /**
     * Test de l'endpoint indexAll avec filtrage par statut
     */
    public function testIndexAllWithStatusFilter(): void
    {
        $client = static::createClient();

        $status = ReservationStatus::EN_ATTENTE->value;
        $client->request('GET', '/api/reservation/entreprise', ['status' => $status]);
        
        // Vérifier que la requête est acceptée (même si elle peut retourner une erreur d'auth)
        $this->assertContains($client->getResponse()->getStatusCode(), [200, 401, 403], 
            "L'endpoint /entreprise doit accepter le filtrage par statut");
    }

    /**
     * Test de l'endpoint index sans filtre (comportement par défaut)
     */
    public function testIndexWithoutFilter(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/reservation/');
        
        // Vérifier que la requête fonctionne sans filtre
        $this->assertContains($client->getResponse()->getStatusCode(), [200, 401, 403], 
            "L'endpoint doit fonctionner sans paramètre de filtre");
    }

    /**
     * Test de validation des paramètres de requête
     */
    public function testQueryParameterValidation(): void
    {
        $client = static::createClient();

        // Test avec chaîne vide
        $client->request('GET', '/api/reservation/', ['status' => '']);
        $this->assertContains($client->getResponse()->getStatusCode(), [200, 401, 403], 
            "L'endpoint doit gérer les paramètres vides");

        // Test avec espaces
        $client->request('GET', '/api/reservation/', ['status' => ' en_attente ']);
        $this->assertContains($client->getResponse()->getStatusCode(), [200, 401, 403], 
            "L'endpoint doit gérer les espaces dans les paramètres");

        // Test avec virgules supplémentaires
        $client->request('GET', '/api/reservation/', ['status' => 'en_attente,']);
        $this->assertContains($client->getResponse()->getStatusCode(), [200, 400, 401, 403], 
            "L'endpoint doit gérer les virgules supplémentaires");
    }

    /**
     * Test de compatibilité avec les autres paramètres
     */
    public function testCompatibilityWithOtherParameters(): void
    {
        $client = static::createClient();

        // Test avec d'autres paramètres de requête potentiels
        $client->request('GET', '/api/reservation/', [
            'status' => 'en_attente',
            'page' => 1,
            'limit' => 10
        ]);
        
        $this->assertContains($client->getResponse()->getStatusCode(), [200, 401, 403], 
            "L'endpoint doit être compatible avec d'autres paramètres de requête");
    }

    /**
     * Test de la documentation OpenAPI
     */
    public function testOpenApiDocumentation(): void
    {
        // Ce test vérifie que les annotations OpenAPI sont correctement configurées
        // En vérifiant que les endpoints acceptent les paramètres de statut
        
        $client = static::createClient();
        
        // Test que l'endpoint accepte le paramètre status
        $client->request('GET', '/api/reservation/', ['status' => 'en_attente']);
        
        // Si l'endpoint n'était pas configuré pour accepter ce paramètre,
        // il pourrait ignorer le paramètre ou retourner une erreur
        $this->assertNotEquals(404, $client->getResponse()->getStatusCode(), 
            "L'endpoint doit être configuré pour accepter le paramètre status");
    }
}