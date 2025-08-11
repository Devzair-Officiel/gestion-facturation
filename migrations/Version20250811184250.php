<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250811184250 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE company (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(160) NOT NULL, legal_name VARCHAR(160) NOT NULL, siren VARCHAR(32) DEFAULT NULL, siret VARCHAR(32) DEFAULT NULL, rcs_number VARCHAR(32) DEFAULT NULL, ape_code VARCHAR(32) DEFAULT NULL, vat_number VARCHAR(32) DEFAULT NULL, address JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', phone VARCHAR(50) DEFAULT NULL, email VARCHAR(160) DEFAULT NULL, website VARCHAR(255) DEFAULT NULL, default_currency VARCHAR(3) NOT NULL, iban VARCHAR(32) DEFAULT NULL, bic VARCHAR(32) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user ADD company_id INT NOT NULL, ADD first_name VARCHAR(100) NOT NULL, ADD last_name VARCHAR(100) NOT NULL, ADD is_active TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_8D93D649979B1AD6 ON user (company_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649979B1AD6');
        $this->addSql('DROP TABLE company');
        $this->addSql('DROP INDEX IDX_8D93D649979B1AD6 ON `user`');
        $this->addSql('ALTER TABLE `user` DROP company_id, DROP first_name, DROP last_name, DROP is_active');
    }
}
