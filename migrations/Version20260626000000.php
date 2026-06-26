<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260626000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Grant ROLE_SUPERADMIN to rvollenberg@valuemaritime.com';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE user SET roles = JSON('[\"ROLE_ADMIN\",\"ROLE_SUPERADMIN\"]') WHERE email = 'rvollenberg@valuemaritime.com'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE user SET roles = (SELECT json_group_array(value) FROM json_each(roles) WHERE value != 'ROLE_SUPERADMIN') WHERE email = 'rvollenberg@valuemaritime.com'");
    }
}
