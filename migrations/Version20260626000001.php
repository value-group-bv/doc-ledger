<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260626000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove redundant ROLE_ADMIN from rvollenberg (implied by ROLE_SUPERADMIN hierarchy)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE user SET roles = JSON('[\"ROLE_SUPERADMIN\"]') WHERE email = 'rvollenberg@valuemaritime.com'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE user SET roles = JSON('[\"ROLE_ADMIN\",\"ROLE_SUPERADMIN\"]') WHERE email = 'rvollenberg@valuemaritime.com'");
    }
}
