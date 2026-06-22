<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260622000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add main_category_id to doc_sub_category; unique constraint becomes (code, doc_type_id, main_category_id, subsidiary_id)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE doc_sub_category_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            code INTEGER NOT NULL,
            description VARCHAR(150) NOT NULL,
            doc_type_id INTEGER NOT NULL,
            main_category_id INTEGER DEFAULT NULL,
            subsidiary_id INTEGER DEFAULT NULL,
            CONSTRAINT FK_E0D756A1AAE044D FOREIGN KEY (doc_type_id) REFERENCES doc_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE,
            CONSTRAINT FK_E0D756AC6C55574 FOREIGN KEY (main_category_id) REFERENCES doc_main_category (id) NOT DEFERRABLE INITIALLY IMMEDIATE,
            CONSTRAINT FK_E0D756AD4A7BDA2 FOREIGN KEY (subsidiary_id) REFERENCES doc_subsidiary (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        )');
        $this->addSql('INSERT INTO doc_sub_category_new (id, code, description, doc_type_id, subsidiary_id) SELECT id, code, description, doc_type_id, subsidiary_id FROM doc_sub_category');
        $this->addSql('DROP TABLE doc_sub_category');
        $this->addSql('ALTER TABLE doc_sub_category_new RENAME TO doc_sub_category');
        $this->addSql('CREATE INDEX IDX_E0D756A1AAE044D ON doc_sub_category (doc_type_id)');
        $this->addSql('CREATE INDEX IDX_E0D756AC6C55574 ON doc_sub_category (main_category_id)');
        $this->addSql('CREATE INDEX IDX_E0D756AD4A7BDA2 ON doc_sub_category (subsidiary_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E0D756A771530981AAE044DC6C55574D4A7BDA2 ON doc_sub_category (code, doc_type_id, main_category_id, subsidiary_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE doc_sub_category_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            code INTEGER NOT NULL,
            description VARCHAR(150) NOT NULL,
            doc_type_id INTEGER NOT NULL,
            subsidiary_id INTEGER DEFAULT NULL,
            CONSTRAINT FK_E0D756A1AAE044D FOREIGN KEY (doc_type_id) REFERENCES doc_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE,
            CONSTRAINT FK_E0D756A1D4A7BDA2 FOREIGN KEY (subsidiary_id) REFERENCES doc_subsidiary (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE
        )');
        $this->addSql('INSERT INTO doc_sub_category_new (id, code, description, doc_type_id, subsidiary_id) SELECT id, code, description, doc_type_id, subsidiary_id FROM doc_sub_category');
        $this->addSql('DROP TABLE doc_sub_category');
        $this->addSql('ALTER TABLE doc_sub_category_new RENAME TO doc_sub_category');
        $this->addSql('CREATE INDEX IDX_E0D756A1AAE044D ON doc_sub_category (doc_type_id)');
        $this->addSql('CREATE INDEX IDX_E0D756A1D4A7BDA2 ON doc_sub_category (subsidiary_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E0D756A771530981AAE044DD4A7BDA2 ON doc_sub_category (code, doc_type_id, subsidiary_id)');
    }
}
