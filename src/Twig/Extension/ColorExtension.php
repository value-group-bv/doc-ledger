<?php

namespace App\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ColorExtension extends AbstractExtension
{
    // Primary (text/border) and secondary (background) colors per subsidiary prefix
    private const SUBSIDIARY_COLORS = [
        'VM' => ['#84bd00', '#E6F2FF'],
        'VC' => ['#2f52a0', '#FFE6D5'],
        'VS' => ['#007e97', '#E6F9F0'],
        'VG' => ['#0f5c80', '#F0F0F0'],
    ];

    public function getFunctions(): array
    {
        return [
            new TwigFunction('doc_type_badge_style', $this->docTypeBadgeStyle(...)),
            new TwigFunction('subsidiary_doc_number_style', $this->subsidiaryDocNumberStyle(...)),
        ];
    }

    /**
     * Returns an inline style for the document number badge based on the subsidiary code.
     * Matches on the first two characters (VM, VC, VS, VG) against the brand palette.
     */
    public function subsidiaryDocNumberStyle(string $subsidiaryCode): string
    {
        $prefix = strtoupper(substr($subsidiaryCode, 0, 2));
        [$primary] = self::SUBSIDIARY_COLORS[$prefix] ?? ['#4b5563'];

        return "background-color: {$primary}; border-color: {$primary}; color: #fff;";
    }

    /**
     * Returns an inline style string that gives each doc type code a unique,
     * stable color derived from the string itself.
     */
    public function docTypeBadgeStyle(string $code): string
    {
        $hash = crc32(strtoupper($code));
        $hue = abs($hash) % 360;

        return sprintf(
            'background-color: hsl(%d, 55%%, 32%%); border-color: hsl(%d, 55%%, 25%%); color: #fff;',
            $hue,
            $hue,
        );
    }
}
