<?php

namespace App\Twig\Components;

use App\Entity\DocumentEntry;
use App\Form\DocumentEntryType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * The edit form, re-rendered live so dependent choices (sub categories per subsidiary, alternates
 * per main category) follow the selection. Saving is still a normal POST to LedgerController::edit.
 */
#[AsLiveComponent]
#[IsGranted('ROLE_ADMIN')]
class DocumentEntryForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public DocumentEntry $entry;

    protected function instantiateForm(): FormInterface
    {
        // Live requests are reachable directly, so repeat the page's check
        $this->denyAccessUnlessGranted('edit_entry', $this->entry);

        return $this->createForm(DocumentEntryType::class, $this->entry, [
            'action' => $this->generateUrl('ledger_edit', ['id' => $this->entry->getId()]),
        ]);
    }
}
