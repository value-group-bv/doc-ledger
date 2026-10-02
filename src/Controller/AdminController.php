<?php

namespace App\Controller;

use App\Entity\DocMainCategory;
use App\Entity\DocSubCategory;
use App\Entity\DocSubsidiary;
use App\Entity\DocTitleWord;
use App\Entity\DocType;
use App\Entity\DocumentEntry;
use App\Entity\FeasibilityCode;
use App\Entity\ReservedFeasibilityCode;
use App\Entity\User;
use App\Repository\DocMainCategoryRepository;
use App\Repository\DocSubCategoryRepository;
use App\Repository\DocSubsidiaryRepository;
use App\Repository\DocTitleWordRepository;
use App\Repository\DocTypeRepository;
use App\Repository\DocumentEntryRepository;
use App\Repository\FeasibilityCodeRepository;
use App\Repository\ReservedFeasibilityCodeRepository;
use App\Repository\UserRepository;
use App\Service\AuditLogger;
use App\Service\TitleCaseFormatter;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin', name: 'admin_')]
class AdminController extends AbstractController
{
    private const FC_PAGE_SIZE = 25;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AuditLogger $auditLogger,
    ) {}

    #[Route('', name: 'index')]
    public function index(
        Request $request,
        DocSubsidiaryRepository $subsidiaries,
        DocMainCategoryRepository $mainCats,
        DocTypeRepository $docTypes,
        DocSubCategoryRepository $subCats,
        UserRepository $users,
        FeasibilityCodeRepository $feasibilityCodes,
        DocTitleWordRepository $titleWords,
        ReservedFeasibilityCodeRepository $reservedCodes,
    ): Response {
        $editFeasibilityCodeId = (int) $request->query->get('editFeasibilityCode', 0);
        $fcTotalPages = max(1, (int) ceil($feasibilityCodes->count([]) / self::FC_PAGE_SIZE));
        $fcPage = $request->query->has('fcPage')
            ? (int) $request->query->get('fcPage')
            : ($editFeasibilityCodeId ? $feasibilityCodes->findPageNumberOf($editFeasibilityCodeId, self::FC_PAGE_SIZE) : 1);
        // Clamped, so deleting the last code on the last page still lands on a page with codes
        $fcPage = min(max(1, $fcPage), $fcTotalPages);

        return $this->render('admin/index.html.twig', [
            'subsidiaries' => $subsidiaries->findBy([], ['sortOrder' => 'ASC']),
            'mainCategories' => $mainCats->findBy([], ['code' => 'ASC']),
            'docTypes' => $docTypes->findBy([], ['sortOrder' => 'ASC']),
            'subCategories' => $subCats->findBy([], ['docType' => 'ASC', 'code' => 'ASC']),
            'users' => $users->findBy([], ['createdAt' => 'DESC']),
            'feasibilityCodes' => $feasibilityCodes->findPage($fcPage, self::FC_PAGE_SIZE),
            'fcPage' => $fcPage,
            'fcTotalPages' => $fcTotalPages,
            'reservedCodes' => $reservedCodes->findBy([], ['code' => 'ASC']),
            'minorWords' => $titleWords->findBy(['type' => DocTitleWord::TYPE_MINOR], ['word' => 'ASC']),
            'uppercaseWords' => $titleWords->findBy(['type' => DocTitleWord::TYPE_UPPERCASE], ['word' => 'ASC']),
            'editMaincatId' => (int) $request->query->get('editMaincat', 0),
            'editSubcatId' => (int) $request->query->get('editSubcat', 0),
            'editFeasibilityCodeId' => $editFeasibilityCodeId,
        ]);
    }

    #[Route('/feasibility-codes/export', name: 'feasibility_codes_export')]
    public function feasibilityCodesExport(FeasibilityCodeRepository $feasibilityCodes): StreamedResponse
    {
        $entries = $feasibilityCodes->findAllOrderedByCreatedAt();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Feasibility Codes');

        $headers = ['Code', 'Title', 'Requestor', 'Subsidiary', 'Created At'];
        $sheet->fromArray([$headers], null, 'A1');

        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E2']],
        ];
        $sheet->getStyle('A1:E1')->applyFromArray($headerStyle);
        $sheet->freezePane('A2');

        $row = 2;
        foreach ($entries as $entry) {
            $sheet->fromArray([[
                $entry->getCode(),
                $entry->getTitle(),
                $entry->getRequestor(),
                $entry->getSubsidiary()->getCode(),
                $entry->getCreatedAt()->format('Y-m-d H:i'),
            ]], null, 'A' . $row, true);
            $row++;
        }

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'FeasibilityCodesExport-' . date('Y-m-d') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        $response = new StreamedResponse(static function () use ($writer): void {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    #[Route('/feasibility-code/{id}/edit', name: 'feasibility_code_edit', methods: ['GET'])]
    public function feasibilityCodeEdit(int $id): Response
    {
        return $this->redirectToRoute('admin_index', ['editFeasibilityCode' => $id, '_fragment' => "fc-$id"]);
    }

    #[Route('/feasibility-code/{id}/update', name: 'feasibility_code_update', methods: ['POST'])]
    public function feasibilityCodeUpdate(int $id, Request $request, FeasibilityCodeRepository $feasibilityCodes): Response
    {
        $fcPage = $request->query->getInt('fcPage', 1);
        $entity = $feasibilityCodes->find($id);
        $title = trim((string) $request->request->get('title', ''));

        if (!$entity) {
            $this->addFlash('error', 'Feasibility code not found.');
        } elseif (!$title) {
            $this->addFlash('error', 'Title is required.');
        } else {
            $entity->setTitle($title);
            $this->em->flush();
            $this->addFlash('success', "Feasibility code '{$entity->getCode()}' updated.");

            // Back to the (highlighted) row; errors stay at the top where their message shows
            return $this->redirectToRoute('admin_index', ['fcPage' => $fcPage, '_fragment' => "fc-$id"]);
        }

        return $this->redirectToRoute('admin_index', ['fcPage' => $fcPage]);
    }

    #[Route('/feasibility-code/{id}/delete', name: 'feasibility_code_delete', methods: ['POST'])]
    public function feasibilityCodeDelete(int $id, Request $request, FeasibilityCodeRepository $feasibilityCodes): Response
    {
        $fcPage = $request->query->getInt('fcPage', 1);
        $entity = $feasibilityCodes->find($id);
        if (!$entity) {
            return $this->redirectToRoute('admin_index', ['fcPage' => $fcPage]);
        }

        $code = $entity->getCode();
        $this->em->remove($entity);
        $this->em->flush();
        $this->addFlash('success', "Feasibility code '{$code}' deleted.");

        return $this->redirectToRoute('admin_index', ['fcPage' => $fcPage]);
    }

    // ── Reserved feasibility codes ────────────────────────────────────────────

    #[Route('/reserved-code/new', name: 'reserved_code_new', methods: ['POST'])]
    public function reservedCodeNew(Request $request, FeasibilityCodeRepository $feasibilityCodes): Response
    {
        $code = strtoupper(trim((string) $request->request->get('code', '')));
        $description = trim((string) $request->request->get('description', ''));

        if (!preg_match('/^[A-Z]{3}$/', $code)) {
            $this->addFlash('error', 'Reserved code must be exactly 3 letters.');
        } elseif (!$description) {
            $this->addFlash('error', 'Description is required.');
        } elseif ($taken = $feasibilityCodes->findOneBy(['code' => $code])) {
            $this->addFlash('error', "Code '{$code}' is already in use by feasibility project '{$taken->getTitle()}'.");
        } else {
            $entity = new ReservedFeasibilityCode();
            $entity->setCode($code)->setDescription($description);
            $this->em->persist($entity);
            try {
                $this->em->flush();
                $this->addFlash('success', "Code '{$code}' reserved.");
            } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException) {
                $this->em->clear();
                $this->addFlash('error', "Code '{$code}' is already reserved.");
            }
        }

        return $this->redirectToRoute('admin_index');
    }

    #[Route('/reserved-code/{id}/delete', name: 'reserved_code_delete', methods: ['POST'])]
    public function reservedCodeDelete(int $id): Response
    {
        $entity = $this->em->find(ReservedFeasibilityCode::class, $id);
        if ($entity) {
            $this->em->remove($entity);
            $this->em->flush();
            $this->addFlash('success', "Code '{$entity->getCode()}' is no longer reserved.");
        }

        return $this->redirectToRoute('admin_index');
    }

    // ── Subsidiaries ──────────────────────────────────────────────────────────

    #[Route('/subsidiary/new', name: 'subsidiary_new', methods: ['POST'])]
    public function subsidiaryNew(Request $request): Response
    {
        $code = trim((string) $request->request->get('code', ''));
        $description = trim((string) $request->request->get('description', ''));

        if (!$code || !$description) {
            $this->addFlash('error', 'Code and description are required.');
            return $this->redirectToRoute('admin_index');
        }

        if (!preg_match('/^[A-Za-z]{2}$/', $code)) {
            $this->addFlash('error', 'Subsidiary code must be exactly 2 letters (no numbers or special characters).');
            return $this->redirectToRoute('admin_index');
        }

        $entity = new DocSubsidiary();
        $entity->setCode(strtoupper($code))->setDescription($description);
        $this->em->persist($entity);
        try {
            $this->em->flush();
            $this->addFlash('success', "Subsidiary '{$code}' added.");
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException) {
            $this->em->clear();
            $this->addFlash('error', "Subsidiary code '{$code}' already exists.");
        }

        return $this->redirectToRoute('admin_index');
    }

    #[Route('/subsidiary/{id}/apikey/generate', name: 'subsidiary_apikey_generate', methods: ['POST'])]
    public function subsidiaryApiKeyGenerate(int $id): Response
    {
        $entity = $this->em->find(DocSubsidiary::class, $id);
        if (!$entity) {
            return $this->redirectToRoute('admin_index');
        }

        $plaintextKey = sprintf('fk_%s_%s', strtolower($entity->getCode()), bin2hex(random_bytes(24)));
        $entity->setApiKey($plaintextKey);
        $this->em->flush();

        $this->addFlash('success', "New API key for {$entity->getCode()}: {$plaintextKey} - Copy it now, it won't be shown again.");

        return $this->redirectToRoute('admin_index');
    }

    #[Route('/subsidiary/{id}/delete', name: 'subsidiary_delete', methods: ['POST'])]
    public function subsidiaryDelete(int $id): Response
    {
        $entity = $this->em->find(DocSubsidiary::class, $id);
        if (!$entity) {
            return $this->redirectToRoute('admin_index');
        }

        $usage = $this->describeUsage([
            'document(s)' => $this->countWhere(DocumentEntry::class, 'x.subsidiary = :v', $entity),
            'feasibility code(s)' => $this->countWhere(FeasibilityCode::class, 'x.subsidiary = :v', $entity),
            'sub category(ies)' => $this->countWhere(DocSubCategory::class, 'x.subsidiary = :v', $entity),
        ]);
        if ($usage) {
            $this->addFlash('error', "Cannot delete subsidiary '{$entity->getCode()}': it is still used by {$usage}.");
            return $this->redirectToRoute('admin_index');
        }

        $this->em->remove($entity);
        $this->em->flush();
        $this->addFlash('success', "Subsidiary '{$entity->getCode()}' deleted.");
        return $this->redirectToRoute('admin_index');
    }

    // ── Main categories ───────────────────────────────────────────────────────

    #[Route('/maincat/new', name: 'maincat_new', methods: ['POST'])]
    public function maincatNew(Request $request): Response
    {
        $code = trim((string) $request->request->get('code', ''));
        $description = trim((string) $request->request->get('description', ''));

        $referenceCode = \in_array($request->request->get('referenceCode'), ['000', 'AAA', 'PRO'], true)
            ? $request->request->get('referenceCode')
            : '000';

        // Explicit checks: '0' is a valid code but falsy in PHP
        if (!preg_match('/^\d$/', $code)) {
            $this->addFlash('error', 'Code must be a single digit (0-9).');
        } elseif ($description === '') {
            $this->addFlash('error', 'Description is required.');
        } else {
            $entity = new DocMainCategory();
            $entity->setCode($code)->setDescription($description)->setReferenceCode($referenceCode);
            $this->em->persist($entity);
            try {
                $this->em->flush();
                $this->addFlash('success', "Main category '{$code}' added.");
            } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException) {
                $this->em->clear();
                $this->addFlash('error', "Main category code '{$code}' already exists.");
            }
        }

        return $this->redirectToRoute('admin_index');
    }

    #[Route('/maincat/{id}/refcode', name: 'maincat_set_refcode', methods: ['POST'])]
    public function maincatSetRefcode(int $id, Request $request): Response
    {
        $entity = $this->em->find(DocMainCategory::class, $id);
        $value = $request->request->get('referenceCode');

        if ($entity && \in_array($value, ['000', 'AAA', 'PRO'], true)) {
            $entity->setReferenceCode($value);
            $this->em->flush();
        }

        return $this->redirectToRoute('admin_index');
    }

    /** Only the description can be changed: the code is part of every document ID */
    #[Route('/maincat/{id}/update', name: 'maincat_update', methods: ['POST'])]
    public function maincatUpdate(int $id, Request $request): Response
    {
        $entity = $this->em->find(DocMainCategory::class, $id);
        $description = trim((string) $request->request->get('description', ''));

        if (!$entity) {
            $this->addFlash('error', 'Main category not found.');
        } elseif (!$description) {
            $this->addFlash('error', 'Description is required.');
        } else {
            $entity->setDescription($description);
            $this->em->flush();
            $this->addFlash('success', "Main category '{$entity->getCode()}' updated.");

            // Back to the (highlighted) row; errors stay at the top where their message shows
            return $this->redirectToRoute('admin_index', ['_fragment' => "maincat-$id"]);
        }

        return $this->redirectToRoute('admin_index');
    }

    #[Route('/maincat/{id}/delete', name: 'maincat_delete', methods: ['POST'])]
    public function maincatDelete(int $id): Response
    {
        $entity = $this->em->find(DocMainCategory::class, $id);
        $usage = $entity ? $this->describeUsage([
            'document(s)' => $this->countWhere(DocumentEntry::class, 'x.mainCategory = :v', $entity),
            'document(s) as alternate' => $this->countWhere(DocumentEntry::class, ':v MEMBER OF x.alternateMainCategories', $entity),
            'sub category(ies)' => $this->countWhere(DocSubCategory::class, 'x.mainCategory = :v', $entity),
        ]) : null;
        if ($usage) {
            $this->addFlash('error', "Cannot delete main category '{$entity->getCode()}': it is still used by {$usage}.");
        } elseif ($entity) {
            $this->em->remove($entity);
            $this->em->flush();
            $this->addFlash('success', 'Main category deleted.');
        }
        return $this->redirectToRoute('admin_index');
    }

    // ── Doc types ─────────────────────────────────────────────────────────────

    #[Route('/doctype/new', name: 'doctype_new', methods: ['POST'])]
    public function doctypeNew(Request $request): Response
    {
        $code = trim((string) $request->request->get('code', ''));
        $description = trim((string) $request->request->get('description', ''));

        $code = preg_replace('/[^A-Za-z]/', '', $code);

        if ($code && $description) {
            $entity = new DocType();
            $entity->setCode(strtoupper($code))->setDescription($description);
            $this->em->persist($entity);
            try {
                $this->em->flush();
                $this->addFlash('success', "Doc type '{$code}' added.");
            } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException) {
                $this->em->clear();
                $this->addFlash('error', "Doc type code '{$code}' already exists.");
            }
        }

        return $this->redirectToRoute('admin_index');
    }

    #[Route('/doctype/{id}/delete', name: 'doctype_delete', methods: ['POST'])]
    public function doctypeDelete(int $id): Response
    {
        $entity = $this->em->find(DocType::class, $id);
        $usage = $entity ? $this->describeUsage([
            'document(s)' => $this->countWhere(DocumentEntry::class, 'x.docType = :v', $entity),
            'sub category(ies)' => $this->countWhere(DocSubCategory::class, 'x.docType = :v', $entity),
        ]) : null;
        if ($usage) {
            $this->addFlash('error', "Cannot delete doc type '{$entity->getCode()}': it is still used by {$usage}.");
        } elseif ($entity) {
            $this->em->remove($entity);
            $this->em->flush();
            $this->addFlash('success', 'Doc type deleted.');
        }
        return $this->redirectToRoute('admin_index');
    }

    // ── Sub categories ────────────────────────────────────────────────────────

    #[Route('/subcat/new', name: 'subcat_new', methods: ['POST'])]
    public function subcatNew(Request $request, DocTypeRepository $docTypes, DocMainCategoryRepository $mainCats, DocSubsidiaryRepository $subsidiaries): Response
    {
        $docTypeId = (int) $request->request->get('docTypeId', 0);
        $mainCategoryId = (int) $request->request->get('mainCategoryId', 0);
        $subsidiaryId = (int) $request->request->get('subsidiaryId', 0);
        $code = (int) $request->request->get('code', -1);
        $description = trim((string) $request->request->get('description', ''));
        $docType = $docTypes->find($docTypeId);
        $mainCategory = $mainCategoryId ? $mainCats->find($mainCategoryId) : null;
        $subsidiary = $subsidiaryId ? $subsidiaries->find($subsidiaryId) : null;

        if (!$docType) {
            $this->addFlash('error', 'Document type is required.');
        } elseif ($code < 0 || $code > 999) {
            $this->addFlash('error', 'Code is required and must be between 0 and 999.');
        } elseif (!$description) {
            $this->addFlash('error', 'Description is required.');
        } else {
            $entity = new DocSubCategory();
            $entity->setCode($code)->setDescription($description)->setDocType($docType)->setMainCategory($mainCategory)->setSubsidiary($subsidiary);
            $this->em->persist($entity);
            try {
                $this->em->flush();
                $this->addFlash('success', "Sub category {$code} added.");
            } catch (\Exception $e) {
                $this->em->clear();
                if ($e instanceof \Doctrine\DBAL\Exception\UniqueConstraintViolationException) {
                    $this->addFlash('error', "Sub category {$code} already exists for this document type, main category and subsidiary.");
                } else {
                    $this->addFlash('error', "Error creating sub category: " . $e->getMessage());
                }
            }
        }

        return $this->redirectToRoute('admin_index');
    }

    #[Route('/subcat/{id}/edit', name: 'subcat_edit', methods: ['GET'])]
    public function subcatEdit(int $id): Response
    {
        return $this->redirectToRoute('admin_index', ['editSubcat' => $id, '_fragment' => "subcat-$id"]);
    }

    #[Route('/subcat/{id}/update', name: 'subcat_update', methods: ['POST'])]
    public function subcatUpdate(int $id, Request $request, DocTypeRepository $docTypes, DocMainCategoryRepository $mainCats, DocSubsidiaryRepository $subsidiaries): Response
    {
        $entity = $this->em->find(DocSubCategory::class, $id);
        $code = (int) $request->request->get('code', -1);
        $description = trim((string) $request->request->get('description', ''));
        $docTypeId = (int) $request->request->get('docTypeId', 0);
        $mainCategoryId = (int) $request->request->get('mainCategoryId', 0);
        $subsidiaryId = (int) $request->request->get('subsidiaryId', 0);
        $docType = $docTypes->find($docTypeId);
        $mainCategory = $mainCategoryId ? $mainCats->find($mainCategoryId) : null;
        $subsidiary = $subsidiaryId ? $subsidiaries->find($subsidiaryId) : null;

        if (!$entity) {
            $this->addFlash('error', 'Sub category not found.');
        } elseif ($code < 0 || $code > 999) {
            $this->addFlash('error', 'Code is required and must be between 0 and 999.');
        } elseif (!$description) {
            $this->addFlash('error', 'Description is required.');
        } elseif (!$docType) {
            $this->addFlash('error', 'Document type is required.');
        } else {
            $entity->setCode($code)->setDescription($description)->setDocType($docType)->setMainCategory($mainCategory)->setSubsidiary($subsidiary);
            try {
                $this->em->flush();
                $this->addFlash('success', "Sub category {$entity->getFormattedCode()} updated.");

                // Back to the (highlighted) row; errors stay at the top where their message shows
                return $this->redirectToRoute('admin_index', ['_fragment' => "subcat-$id"]);
            } catch (\Exception $e) {
                $this->em->clear();
                if ($e instanceof \Doctrine\DBAL\Exception\UniqueConstraintViolationException) {
                    $this->addFlash('error', "Sub category {$code} already exists for this document type, main category and subsidiary.");
                } else {
                    $this->addFlash('error', "Error updating sub category: " . $e->getMessage());
                }
            }
        }

        return $this->redirectToRoute('admin_index');
    }

    #[Route('/subcat/{id}/delete', name: 'subcat_delete', methods: ['POST'])]
    public function subcatDelete(int $id): Response
    {
        $entity = $this->em->find(DocSubCategory::class, $id);
        $usage = $entity ? $this->describeUsage([
            'document(s)' => $this->countWhere(DocumentEntry::class, 'x.subCategory = :v', $entity),
        ]) : null;
        if ($usage) {
            $this->addFlash('error', "Cannot delete sub category {$entity->getFormattedCode()}: it is still used by {$usage}.");
        } elseif ($entity) {
            $this->em->remove($entity);
            $this->em->flush();
            $this->addFlash('success', 'Sub category deleted.');
        }
        return $this->redirectToRoute('admin_index');
    }

    // ── Title casing words ───────────────────────────────────────────────────

    #[Route('/title-words/update', name: 'title_words_update', methods: ['POST'])]
    public function titleWordsUpdate(
        Request $request,
        DocTitleWordRepository $titleWords,
        DocumentEntryRepository $documentEntries,
        TitleCaseFormatter $titleCaseFormatter,
    ): Response
    {
        $type = $request->request->get('type');
        if (!\in_array($type, [DocTitleWord::TYPE_MINOR, DocTitleWord::TYPE_UPPERCASE], true)) {
            $this->addFlash('error', 'Invalid word list.');
            return $this->redirectToRoute('admin_index');
        }

        $otherType = $type === DocTitleWord::TYPE_MINOR ? DocTitleWord::TYPE_UPPERCASE : DocTitleWord::TYPE_MINOR;
        $otherWords = $titleWords->findWordsByType($otherType);

        $words = [];
        foreach (explode(',', (string) $request->request->get('words', '')) as $word) {
            $word = strtolower(trim($word));
            if ($word !== '' && preg_match('/^[a-z0-9]{1,30}$/', $word)) {
                $words[$word] = true;
            }
        }
        $words = array_keys($words);

        $skipped = array_intersect($words, $otherWords);
        $words = array_diff($words, $otherWords);

        $previousWords = $titleWords->findWordsByType($type);

        foreach ($titleWords->findBy(['type' => $type]) as $existing) {
            $this->em->remove($existing);
        }
        $this->em->flush();

        foreach ($words as $word) {
            $entity = new DocTitleWord();
            $entity->setWord($word)->setType($type);
            $this->em->persist($entity);
        }
        $this->em->flush();

        $added = array_diff($words, $previousWords);
        $removed = array_diff($previousWords, $words);
        $recased = 0;
        if ($added || $removed) {
            $changedWords = [...$added, ...$removed];
            foreach ($documentEntries->findByTitleContainingAny($changedWords) as $entry) {
                $title = $titleCaseFormatter->recase($entry->getTitle(), $changedWords);
                if ($title !== $entry->getTitle()) {
                    $entry->setTitle($title);
                    $recased++;
                }
            }
            $this->em->flush();

            $detail = "Updated {$type} title words";
            if ($added) {
                $detail .= ' (+' . implode(', ', $added) . ')';
            }
            if ($removed) {
                $detail .= ' (-' . implode(', ', $removed) . ')';
            }
            $detail .= ", recased {$recased} document title(s)";
            $user = $this->getUser();
            if ($user instanceof User) {
                $this->auditLogger->log($user->getEmail(), 'setting.updated', $detail);
            }
        }

        if ($skipped) {
            $this->addFlash('error', 'Skipped (already used in the other list): ' . implode(', ', $skipped));
        }
        $this->addFlash('success', $recased
            ? "Word list updated, {$recased} document title(s) recased."
            : 'Word list updated.');

        return $this->redirectToRoute('admin_index');
    }

    // ── Delete guards ─────────────────────────────────────────────────────────
    // SQLite doesn't enforce foreign keys here, so deleting a row that's still referenced would
    // leave dangling IDs behind (and a broken ledger). Every delete checks its usages first.

    private function countWhere(string $entityClass, string $condition, object $value): int
    {
        return (int) $this->em->createQuery("SELECT COUNT(x) FROM {$entityClass} x WHERE {$condition}")
            ->setParameter('v', $value)
            ->getSingleScalarResult();
    }

    /** @param array<string, int> $counts label => count; returns e.g. "3 document(s), 1 sub category(ies)", or null if unused */
    private function describeUsage(array $counts): ?string
    {
        $parts = [];
        foreach ($counts as $label => $count) {
            if ($count > 0) {
                $parts[] = "{$count} {$label}";
            }
        }

        return $parts ? implode(', ', $parts) : null;
    }

    // ── Users ─────────────────────────────────────────────────────────────────

    #[Route('/user/{id}/toggle-admin', name: 'user_toggle_admin', methods: ['POST'])]
    public function userToggleAdmin(string $id, UserRepository $users): Response
    {
        $user = $users->find($id);
        if ($user && $user !== $this->getUser() && !\in_array('ROLE_SUPERADMIN', $user->getRoles(), true)) {
            $roles = $user->getRoles();
            if (\in_array('ROLE_ADMIN', $roles, true)) {
                $user->setRoles(array_filter($roles, fn($r) => $r !== 'ROLE_ADMIN' && $r !== 'ROLE_USER'));
            } else {
                $user->setRoles(array_unique([...$roles, 'ROLE_ADMIN']));
            }
            $this->em->flush();
            $this->addFlash('success', 'User role updated.');
        }
        return $this->redirectToRoute('admin_index');
    }
}
