<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260629000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add unique constraint on document_entry to prevent duplicate document IDs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX document_entry_unique_id ON document_entry (subsidiary_id, main_category_id, doc_type_id, sub_category_id, doc_number, revision)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX document_entry_unique_id');
    }
}
