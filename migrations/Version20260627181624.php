<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260627181624 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__doc_subsidiary AS SELECT id, code, description, sort_order FROM doc_subsidiary');
        $this->addSql('DROP TABLE doc_subsidiary');
        $this->addSql('CREATE TABLE doc_subsidiary (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(2) NOT NULL, description VARCHAR(100) NOT NULL, sort_order INTEGER DEFAULT 0 NOT NULL)');
        $this->addSql('INSERT INTO doc_subsidiary (id, code, description, sort_order) SELECT id, code, description, sort_order FROM __temp__doc_subsidiary');
        $this->addSql('DROP TABLE __temp__doc_subsidiary');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4965709877153098 ON doc_subsidiary (code)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__document_entry AS SELECT id, reference_code, doc_number, revision, title, created_at, updated_at, subsidiary_id, main_category_id, doc_type_id, sub_category_id, created_by_id, comments FROM document_entry');
        $this->addSql('DROP TABLE document_entry');
        $this->addSql('CREATE TABLE document_entry (id BLOB NOT NULL, reference_code VARCHAR(20) NOT NULL, doc_number INTEGER DEFAULT 0 NOT NULL, revision VARCHAR(10) DEFAULT \'00\' NOT NULL, title VARCHAR(48) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, subsidiary_id INTEGER NOT NULL, main_category_id INTEGER NOT NULL, doc_type_id INTEGER NOT NULL, sub_category_id INTEGER NOT NULL, created_by_id BLOB DEFAULT NULL, comments CLOB DEFAULT NULL, PRIMARY KEY (id), CONSTRAINT FK_4280EB02D4A7BDA2 FOREIGN KEY (subsidiary_id) REFERENCES doc_subsidiary (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4280EB02C6C55574 FOREIGN KEY (main_category_id) REFERENCES doc_main_category (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4280EB021AAE044D FOREIGN KEY (doc_type_id) REFERENCES doc_type (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4280EB02F7BFE87C FOREIGN KEY (sub_category_id) REFERENCES doc_sub_category (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4280EB02B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO document_entry (id, reference_code, doc_number, revision, title, created_at, updated_at, subsidiary_id, main_category_id, doc_type_id, sub_category_id, created_by_id, comments) SELECT id, reference_code, doc_number, revision, title, created_at, updated_at, subsidiary_id, main_category_id, doc_type_id, sub_category_id, created_by_id, comments FROM __temp__document_entry');
        $this->addSql('DROP TABLE __temp__document_entry');
        $this->addSql('CREATE INDEX IDX_4280EB02B03A8386 ON document_entry (created_by_id)');
        $this->addSql('CREATE INDEX IDX_4280EB02F7BFE87C ON document_entry (sub_category_id)');
        $this->addSql('CREATE INDEX IDX_4280EB021AAE044D ON document_entry (doc_type_id)');
        $this->addSql('CREATE INDEX IDX_4280EB02C6C55574 ON document_entry (main_category_id)');
        $this->addSql('CREATE INDEX IDX_4280EB02D4A7BDA2 ON document_entry (subsidiary_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__doc_subsidiary AS SELECT id, code, description, sort_order FROM doc_subsidiary');
        $this->addSql('DROP TABLE doc_subsidiary');
        $this->addSql('CREATE TABLE doc_subsidiary (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(4) NOT NULL, description VARCHAR(100) NOT NULL, sort_order INTEGER DEFAULT 0 NOT NULL)');
        $this->addSql('INSERT INTO doc_subsidiary (id, code, description, sort_order) SELECT id, code, description, sort_order FROM __temp__doc_subsidiary');
        $this->addSql('DROP TABLE __temp__doc_subsidiary');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4965709877153098 ON doc_subsidiary (code)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__document_entry AS SELECT id, reference_code, doc_number, revision, title, comments, created_at, updated_at, subsidiary_id, main_category_id, doc_type_id, sub_category_id, created_by_id FROM document_entry');
        $this->addSql('DROP TABLE document_entry');
        $this->addSql('CREATE TABLE document_entry (id BLOB NOT NULL, reference_code VARCHAR(20) NOT NULL, doc_number INTEGER DEFAULT 0 NOT NULL, revision VARCHAR(10) DEFAULT \'00\' NOT NULL, title VARCHAR(255) NOT NULL, comments CLOB DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, subsidiary_id INTEGER NOT NULL, main_category_id INTEGER NOT NULL, doc_type_id INTEGER NOT NULL, sub_category_id INTEGER NOT NULL, created_by_id BLOB DEFAULT NULL, PRIMARY KEY (id), CONSTRAINT FK_4280EB02D4A7BDA2 FOREIGN KEY (subsidiary_id) REFERENCES doc_subsidiary (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4280EB02C6C55574 FOREIGN KEY (main_category_id) REFERENCES doc_main_category (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4280EB021AAE044D FOREIGN KEY (doc_type_id) REFERENCES doc_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4280EB02F7BFE87C FOREIGN KEY (sub_category_id) REFERENCES doc_sub_category (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4280EB02B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO document_entry (id, reference_code, doc_number, revision, title, comments, created_at, updated_at, subsidiary_id, main_category_id, doc_type_id, sub_category_id, created_by_id) SELECT id, reference_code, doc_number, revision, title, comments, created_at, updated_at, subsidiary_id, main_category_id, doc_type_id, sub_category_id, created_by_id FROM __temp__document_entry');
        $this->addSql('DROP TABLE __temp__document_entry');
        $this->addSql('CREATE INDEX IDX_4280EB02D4A7BDA2 ON document_entry (subsidiary_id)');
        $this->addSql('CREATE INDEX IDX_4280EB02C6C55574 ON document_entry (main_category_id)');
        $this->addSql('CREATE INDEX IDX_4280EB021AAE044D ON document_entry (doc_type_id)');
        $this->addSql('CREATE INDEX IDX_4280EB02F7BFE87C ON document_entry (sub_category_id)');
        $this->addSql('CREATE INDEX IDX_4280EB02B03A8386 ON document_entry (created_by_id)');
    }
}
