<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Ensures a ledger entry claims no document ID that another entry already claims, under its
 * default main category or any of its alternates. Also checks that alternates are only used
 * with sub categories that apply to all main categories.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class UniqueDocumentId extends Constraint
{
    public string $message = 'Document ID {{ id }} already exists in the ledger.';
    public string $scopedSubCategoryMessage = 'Sub category {{ code }} is limited to main category {{ main }}, so it cannot be valid under other main categories.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
