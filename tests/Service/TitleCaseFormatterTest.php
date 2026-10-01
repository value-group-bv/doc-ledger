<?php

namespace App\Tests\Service;

use App\Entity\DocTitleWord;
use App\Repository\DocTitleWordRepository;
use App\Service\TitleCaseFormatter;
use PHPUnit\Framework\TestCase;

class TitleCaseFormatterTest extends TestCase
{
    private function formatter(array $minor, array $uppercase): TitleCaseFormatter
    {
        $repo = $this->createStub(DocTitleWordRepository::class);
        $repo->method('findWordsByType')->willReturnMap([
            [DocTitleWord::TYPE_MINOR, $minor],
            [DocTitleWord::TYPE_UPPERCASE, $uppercase],
        ]);

        return new TitleCaseFormatter($repo);
    }

    public function testFormat(): void
    {
        $f = $this->formatter(['of', 'the'], ['pid']);
        $this->assertSame('The PID of the Plant', $f->format('  the pid of   THE plant!'));
        $this->assertSame('Plant Of', $f->format('plant of'));
    }

    public function testRecaseOnlyTouchesGivenWords(): void
    {
        $f = $this->formatter(['of', 'the'], ['pid', 'hvac']);
        // "hvac" newly uppercase; "ABC" and "of-site" must stay as they are
        $this->assertSame('ABC HVAC of-site Hvacs', $f->recase('ABC Hvac of-site Hvacs', ['hvac']));
        // "pid" removed from uppercase list
        $f = $this->formatter(['of', 'the'], []);
        $this->assertSame('Pid of the Pid', $f->recase('PID of the PID', ['pid']));
        // "of" newly minor, except as first/last word
        $this->assertSame('Of Value of Of', $f->recase('Of Value Of Of', ['of']));
    }
}
