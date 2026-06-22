<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260528133235 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE doc_main_category (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(1) NOT NULL, description VARCHAR(100) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8C6F750677153098 ON doc_main_category (code)');
        $this->addSql('CREATE TABLE doc_predefined_number (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code INTEGER NOT NULL, description VARCHAR(200) NOT NULL, sub_category_id INTEGER NOT NULL, CONSTRAINT FK_91E06F2BF7BFE87C FOREIGN KEY (sub_category_id) REFERENCES doc_sub_category (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_91E06F2BF7BFE87C ON doc_predefined_number (sub_category_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_91E06F2B77153098F7BFE87C ON doc_predefined_number (code, sub_category_id)');
        $this->addSql('CREATE TABLE doc_sub_category (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code INTEGER NOT NULL, description VARCHAR(150) NOT NULL, doc_type_id INTEGER NOT NULL, CONSTRAINT FK_E0D756A1AAE044D FOREIGN KEY (doc_type_id) REFERENCES doc_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_E0D756A1AAE044D ON doc_sub_category (doc_type_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E0D756A771530981AAE044D ON doc_sub_category (code, doc_type_id)');
        $this->addSql('CREATE TABLE doc_subsidiary (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(4) NOT NULL, description VARCHAR(100) NOT NULL, sort_order INTEGER DEFAULT 0 NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4965709877153098 ON doc_subsidiary (code)');
        $this->addSql('CREATE TABLE doc_type (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(10) NOT NULL, description VARCHAR(100) NOT NULL, sort_order INTEGER DEFAULT 0 NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D5C4E0FF77153098 ON doc_type (code)');
        $this->addSql('CREATE TABLE document_entry (id BLOB NOT NULL, reference_code VARCHAR(20) NOT NULL, doc_number INTEGER DEFAULT 0 NOT NULL, revision VARCHAR(10) DEFAULT \'00\' NOT NULL, title VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, subsidiary_id INTEGER NOT NULL, main_category_id INTEGER NOT NULL, doc_type_id INTEGER NOT NULL, sub_category_id INTEGER NOT NULL, created_by_id BLOB DEFAULT NULL, PRIMARY KEY (id), CONSTRAINT FK_4280EB02D4A7BDA2 FOREIGN KEY (subsidiary_id) REFERENCES doc_subsidiary (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4280EB02C6C55574 FOREIGN KEY (main_category_id) REFERENCES doc_main_category (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4280EB021AAE044D FOREIGN KEY (doc_type_id) REFERENCES doc_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4280EB02F7BFE87C FOREIGN KEY (sub_category_id) REFERENCES doc_sub_category (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_4280EB02B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_4280EB02D4A7BDA2 ON document_entry (subsidiary_id)');
        $this->addSql('CREATE INDEX IDX_4280EB02C6C55574 ON document_entry (main_category_id)');
        $this->addSql('CREATE INDEX IDX_4280EB021AAE044D ON document_entry (doc_type_id)');
        $this->addSql('CREATE INDEX IDX_4280EB02F7BFE87C ON document_entry (sub_category_id)');
        $this->addSql('CREATE INDEX IDX_4280EB02B03A8386 ON document_entry (created_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE doc_main_category');
        $this->addSql('DROP TABLE doc_predefined_number');
        $this->addSql('DROP TABLE doc_sub_category');
        $this->addSql('DROP TABLE doc_subsidiary');
        $this->addSql('DROP TABLE doc_type');
        $this->addSql('DROP TABLE document_entry');
    }
}
