<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261005120000 extends AbstractMigration
{
    /** Same code, description (ignoring case/spaces), main category and subsidiary: one sub category */
    private const SAME_SUB_CATEGORY = 'o.code = sc.code AND LOWER(TRIM(o.description)) = LOWER(TRIM(sc.description))'
        . ' AND o.main_category_id IS sc.main_category_id AND o.subsidiary_id IS sc.subsidiary_id';

    public function getDescription(): string
    {
        return 'Sub categories can belong to several doc types (doc_sub_category_doc_type); per-type duplicates are merged';
    }

    public function up(Schema $schema): void
    {
        // Map every sub category onto the oldest identical one, which keeps the doc types of all of them.
        // The new table is created only after doc_sub_category is rebuilt, so its ON DELETE CASCADE can't fire.
        $this->addSql('CREATE TEMPORARY TABLE __temp__keeper AS SELECT sc.id, sc.doc_type_id, (SELECT MIN(o.id) FROM doc_sub_category o WHERE ' . self::SAME_SUB_CATEGORY . ') AS keeper_id FROM doc_sub_category sc');
        $this->addSql('UPDATE document_entry SET sub_category_id = (SELECT k.keeper_id FROM __temp__keeper k WHERE k.id = document_entry.sub_category_id)');

        $this->addSql('CREATE TEMPORARY TABLE __temp__doc_sub_category AS SELECT id, code, description, main_category_id, subsidiary_id FROM doc_sub_category WHERE id IN (SELECT keeper_id FROM __temp__keeper)');
        $this->addSql('DROP TABLE doc_sub_category');
        $this->addSql('CREATE TABLE doc_sub_category (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code INTEGER NOT NULL, description VARCHAR(150) NOT NULL, main_category_id INTEGER DEFAULT NULL, subsidiary_id INTEGER DEFAULT NULL, CONSTRAINT FK_E0D756AC6C55574 FOREIGN KEY (main_category_id) REFERENCES doc_main_category (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E0D756AD4A7BDA2 FOREIGN KEY (subsidiary_id) REFERENCES doc_subsidiary (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO doc_sub_category (id, code, description, main_category_id, subsidiary_id) SELECT id, code, description, main_category_id, subsidiary_id FROM __temp__doc_sub_category');
        $this->addSql('DROP TABLE __temp__doc_sub_category');
        $this->addSql('CREATE INDEX IDX_E0D756AD4A7BDA2 ON doc_sub_category (subsidiary_id)');
        $this->addSql('CREATE INDEX IDX_E0D756AC6C55574 ON doc_sub_category (main_category_id)');

        $this->addSql('CREATE TABLE doc_sub_category_doc_type (doc_sub_category_id INTEGER NOT NULL, doc_type_id INTEGER NOT NULL, PRIMARY KEY (doc_sub_category_id, doc_type_id), CONSTRAINT FK_8D2431E1FF87AC6 FOREIGN KEY (doc_sub_category_id) REFERENCES doc_sub_category (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_8D2431E11AAE044D FOREIGN KEY (doc_type_id) REFERENCES doc_type (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_8D2431E1FF87AC6 ON doc_sub_category_doc_type (doc_sub_category_id)');
        $this->addSql('CREATE INDEX IDX_8D2431E11AAE044D ON doc_sub_category_doc_type (doc_type_id)');
        $this->addSql('INSERT INTO doc_sub_category_doc_type (doc_sub_category_id, doc_type_id) SELECT DISTINCT keeper_id, doc_type_id FROM __temp__keeper');
        $this->addSql('DROP TABLE __temp__keeper');
    }

    public function down(Schema $schema): void
    {
        // One row per doc type again: the first keeps the id, the others get a copy, and entries follow their doc type
        $this->addSql('CREATE TEMPORARY TABLE __temp__sc_type AS SELECT doc_sub_category_id, doc_type_id FROM doc_sub_category_doc_type');
        $this->addSql('DROP TABLE doc_sub_category_doc_type');
        $this->addSql('CREATE TEMPORARY TABLE __temp__doc_sub_category AS SELECT id, code, description, main_category_id, subsidiary_id, COALESCE((SELECT MIN(t.doc_type_id) FROM __temp__sc_type t WHERE t.doc_sub_category_id = doc_sub_category.id), (SELECT MIN(id) FROM doc_type)) AS doc_type_id FROM doc_sub_category');
        $this->addSql('DROP TABLE doc_sub_category');
        $this->addSql('CREATE TABLE doc_sub_category (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code INTEGER NOT NULL, description VARCHAR(150) NOT NULL, main_category_id INTEGER DEFAULT NULL, subsidiary_id INTEGER DEFAULT NULL, doc_type_id INTEGER NOT NULL, CONSTRAINT FK_E0D756AC6C55574 FOREIGN KEY (main_category_id) REFERENCES doc_main_category (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E0D756AD4A7BDA2 FOREIGN KEY (subsidiary_id) REFERENCES doc_subsidiary (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_E0D756A1AAE044D FOREIGN KEY (doc_type_id) REFERENCES doc_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO doc_sub_category (id, code, description, main_category_id, subsidiary_id, doc_type_id) SELECT id, code, description, main_category_id, subsidiary_id, doc_type_id FROM __temp__doc_sub_category');
        $this->addSql('DROP TABLE __temp__doc_sub_category');
        $this->addSql('INSERT INTO doc_sub_category (code, description, main_category_id, subsidiary_id, doc_type_id) SELECT sc.code, sc.description, sc.main_category_id, sc.subsidiary_id, t.doc_type_id FROM __temp__sc_type t JOIN doc_sub_category sc ON sc.id = t.doc_sub_category_id WHERE t.doc_type_id != sc.doc_type_id');
        $this->addSql('UPDATE document_entry SET sub_category_id = (SELECT MIN(o.id) FROM doc_sub_category o JOIN doc_sub_category sc ON ' . self::SAME_SUB_CATEGORY . ' WHERE sc.id = document_entry.sub_category_id AND o.doc_type_id = document_entry.doc_type_id) WHERE EXISTS (SELECT 1 FROM doc_sub_category o JOIN doc_sub_category sc ON ' . self::SAME_SUB_CATEGORY . ' WHERE sc.id = document_entry.sub_category_id AND o.doc_type_id = document_entry.doc_type_id)');
        $this->addSql('DROP TABLE __temp__sc_type');
        $this->addSql('CREATE INDEX IDX_E0D756AC6C55574 ON doc_sub_category (main_category_id)');
        $this->addSql('CREATE INDEX IDX_E0D756AD4A7BDA2 ON doc_sub_category (subsidiary_id)');
        $this->addSql('CREATE INDEX IDX_E0D756A1AAE044D ON doc_sub_category (doc_type_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E0D756A771530981AAE044DC6C55574D4A7BDA2 ON doc_sub_category (code, doc_type_id, main_category_id, subsidiary_id)');
    }
}
