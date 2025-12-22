<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251222111146 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add reservation workflow fields (status, confirmation/cancellation tracking) and migrate existing reservations to "confirmee" status';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE reservation_status_history (id INT AUTO_INCREMENT NOT NULL, reservation_id INT NOT NULL, changed_by_id INT NOT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, old_status VARCHAR(20) NOT NULL, new_status VARCHAR(20) NOT NULL, changed_at DATETIME NOT NULL, reason LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_active TINYINT(1) NOT NULL, INDEX IDX_3A5E46F7B83297E7 (reservation_id), INDEX IDX_3A5E46F7828AD0A0 (changed_by_id), INDEX IDX_3A5E46F7B03A8386 (created_by_id), INDEX IDX_3A5E46F7896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE reservation_status_history ADD CONSTRAINT FK_3A5E46F7B83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id)');
        $this->addSql('ALTER TABLE reservation_status_history ADD CONSTRAINT FK_3A5E46F7828AD0A0 FOREIGN KEY (changed_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE reservation_status_history ADD CONSTRAINT FK_3A5E46F7B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE reservation_status_history ADD CONSTRAINT FK_3A5E46F7896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE reservation ADD confirmed_by_id INT DEFAULT NULL, ADD cancelled_by_id INT DEFAULT NULL, ADD status VARCHAR(20) DEFAULT \'en_attente\' NOT NULL, ADD confirmed_at DATETIME DEFAULT NULL, ADD cancelled_at DATETIME DEFAULT NULL, ADD cancellation_reason LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C849556F45385D FOREIGN KEY (confirmed_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955187B2D12 FOREIGN KEY (cancelled_by_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_42C849556F45385D ON reservation (confirmed_by_id)');
        $this->addSql('CREATE INDEX IDX_42C84955187B2D12 ON reservation (cancelled_by_id)');
        
        // Migration des données existantes : toutes les réservations existantes sont considérées comme confirmées
        // car elles ont déjà été créées avec déduction du stock dans l'ancien système
        $this->addSql('UPDATE reservation SET status = \'confirmee\', confirmed_at = created_at WHERE status = \'en_attente\'');
        
        // Log de la migration pour audit
        $this->write('Migration completed: All existing reservations have been set to "confirmee" status');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reservation_status_history DROP FOREIGN KEY FK_3A5E46F7B83297E7');
        $this->addSql('ALTER TABLE reservation_status_history DROP FOREIGN KEY FK_3A5E46F7828AD0A0');
        $this->addSql('ALTER TABLE reservation_status_history DROP FOREIGN KEY FK_3A5E46F7B03A8386');
        $this->addSql('ALTER TABLE reservation_status_history DROP FOREIGN KEY FK_3A5E46F7896DBBDE');
        $this->addSql('DROP TABLE reservation_status_history');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C849556F45385D');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955187B2D12');
        $this->addSql('DROP INDEX IDX_42C849556F45385D ON reservation');
        $this->addSql('DROP INDEX IDX_42C84955187B2D12 ON reservation');
        $this->addSql('ALTER TABLE reservation DROP confirmed_by_id, DROP cancelled_by_id, DROP status, DROP confirmed_at, DROP cancelled_at, DROP cancellation_reason');
    }
}
