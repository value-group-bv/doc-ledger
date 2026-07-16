<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260716125417 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add audit_log_entry table for the superadmin-only audit log';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE audit_log_entry (id BLOB NOT NULL, actor VARCHAR(255) NOT NULL, event VARCHAR(64) NOT NULL, detail CLOB DEFAULT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX audit_log_entry_created_at_idx ON audit_log_entry (created_at)');
        $this->addSql('CREATE INDEX audit_log_entry_event_idx ON audit_log_entry (event)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE audit_log_entry');
    }
}
