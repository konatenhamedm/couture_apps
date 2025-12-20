<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251220000921 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE abonnement CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE boutique CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE caisse CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE categorie_mesure CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE categorie_type_mesure CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE client CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE entre_stock CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE entreprise CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE facture CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE ligne_mesure CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE ligne_module CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE ligne_reservation CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE mesure CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE modele CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE modele_boutique CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE module_abonnement CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE notification CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE operateur CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE paiement CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE pays CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE reservation CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE setting CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE surccursale CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE type_mesure CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE type_user CHANGE is_active is_active TINYINT(1) DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE abonnement CHANGE is_active is_active TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE boutique CHANGE is_active is_active TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE caisse CHANGE is_active is_active TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE categorie_mesure CHANGE is_active is_active TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE categorie_type_mesure CHANGE is_active is_active TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE client CHANGE is_active is_active TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE entre_stock CHANGE is_active is_active TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE entreprise CHANGE is_active is_active TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE facture CHANGE is_active is_active TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE ligne_mesure CHANGE is_active is_active TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE ligne_module CHANGE is_active is_active TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE ligne_reservation CHANGE is_active is_active TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE mesure CHANGE is_active is_active TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE modele CHANGE is_active is_active TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE modele_boutique CHANGE is_active is_active TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE module_abonnement CHANGE is_active is_active TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE notification CHANGE is_active is_active TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE operateur CHANGE is_active is_active TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE paiement CHANGE is_active is_active TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE pays CHANGE is_active is_active TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE reservation CHANGE is_active is_active TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE setting CHANGE is_active is_active TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE surccursale CHANGE is_active is_active TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE type_mesure CHANGE is_active is_active TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE type_user CHANGE is_active is_active TINYINT(1) NOT NULL');
    }
}
