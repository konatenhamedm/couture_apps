<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251216131836 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ligne_vente ADD CONSTRAINT FK_8B26C07C7DC7170A FOREIGN KEY (vente_id) REFERENCES vente (id)');
        $this->addSql('DROP INDEX idx_3170b74b7dc7170a ON ligne_vente');
        $this->addSql('CREATE INDEX IDX_8B26C07C7DC7170A ON ligne_vente (vente_id)');
        $this->addSql('ALTER TABLE vente DROP FOREIGN KEY FK_888A2A4CAB677BE6');
        $this->addSql('DROP INDEX idx_888a2a4cab677312 ON vente');
        $this->addSql('CREATE INDEX IDX_888A2A4CAB677BE6 ON vente (boutique_id)');
        $this->addSql('ALTER TABLE vente ADD CONSTRAINT FK_888A2A4CAB677BE6 FOREIGN KEY (boutique_id) REFERENCES boutique (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ligne_vente DROP FOREIGN KEY FK_8B26C07C7DC7170A');
        $this->addSql('ALTER TABLE ligne_vente DROP FOREIGN KEY FK_8B26C07C7DC7170A');
        $this->addSql('DROP INDEX idx_8b26c07c7dc7170a ON ligne_vente');
        $this->addSql('CREATE INDEX IDX_3170B74B7DC7170A ON ligne_vente (vente_id)');
        $this->addSql('ALTER TABLE ligne_vente ADD CONSTRAINT FK_8B26C07C7DC7170A FOREIGN KEY (vente_id) REFERENCES vente (id)');
        $this->addSql('ALTER TABLE vente DROP FOREIGN KEY FK_888A2A4CAB677BE6');
        $this->addSql('DROP INDEX idx_888a2a4cab677be6 ON vente');
        $this->addSql('CREATE INDEX IDX_888A2A4CAB677312 ON vente (boutique_id)');
        $this->addSql('ALTER TABLE vente ADD CONSTRAINT FK_888A2A4CAB677BE6 FOREIGN KEY (boutique_id) REFERENCES boutique (id)');
    }
}
