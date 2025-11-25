<?php

namespace App\Tests\Unit;

use App\Entity\Paiement;
use App\Entity\PaiementFacture;
use App\Entity\PaiementReservation;
use App\Entity\Facture;
use App\Entity\Reservation;
use App\Entity\Client;
use PHPUnit\Framework\TestCase;
use DateTime;

class EntityTest extends TestCase
{
    public function testPaiementEntity(): void
    {
        $paiement = new Paiement();
        $paiement->setMontant('1500.50');
        $paiement->setReference('PAY-2025-001');
        $paiement->setType('paiementFacture');

        $this->assertEquals('1500.50', $paiement->getMontant());
        $this->assertEquals('PAY-2025-001', $paiement->getReference());
        $this->assertEquals('paiementFacture', $paiement->getType());
    }

    public function testPaiementFactureInheritance(): void
    {
        $facture = new Facture();
        $paiementFacture = new PaiementFacture();
        $paiementFacture->setFacture($facture);
        $paiementFacture->setMontant('2500.00');

        $this->assertInstanceOf(Paiement::class, $paiementFacture);
        $this->assertEquals($facture, $paiementFacture->getFacture());
        $this->assertEquals('2500.00', $paiementFacture->getMontant());
    }

    public function testPaiementReservationInheritance(): void
    {
        $reservation = new Reservation();
        $paiementReservation = new PaiementReservation();
        $paiementReservation->setReservation($reservation);
        $paiementReservation->setMontant('500.00');

        $this->assertInstanceOf(Paiement::class, $paiementReservation);
        $this->assertEquals($reservation, $paiementReservation->getReservation());
        $this->assertEquals('500.00', $paiementReservation->getMontant());
    }

    public function testPaiementTypeConstants(): void
    {
        $expectedTypes = [
            "paiementSuccursale" => "paiementSuccursale",
            "paiementBoutique" => "paiementBoutique",
            "paiementAbonnement" => "paiementAbonnement",
            'paiementFacture' => 'paiementFacture',
            'paiementReservation' => 'paiementReservation',
        ];

        $this->assertEquals($expectedTypes, Paiement::TYPE);
        $this->assertArrayHasKey('paiementFacture', Paiement::TYPE);
        $this->assertArrayHasKey('paiementReservation', Paiement::TYPE);
    }
}