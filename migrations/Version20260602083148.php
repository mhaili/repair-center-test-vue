<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260602083148 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE quote_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE quote_line_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE quote (id INT NOT NULL, repair_order_id INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6B71CBF4E4071493 ON quote (repair_order_id)');
        $this->addSql('CREATE TABLE quote_line (id INT NOT NULL, quote_id INT NOT NULL, part_id INT DEFAULT NULL, type VARCHAR(10) NOT NULL, quantity INT DEFAULT NULL, unit_price_cents INT DEFAULT NULL, labor_type VARCHAR(20) DEFAULT NULL, duration_centis INT DEFAULT NULL, hourly_rate_cents INT DEFAULT NULL, discount_basis_points INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_43F3EB7CDB805178 ON quote_line (quote_id)');
        $this->addSql('CREATE INDEX IDX_43F3EB7C4CE34BEC ON quote_line (part_id)');
        $this->addSql('ALTER TABLE quote ADD CONSTRAINT FK_6B71CBF4E4071493 FOREIGN KEY (repair_order_id) REFERENCES repair_order (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quote_line ADD CONSTRAINT FK_43F3EB7CDB805178 FOREIGN KEY (quote_id) REFERENCES quote (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quote_line ADD CONSTRAINT FK_43F3EB7C4CE34BEC FOREIGN KEY (part_id) REFERENCES part (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE customer ALTER id DROP DEFAULT');
        $this->addSql('ALTER TABLE part ALTER id DROP DEFAULT');
        $this->addSql('ALTER INDEX uniq_part_reference RENAME TO UNIQ_490F70C6AEA34913');
        $this->addSql('ALTER TABLE repair_order DROP total_amount');
        $this->addSql('ALTER TABLE repair_order ALTER id DROP DEFAULT');
        $this->addSql('ALTER INDEX uniq_repair_order_reference RENAME TO UNIQ_55F65734AEA34913');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE quote_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE quote_line_id_seq CASCADE');
        $this->addSql('ALTER TABLE quote DROP CONSTRAINT FK_6B71CBF4E4071493');
        $this->addSql('ALTER TABLE quote_line DROP CONSTRAINT FK_43F3EB7CDB805178');
        $this->addSql('ALTER TABLE quote_line DROP CONSTRAINT FK_43F3EB7C4CE34BEC');
        $this->addSql('DROP TABLE quote');
        $this->addSql('DROP TABLE quote_line');
        $this->addSql('CREATE SEQUENCE part_id_seq');
        $this->addSql('SELECT setval(\'part_id_seq\', (SELECT MAX(id) FROM part))');
        $this->addSql('ALTER TABLE part ALTER id SET DEFAULT nextval(\'part_id_seq\')');
        $this->addSql('ALTER INDEX uniq_490f70c6aea34913 RENAME TO uniq_part_reference');
        $this->addSql('CREATE SEQUENCE customer_id_seq');
        $this->addSql('SELECT setval(\'customer_id_seq\', (SELECT MAX(id) FROM customer))');
        $this->addSql('ALTER TABLE customer ALTER id SET DEFAULT nextval(\'customer_id_seq\')');
        $this->addSql('ALTER TABLE repair_order ADD total_amount DOUBLE PRECISION NOT NULL');
        $this->addSql('CREATE SEQUENCE repair_order_id_seq');
        $this->addSql('SELECT setval(\'repair_order_id_seq\', (SELECT MAX(id) FROM repair_order))');
        $this->addSql('ALTER TABLE repair_order ALTER id SET DEFAULT nextval(\'repair_order_id_seq\')');
        $this->addSql('ALTER INDEX uniq_55f65734aea34913 RENAME TO uniq_repair_order_reference');
    }
}
