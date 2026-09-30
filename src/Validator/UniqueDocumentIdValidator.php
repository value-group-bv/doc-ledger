<?php

namespace App\Validator;

use App\Entity\DocumentEntry;
use App\Repository\DocumentEntryRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class UniqueDocumentIdValidator extends ConstraintValidator
{
    public function __construct(private readonly DocumentEntryRepository $entries) {}

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueDocumentId) {
            throw new UnexpectedTypeException($constraint, UniqueDocumentId::class);
        }
        if (!$value instanceof DocumentEntry) {
            throw new UnexpectedValueException($value, DocumentEntry::class);
        }

        $scopedTo = $value->getSubCategory()->getMainCategory();
        if ($scopedTo !== null && !$value->getAlternateMainCategories()->isEmpty()) {
            $this->context->buildViolation($constraint->scopedSubCategoryMessage)
                ->setParameter('{{ code }}', $value->getSubCategory()->getFormattedCode())
                ->setParameter('{{ main }}', $scopedTo->getCode())
                ->atPath('alternateMainCategories')
                ->addViolation();
        }

        foreach ($this->entries->findConflictingDocumentIds($value) as $documentId) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ id }}', $documentId)
                ->atPath('docNumber')
                ->addViolation();
        }
    }
}
