<?php

namespace App\Service;

use App\Entity\DocTitleWord;
use App\Repository\DocTitleWordRepository;

/** Sanitises and title-cases document titles using the admin-configurable word lists */
class TitleCaseFormatter
{
    public function __construct(private readonly DocTitleWordRepository $titleWords) {}

    public function format(string $title): string
    {
        $title = trim(preg_replace('/[^A-Za-z0-9 ]/', '', $title));
        $title = preg_replace('/\s+/', ' ', $title);

        $minorWords = $this->titleWords->findWordsByType(DocTitleWord::TYPE_MINOR);
        $uppercaseWords = $this->titleWords->findWordsByType(DocTitleWord::TYPE_UPPERCASE);

        $words = explode(' ', strtolower($title));
        $lastIndex = count($words) - 1;
        foreach ($words as $i => &$word) {
            if ($word === '') continue;

            if (in_array($word, $uppercaseWords, true)) {
                $word = strtoupper($word);
            } elseif ($i === 0 || $i === $lastIndex || !in_array($word, $minorWords, true)) {
                $word = ucfirst($word);
            }
        }
        unset($word);

        return implode(' ', $words);
    }
}
