<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261002120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add reserved_feasibility_code: codes the feasibility code API never hands out (e.g. used by products)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE reserved_feasibility_code (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code VARCHAR(3) NOT NULL, description VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E1B352A177153098 ON reserved_feasibility_code (code)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE reserved_feasibility_code');
    }
}
