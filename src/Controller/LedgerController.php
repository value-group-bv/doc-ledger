<?php

namespace App\Controller;

use App\Form\DocumentEntryType;
use App\Repository\DocumentEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[IsGranted('ROLE_USER')]
#[Route('/ledger', name: 'ledger_')]
class LedgerController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly DocumentEntryRepository $entries,
    ) {}

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('ledger/index.html.twig');
    }

    #[Route('/new', name: 'new')]
    public function new(): Response
    {
        return $this->render('ledger/new.html.twig');
    }

    #[Route('/{id}/edit', name: 'edit')]
    public function edit(Uuid $id, Request $request): Response
    {
        $entry = $this->entries->find($id);
        if (!$entry) throw $this->createNotFoundException();

        $this->denyAccessUnlessGranted('edit_entry', $entry);

        $form = $this->createForm(DocumentEntryType::class, $entry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entry->setReferenceCode($entry->getMainCategory()->getReferenceCode());
            $this->em->flush();
            $this->addFlash('success', "Entry updated: {$entry->getDocumentNumber()}");
            return $this->redirectToRoute('ledger_index');
        }

        return $this->render('ledger/edit.html.twig', [
            'entry' => $entry,
            'form'  => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Uuid $id, Request $request): Response
    {
        $entry = $this->entries->find($id);
        if (!$entry) throw $this->createNotFoundException();

        $this->denyAccessUnlessGranted('delete_entry', $entry);

        if ($this->isCsrfTokenValid('delete_entry_' . $id, $request->request->get('_token'))) {
            $this->em->remove($entry);
            $this->em->flush();
            $this->addFlash('success', 'Entry deleted.');
        }

        return $this->redirectToRoute('ledger_index');
    }
}
