<?php

namespace App\Tests\Controller\ApiClient\Mocks;

use App\Entity\Entreprise;
use App\Entity\Abonnement;

/**
 * Mock subscription checker for testing
 */
class MockSubscriptionChecker
{
    private array $activeSubscriptions = [];
    private array $inactiveEntreprises = [];
    
    /**
     * Set an entreprise to have an active subscription
     */
    public function setActiveSubscription(Entreprise $entreprise): void
    {
        $this->activeSubscriptions[$entreprise->getId()] = true;
    }
    
    /**
     * Set an entreprise to not have an active subscription
     */
    public function setInactiveSubscription(Entreprise $entreprise): void
    {
        $this->inactiveEntreprises[$entreprise->getId()] = true;
        unset($this->activeSubscriptions[$entreprise->getId()]);
    }
    
    /**
     * Mock the getActiveSubscription method
     */
    public function getActiveSubscription(?Entreprise $entreprise): ?Abonnement
    {
        if (!$entreprise) {
            return null;
        }
        
        if (isset($this->inactiveEntreprises[$entreprise->getId()])) {
            return null;
        }
        
        if (isset($this->activeSubscriptions[$entreprise->getId()])) {
            // Create a mock subscription
            $subscription = new Abonnement();
            $subscription->setEntreprise($entreprise);
            $subscription->setIsActive(true);
            return $subscription;
        }
        
        // Default: return active subscription for testing
        $subscription = new Abonnement();
        $subscription->setEntreprise($entreprise);
        $subscription->setIsActive(true);
        return $subscription;
    }
    
    /**
     * Reset all subscription states
     */
    public function reset(): void
    {
        $this->activeSubscriptions = [];
        $this->inactiveEntreprises = [];
    }
    
    /**
     * Check if entreprise has active subscription
     */
    public function hasActiveSubscription(?Entreprise $entreprise): bool
    {
        return $this->getActiveSubscription($entreprise) !== null;
    }
}