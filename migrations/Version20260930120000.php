<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260930120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop doc_predefined_number table (predefined document numbers feature removed)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE doc_predefined_number');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE doc_predefined_number (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, code INTEGER NOT NULL, description VARCHAR(200) NOT NULL, sub_category_id INTEGER NOT NULL, CONSTRAINT FK_91E06F2BF7BFE87C FOREIGN KEY (sub_category_id) REFERENCES doc_sub_category (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_91E06F2BF7BFE87C ON doc_predefined_number (sub_category_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_91E06F2B77153098F7BFE87C ON doc_predefined_number (code, sub_category_id)');
    }
}
