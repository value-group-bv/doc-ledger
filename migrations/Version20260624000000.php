<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260624000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add comments field to document_entry';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document_entry ADD COLUMN comments CLOB DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE document_entry DROP COLUMN comments');
    }
}
