<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250811210440 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE customer (id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, title VARCHAR(160) NOT NULL, email VARCHAR(160) DEFAULT NULL, billing_address JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', currency VARCHAR(3) NOT NULL, vat_number VARCHAR(32) DEFAULT NULL, INDEX idx_customer_company (company_id), INDEX idx_customer_email (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE invoice (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', company_id INT NOT NULL, customer_id INT NOT NULL, number VARCHAR(32) NOT NULL, status VARCHAR(255) NOT NULL, issue_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', due_date DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', currency VARCHAR(3) NOT NULL, total_net INT NOT NULL, total_vat INT NOT NULL, total_gross INT NOT NULL, customer_snapshot JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', legal_mentions JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', INDEX idx_invoice_company (company_id), INDEX idx_invoice_customer (customer_id), INDEX idx_invoice_status (status), INDEX idx_invoice_due (due_date), UNIQUE INDEX uniq_invoice_company_number (company_id, number), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE invoice_line (id INT AUTO_INCREMENT NOT NULL, invoice_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', tax_rate_id INT DEFAULT NULL, designation VARCHAR(255) NOT NULL, quantity NUMERIC(18, 6) NOT NULL, unit_price_cents INT NOT NULL, discount_cents INT DEFAULT 0 NOT NULL, INDEX IDX_D3D1D693FDD13F95 (tax_rate_id), INDEX idx_invoiceline_invoice (invoice_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE payment (id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', company_id INT NOT NULL, invoice_id BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\', amount_cents INT NOT NULL, paid_at DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', method VARCHAR(24) NOT NULL, reference VARCHAR(255) DEFAULT NULL, INDEX IDX_6D28840D979B1AD6 (company_id), INDEX idx_payment_invoice (invoice_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, default_tax_id INT DEFAULT NULL, title VARCHAR(160) NOT NULL, unit_price_cents INT NOT NULL, currency VARCHAR(3) NOT NULL, archived TINYINT(1) NOT NULL, INDEX IDX_D34A04AD55F92F8B (default_tax_id), INDEX idx_product_company (company_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sequence (year INT NOT NULL, company_id INT NOT NULL, last_number INT NOT NULL, INDEX IDX_5286D72B979B1AD6 (company_id), UNIQUE INDEX uniq_seq_company_year (company_id, year), PRIMARY KEY(company_id, year)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tax_rate (id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, title VARCHAR(40) NOT NULL, percent NUMERIC(7, 4) NOT NULL, country VARCHAR(2) DEFAULT \'FR\' NOT NULL, active TINYINT(1) NOT NULL, INDEX IDX_C36330C1979B1AD6 (company_id), UNIQUE INDEX uniq_tax_company_title (company_id, title), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE customer ADD CONSTRAINT FK_81398E09979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_90651744979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_906517449395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('ALTER TABLE invoice_line ADD CONSTRAINT FK_D3D1D6932989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (id)');
        $this->addSql('ALTER TABLE invoice_line ADD CONSTRAINT FK_D3D1D693FDD13F95 FOREIGN KEY (tax_rate_id) REFERENCES tax_rate (id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D2989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD55F92F8B FOREIGN KEY (default_tax_id) REFERENCES tax_rate (id)');
        $this->addSql('ALTER TABLE sequence ADD CONSTRAINT FK_5286D72B979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('ALTER TABLE tax_rate ADD CONSTRAINT FK_C36330C1979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer DROP FOREIGN KEY FK_81398E09979B1AD6');
        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_90651744979B1AD6');
        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_906517449395C3F3');
        $this->addSql('ALTER TABLE invoice_line DROP FOREIGN KEY FK_D3D1D6932989F1FD');
        $this->addSql('ALTER TABLE invoice_line DROP FOREIGN KEY FK_D3D1D693FDD13F95');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D979B1AD6');
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D2989F1FD');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD979B1AD6');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD55F92F8B');
        $this->addSql('ALTER TABLE sequence DROP FOREIGN KEY FK_5286D72B979B1AD6');
        $this->addSql('ALTER TABLE tax_rate DROP FOREIGN KEY FK_C36330C1979B1AD6');
        $this->addSql('DROP TABLE customer');
        $this->addSql('DROP TABLE invoice');
        $this->addSql('DROP TABLE invoice_line');
        $this->addSql('DROP TABLE payment');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE sequence');
        $this->addSql('DROP TABLE tax_rate');
    }
}
