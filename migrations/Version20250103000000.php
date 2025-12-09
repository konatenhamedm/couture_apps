<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour les nouvelles entités de gestion financière
 */
final class Version20250103000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des tables pour la gestion des ventes et paiements de factures';
    }

    public function up(Schema $schema): void
    {
        // Table vente
        $this->addSql('CREATE TABLE vente (
            id INT AUTO_INCREMENT NOT NULL, 
            boutique_id INT DEFAULT NULL, 
            client_id INT DEFAULT NULL, 
            numero VARCHAR(50) NOT NULL, 
            date DATETIME NOT NULL, 
            montant NUMERIC(10, 2) NOT NULL, 
            mode_paiement VARCHAR(50) DEFAULT NULL, 
            INDEX IDX_888A2A4CAB677312 (boutique_id), 
            INDEX IDX_888A2A4C19EB6921 (client_id), 
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Table ligne_vente
        $this->addSql('CREATE TABLE ligne_vente (
            id INT AUTO_INCREMENT NOT NULL, 
            vente_id INT NOT NULL, 
            produit VARCHAR(255) NOT NULL, 
            quantite INT NOT NULL, 
            prix_unitaire NUMERIC(10, 2) NOT NULL, 
            total NUMERIC(10, 2) NOT NULL, 
            INDEX IDX_3170B74B7DC7170A (vente_id), 
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Table paiement_facture
        $this->addSql('CREATE TABLE paiement_facture (
            id INT AUTO_INCREMENT NOT NULL, 
            facture_id INT NOT NULL, 
            date DATETIME NOT NULL, 
            montant NUMERIC(10, 2) NOT NULL, 
            mode_paiement VARCHAR(50) DEFAULT NULL, 
            reference VARCHAR(100) DEFAULT NULL, 
            INDEX IDX_F4E9A0477F2DEE08 (facture_id), 
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Contraintes de clés étrangères
        $this->addSql('ALTER TABLE vente ADD CONSTRAINT FK_888A2A4CAB677312 FOREIGN KEY (boutique_id) REFERENCES boutique (id)');
        $this->addSql('ALTER TABLE vente ADD CONSTRAINT FK_888A2A4C19EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE ligne_vente ADD CONSTRAINT FK_3170B74B7DC7170A FOREIGN KEY (vente_id) REFERENCES vente (id)');
        $this->addSql('ALTER TABLE paiement_facture ADD CONSTRAINT FK_F4E9A0477F2DEE08 FOREIGN KEY (facture_id) REFERENCES facture (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ligne_vente DROP FOREIGN KEY FK_3170B74B7DC7170A');
        $this->addSql('ALTER TABLE paiement_facture DROP FOREIGN KEY FK_F4E9A0477F2DEE08');
        $this->addSql('ALTER TABLE vente DROP FOREIGN KEY FK_888A2A4CAB677312');
        $this->addSql('ALTER TABLE vente DROP FOREIGN KEY FK_888A2A4C19EB6921');
        $this->addSql('DROP TABLE ligne_vente');
        $this->addSql('DROP TABLE paiement_facture');
        $this->addSql('DROP TABLE vente');
    }
}