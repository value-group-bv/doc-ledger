<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260529000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add subsidiary_id to doc_sub_category, unique constraint becomes (code, doc_type_id, subsidiary_id)';
    }

    public function up(Schema $schema): void
    {
        // SQLite requires table recreation to change constraints
        $this->addSql('CREATE TABLE doc_sub_category_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            code INTEGER NOT NULL,
            description VARCHAR(150) NOT NULL,
            doc_type_id INTEGER NOT NULL,
            subsidiary_id INTEGER DEFAULT NULL,
            CONSTRAINT FK_E0D756A1AAE044D FOREIGN KEY (doc_type_id) REFERENCES doc_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE,
            CONSTRAINT FK_E0D756A1D4A7BDA2 FOREIGN KEY (subsidiary_id) REFERENCES doc_subsidiary (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE
        )');
        $this->addSql('INSERT INTO doc_sub_category_new (id, code, description, doc_type_id) SELECT id, code, description, doc_type_id FROM doc_sub_category');
        $this->addSql('DROP TABLE doc_sub_category');
        $this->addSql('ALTER TABLE doc_sub_category_new RENAME TO doc_sub_category');
        $this->addSql('CREATE INDEX IDX_E0D756A1AAE044D ON doc_sub_category (doc_type_id)');
        $this->addSql('CREATE INDEX IDX_E0D756A1D4A7BDA2 ON doc_sub_category (subsidiary_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E0D756A771530981AAE044DD4A7BDA2 ON doc_sub_category (code, doc_type_id, subsidiary_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE doc_sub_category_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            code INTEGER NOT NULL,
            description VARCHAR(150) NOT NULL,
            doc_type_id INTEGER NOT NULL,
            CONSTRAINT FK_E0D756A1AAE044D FOREIGN KEY (doc_type_id) REFERENCES doc_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        )');
        $this->addSql('INSERT INTO doc_sub_category_new (id, code, description, doc_type_id) SELECT id, code, description, doc_type_id FROM doc_sub_category');
        $this->addSql('DROP TABLE doc_sub_category');
        $this->addSql('ALTER TABLE doc_sub_category_new RENAME TO doc_sub_category');
        $this->addSql('CREATE INDEX IDX_E0D756A1AAE044D ON doc_sub_category (doc_type_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E0D756A771530981AAE044D ON doc_sub_category (code, doc_type_id)');
    }
}
