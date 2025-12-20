<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251220075551 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_active column to paiement_reservation table';
    }

    public function up(Schema $schema): void
    {
        // Add is_active column to paiement_reservation table with default value 1
        $this->addSql('ALTER TABLE paiement_reservation ADD is_active TINYINT(1) NOT NULL DEFAULT 1');
        
        // Add created_at and updated_at columns if they don't exist
        $this->addSql('ALTER TABLE paiement_reservation ADD created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE paiement_reservation ADD updated_at DATETIME DEFAULT NULL');
        
        // Add created_by_id and updated_by_id columns if they don't exist
        $this->addSql('ALTER TABLE paiement_reservation ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE paiement_reservation ADD updated_by_id INT DEFAULT NULL');
        
        // Add foreign key constraints
        $this->addSql('ALTER TABLE paiement_reservation ADD CONSTRAINT FK_PR_CREATED_BY FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE paiement_reservation ADD CONSTRAINT FK_PR_UPDATED_BY FOREIGN KEY (updated_by_id) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        // Remove the columns added in up()
        $this->addSql('ALTER TABLE paiement_reservation DROP FOREIGN KEY FK_PR_CREATED_BY');
        $this->addSql('ALTER TABLE paiement_reservation DROP FOREIGN KEY FK_PR_UPDATED_BY');
        $this->addSql('ALTER TABLE paiement_reservation DROP COLUMN is_active');
        $this->addSql('ALTER TABLE paiement_reservation DROP COLUMN created_at');
        $this->addSql('ALTER TABLE paiement_reservation DROP COLUMN updated_at');
        $this->addSql('ALTER TABLE paiement_reservation DROP COLUMN created_by_id');
        $this->addSql('ALTER TABLE paiement_reservation DROP COLUMN updated_by_id');
    }
}
