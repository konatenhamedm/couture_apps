<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SwaggerUIConfigTest extends WebTestCase
{
    public function testSwaggerUIPageLoads(): void
    {
        $client = static::createClient();
        
        // Test que la page Swagger UI se charge correctement
        $crawler = $client->request('GET', '/');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('title');
        
        // Vérifier que la page contient les éléments Swagger UI
        $response = $client->getResponse();
        $content = $response->getContent();
        
        // Vérifier que la configuration docExpansion est présente
        $this->assertStringContainsString('swagger-ui', $content);
        $this->assertStringContainsString('docExpansion', $content);
    }
    
    public function testSwaggerUIConfigurationIsApplied(): void
    {
        $client = static::createClient();
        
        // Accéder à la page Swagger UI
        $client->request('GET', '/');
        
        $this->assertResponseIsSuccessful();
        
        $response = $client->getResponse();
        $content = $response->getContent();
        
        // Vérifier que la configuration docExpansion: "none" est appliquée
        $this->assertStringContainsString('"docExpansion":"none"', $content);
        
        // Vérifier que d'autres paramètres de configuration sont présents
        $this->assertStringContainsString('"filter":true', $content);
        $this->assertStringContainsString('"displayRequestDuration":true', $content);
    }
    
    public function testAPIEndpointsAreAccessible(): void
    {
        $client = static::createClient();
        
        // Tester qu'une route API simple fonctionne
        $client->request('GET', '/api/boutique/');
        
        // Nous nous attendons à une réponse 401 (non autorisé) car nous n'avons pas de token
        // mais cela confirme que la route existe et que l'API fonctionne
        $this->assertResponseStatusCodeSame(401);
        
        // Vérifier que la réponse est en JSON
        $this->assertResponseHeaderSame('content-type', 'application/json');
    }
}