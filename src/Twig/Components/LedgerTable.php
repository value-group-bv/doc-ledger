<?php

namespace App\Twig\Components;

use App\Entity\DocMainCategory;
use App\Entity\DocSubsidiary;
use App\Entity\DocType;
use App\Repository\DocMainCategoryRepository;
use App\Repository\DocSubsidiaryRepository;
use App\Repository\DocTypeRepository;
use App\Repository\DocumentEntryRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class LedgerTable
{
    use DefaultActionTrait;

    private const PAGE_SIZE = 50;

    #[LiveProp(writable: true)]
    public string $search = '';

    #[LiveProp(writable: true)]
    public int $subsidiaryFilter = 0;

    #[LiveProp(writable: true)]
    public int $docTypeFilter = 0;

    #[LiveProp(writable: true)]
    public int $mainCategoryFilter = 0;

    #[LiveProp(writable: true)]
    public int $page = 1;

    #[LiveProp(writable: true)]
    public string $sortField = 'e.referenceCode';

    #[LiveProp(writable: true)]
    public string $sortDir = 'DESC';

    public function __construct(
        private readonly DocumentEntryRepository $entries,
        private readonly DocSubsidiaryRepository $subsidiaries,
        private readonly DocTypeRepository $docTypes,
        private readonly DocMainCategoryRepository $mainCategories
    ) {}

    public function getEntries(): Paginator
    {
        $qb = $this->entries->createFilteredQueryBuilder(
            search: $this->search ?: null,
            subsidiaryId: $this->subsidiaryFilter ?: null,
            docTypeId: $this->docTypeFilter ?: null,
            mainCategoryId: $this->mainCategoryFilter ?: null,
            sortField: $this->sortField,
            sortDir: $this->sortDir,
        );

        $qb->setFirstResult(($this->page - 1) * self::PAGE_SIZE)
           ->setMaxResults(self::PAGE_SIZE);

        return new Paginator($qb, fetchJoinCollection: true);
    }

    public function getTotalPages(): int
    {
        $paginator = $this->getEntries();
        return (int) ceil(\count($paginator) / self::PAGE_SIZE);
    }

    public function getTotalCount(): int
    {
        return \count($this->getEntries());
    }

    /** @return DocSubsidiary[] */
    public function getSubsidiaries(): array
    {
        return $this->subsidiaries->findBy([], ['sortOrder' => 'ASC']);
    }

    /** @return DocType[] */
    public function getDocTypes(): array
    {
        return $this->docTypes->findBy([], ['sortOrder' => 'ASC']);
    }

    /** @return DocMainCategory[] */
    public function getMainCategories(): array
    {
        return $this->mainCategories->findAll();
    }

    #[LiveAction]
    public function applyFilter(): void
    {
        $this->page = 1;
    }

    #[LiveAction]
    public function goToPage(#[LiveArg] int $page): void
    {
        $this->page = max(1, min($page, $this->getTotalPages()));
    }

    #[LiveAction]
    public function sort(#[LiveArg] string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDir = $this->sortDir === 'ASC' ? 'DESC' : 'ASC';
        } else {
            $this->sortField = $field;
            $this->sortDir = 'ASC';
        }
        $this->page = 1;
    }

    #[LiveAction]
    public function resetFilters(): void
    {
        $this->search = '';
        $this->subsidiaryFilter = 0;
        $this->docTypeFilter = 0;
        $this->mainCategoryFilter = 0;
        $this->page = 1;
    }
}
