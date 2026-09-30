<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260930130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add document_entry_alt_main_category: extra main categories a ledger entry is also valid under';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE document_entry_alt_main_category (document_entry_id BLOB NOT NULL, doc_main_category_id INTEGER NOT NULL, PRIMARY KEY (document_entry_id, doc_main_category_id), CONSTRAINT FK_578CFAA35F7B81B FOREIGN KEY (document_entry_id) REFERENCES document_entry (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_578CFAAED894874 FOREIGN KEY (doc_main_category_id) REFERENCES doc_main_category (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_578CFAA35F7B81B ON document_entry_alt_main_category (document_entry_id)');
        $this->addSql('CREATE INDEX IDX_578CFAAED894874 ON document_entry_alt_main_category (doc_main_category_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE document_entry_alt_main_category');
    }
}
