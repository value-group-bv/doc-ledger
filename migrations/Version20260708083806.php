<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260708083806 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add feasibility_code table (AAA-ZZZ reference codes) and per-subsidiary API key columns';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE feasibility_code (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(3) DEFAULT NULL, title VARCHAR(255) NOT NULL, requestor VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, subsidiary_id INTEGER NOT NULL, CONSTRAINT FK_DC64AA1ED4A7BDA2 FOREIGN KEY (subsidiary_id) REFERENCES doc_subsidiary (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DC64AA1E77153098 ON feasibility_code (code)');
        $this->addSql('CREATE INDEX IDX_DC64AA1ED4A7BDA2 ON feasibility_code (subsidiary_id)');
        $this->addSql('ALTER TABLE doc_subsidiary ADD COLUMN api_key_hash VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE doc_subsidiary ADD COLUMN api_key_generated_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE feasibility_code');
        $this->addSql('CREATE TEMPORARY TABLE __temp__doc_subsidiary AS SELECT id, code, description, sort_order FROM doc_subsidiary');
        $this->addSql('DROP TABLE doc_subsidiary');
        $this->addSql('CREATE TABLE doc_subsidiary (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(2) NOT NULL, description VARCHAR(100) NOT NULL, sort_order INTEGER DEFAULT 0 NOT NULL)');
        $this->addSql('INSERT INTO doc_subsidiary (id, code, description, sort_order) SELECT id, code, description, sort_order FROM __temp__doc_subsidiary');
        $this->addSql('DROP TABLE __temp__doc_subsidiary');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4965709877153098 ON doc_subsidiary (code)');
    }
}
