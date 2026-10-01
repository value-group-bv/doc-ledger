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

        return $this->applyCasing($title, null);
    }

    /**
     * Re-applies casing to only the given (lowercase) words in an existing title, leaving
     * everything else untouched. Used to update stored titles after the word lists change.
     *
     * @param string[] $words
     */
    public function recase(string $title, array $words): string
    {
        return $this->applyCasing($title, $words);
    }

    /** @param string[]|null $onlyWords null cases every word */
    private function applyCasing(string $title, ?array $onlyWords): string
    {
        $minorWords = $this->titleWords->findWordsByType(DocTitleWord::TYPE_MINOR);
        $uppercaseWords = $this->titleWords->findWordsByType(DocTitleWord::TYPE_UPPERCASE);

        $words = explode(' ', $title);
        $lastIndex = count($words) - 1;
        foreach ($words as $i => &$word) {
            $lower = strtolower($word);
            if ($lower === '' || ($onlyWords !== null && !in_array($lower, $onlyWords, true))) continue;

            if (in_array($lower, $uppercaseWords, true)) {
                $word = strtoupper($lower);
            } elseif ($i === 0 || $i === $lastIndex || !in_array($lower, $minorWords, true)) {
                $word = ucfirst($lower);
            } else {
                $word = $lower;
            }
        }
        unset($word);

        return implode(' ', $words);
    }
}
