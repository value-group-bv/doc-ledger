<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260529000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add reference_code to doc_main_category';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE doc_main_category ADD COLUMN reference_code VARCHAR(3) DEFAULT '000' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        // SQLite does not support DROP COLUMN; recreate without it if needed
        $this->throwIrreversibleMigrationException();
    }
}
