<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260709072141 extends AbstractMigration
{
    private const MINOR_WORDS = [
        'a', 'an', 'and', 'as', 'at', 'but', 'by', 'for', 'in',
        'nor', 'of', 'on', 'or', 'so', 'the', 'to', 'up', 'yet',
    ];

    private const UPPERCASE_WORDS = [
        'pid', 'pfd', 'iso', 'api', 'iec', 'ansi', 'astm', 'bs', 'din', 'en',
        '3d', 'cad', 'ccs', 'occs', 'grp', 'grve', 'gre', 'co2', 'ga', 'tea',
    ];

    public function getDescription(): string
    {
        return 'Add doc_title_word table so title-casing rules are admin-configurable; seed it with the previously hardcoded lists';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE doc_title_word (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, word VARCHAR(30) NOT NULL, type VARCHAR(20) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX doc_title_word_unique_word ON doc_title_word (word)');

        foreach (self::MINOR_WORDS as $word) {
            $this->addSql('INSERT INTO doc_title_word (word, type) VALUES (?, ?)', [$word, 'minor']);
        }
        foreach (self::UPPERCASE_WORDS as $word) {
            $this->addSql('INSERT INTO doc_title_word (word, type) VALUES (?, ?)', [$word, 'uppercase']);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE doc_title_word');
    }
}
