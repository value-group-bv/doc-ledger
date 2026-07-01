<?php

namespace App\Controller;

use App\Entity\DocMainCategory;
use App\Entity\DocSubCategory;
use App\Entity\DocSubsidiary;
use App\Entity\DocType;
use App\Repository\DocMainCategoryRepository;
use App\Repository\DocPredefinedNumberRepository;
use App\Repository\DocSubCategoryRepository;
use App\Repository\DocSubsidiaryRepository;
use App\Repository\DocTypeRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin', name: 'admin_')]
class AdminController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    #[Route('/', name: 'index')]
    public function index(
        Request $request,
        DocSubsidiaryRepository $subsidiaries,
        DocMainCategoryRepository $mainCats,
        DocTypeRepository $docTypes,
        DocSubCategoryRepository $subCats,
        DocPredefinedNumberRepository $predefined,
        UserRepository $users,
    ): Response {
        return $this->render('admin/index.html.twig', [
            'subsidiaries' => $subsidiaries->findBy([], ['sortOrder' => 'ASC']),
            'mainCategories' => $mainCats->findBy([], ['code' => 'ASC']),
            'docTypes' => $docTypes->findBy([], ['sortOrder' => 'ASC']),
            'subCategories' => $subCats->findBy([], ['docType' => 'ASC', 'code' => 'ASC']),
            'predefinedNumbers' => $predefined->findBy([], ['subCategory' => 'ASC', 'code' => 'ASC']),
            'users' => $users->findBy([], ['createdAt' => 'DESC']),
            'editSubcatId' => (int) $request->query->get('editSubcat', 0),
        ]);
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

    #[Route('/subsidiary/{id}/delete', name: 'subsidiary_delete', methods: ['POST'])]
    public function subsidiaryDelete(int $id): Response
    {
        $entity = $this->em->find(DocSubsidiary::class, $id);
        if (!$entity) {
            return $this->redirectToRoute('admin_index');
        }

        if ($entity->getDocumentEntries()->count() > 0) {
            $this->addFlash('error', "Cannot delete subsidiary '{$entity->getCode()}' — it has {$entity->getDocumentEntries()->count()} linked document(s). Delete or reassign documents first.");
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

        if ($code && $description) {
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

    #[Route('/maincat/{id}/delete', name: 'maincat_delete', methods: ['POST'])]
    public function maincatDelete(int $id): Response
    {
        $entity = $this->em->find(DocMainCategory::class, $id);
        if ($entity) {
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
        if ($entity) {
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
        return $this->redirectToRoute('admin_index', ['editSubcat' => $id]);
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
        if ($entity) {
            $this->em->remove($entity);
            $this->em->flush();
            $this->addFlash('success', 'Sub category deleted.');
        }
        return $this->redirectToRoute('admin_index');
    }

    // ── Predefined numbers ────────────────────────────────────────────────────

    #[Route('/predefined/new', name: 'predefined_new', methods: ['POST'])]
    public function predefinedNew(Request $request, DocSubCategoryRepository $subCats): Response
    {
        $subCatId = (int) $request->request->get('subCategoryId', 0);
        $code = (int) $request->request->get('code', 0);
        $description = trim((string) $request->request->get('description', ''));
        $subCategory = $subCats->find($subCatId);

        if ($subCategory && $code >= 0 && $description) {
            $entity = new \App\Entity\DocPredefinedNumber();
            $entity->setCode($code)->setDescription($description)->setSubCategory($subCategory);
            $this->em->persist($entity);
            try {
                $this->em->flush();
                $this->addFlash('success', \sprintf("Predefined number %03d added.", $code));
            } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException) {
                $this->em->clear();
                $this->addFlash('error', \sprintf("Predefined number %03d already exists for this sub category.", $code));
            }
        }

        return $this->redirectToRoute('admin_index');
    }

    #[Route('/predefined/{id}/delete', name: 'predefined_delete', methods: ['POST'])]
    public function predefinedDelete(int $id): Response
    {
        $entity = $this->em->find(\App\Entity\DocPredefinedNumber::class, $id);
        if ($entity) {
            $this->em->remove($entity);
            $this->em->flush();
            $this->addFlash('success', 'Predefined number deleted.');
        }
        return $this->redirectToRoute('admin_index');
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
