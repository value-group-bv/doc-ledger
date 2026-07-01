<?php

namespace App\Controller;

use App\Form\DocumentEntryType;
use App\Repository\DocumentEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Uid\Uuid;

#[IsGranted('ROLE_USER')]
class LedgerController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly DocumentEntryRepository $entries,
    ) {}

    #[Route('/', name: 'ledger_index')]
    public function index(): Response
    {
        return $this->render('ledger/index.html.twig');
    }

    #[Route('/ledger/export', name: 'ledger_export')]
    public function export(): StreamedResponse
    {
        $entries = $this->entries->createFilteredQueryBuilder()->getQuery()->getResult();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Ledger');

        $headers = ['Document ID', 'Title', 'Subsidiary', 'Reference Code', 'Document Type', 'Main Category', 'Sub Category', 'Document Number'];
        $sheet->fromArray([$headers], null, 'A1');

        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E2']],
        ];
        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);
        $sheet->freezePane('A2');

        $row = 2;
        foreach ($entries as $entry) {
            $sheet->fromArray([[
                $entry->getDocumentId(),
                $entry->getTitle(),
                $entry->getSubsidiary()->getCode(),
                $entry->getReferenceCode(),
                $entry->getDocType()->getCode(),
                $entry->getMainCategory()->getCode(),
                $entry->getSubCategory()->getFormattedCode(),
                $entry->getDocNumber(),
            ]], null, 'A' . $row, true);
            $row++;
        }

        if ($row > 2) {
            $sheet->getStyle('H2:H' . $row - 1)->getNumberFormat()->setFormatCode('000');
        }

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'ValueLedgerExport-' . date('Y-m-d') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        $response = new StreamedResponse(static function () use ($writer): void {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/ledger/new', name: 'ledger_new')]
    public function new(): Response
    {
        return $this->render('ledger/new.html.twig');
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/ledger/{id}/edit', name: 'ledger_edit')]
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
            $this->addFlash('success', "Entry updated: {$entry->getDocumentId()}");
            return $this->redirectToRoute('ledger_index');
        }

        return $this->render('ledger/edit.html.twig', [
            'entry' => $entry,
            'form'  => $form,
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/ledger/{id}/delete', name: 'ledger_delete', methods: ['POST'])]
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
