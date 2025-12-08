<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajout des champs statut et commentaire à EntreStock pour le workflow de confirmation
 */
final class Version20250115000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des champs statut et commentaire à EntreStock pour le workflow de confirmation';
    }

    public function up(Schema $schema): void
    {
        // Ajouter le champ statut avec valeur par défaut
        $this->addSql('ALTER TABLE entre_stock ADD statut VARCHAR(50) DEFAULT \'EN_ATTENTE\' NOT NULL');
        
        // Ajouter le champ commentaire nullable
        $this->addSql('ALTER TABLE entre_stock ADD commentaire LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Supprimer les champs ajoutés
        $this->addSql('ALTER TABLE entre_stock DROP statut');
        $this->addSql('ALTER TABLE entre_stock DROP commentaire');
    }
}