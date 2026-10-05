<?php

namespace App\Tests\Validator;

use App\Entity\DocMainCategory;
use App\Entity\DocSubCategory;
use App\Entity\DocSubsidiary;
use App\Entity\DocType;
use App\Entity\DocumentEntry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Every test runs inside a transaction that is rolled back afterwards, so the fixtures never
 * reach whatever database DATABASE_URL points to.
 */
class UniqueDocumentIdValidatorTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private ValidatorInterface $validator;

    private DocSubsidiary $subsidiary;
    private DocType $docType;
    private DocSubCategory $subCategory;
    private DocMainCategory $feasibility;
    private DocMainCategory $installation;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
        $this->em->getConnection()->beginTransaction();

        // Main category codes are single, unique digits: use two that aren't taken yet
        $taken = array_map(fn(DocMainCategory $mc) => $mc->getCode(), $this->em->getRepository(DocMainCategory::class)->findAll());
        [$feasibilityCode, $installationCode] = array_values(array_diff(range(0, 9), $taken));

        $this->subsidiary = (new DocSubsidiary())->setCode('ZT')->setDescription('Test subsidiary');
        $this->docType = (new DocType())->setCode('TST')->setDescription('Test type');
        $this->feasibility = (new DocMainCategory())->setCode((string) $feasibilityCode)->setDescription('Feasibility')->setReferenceCode('AAA');
        $this->installation = (new DocMainCategory())->setCode((string) $installationCode)->setDescription('Installation');
        $this->subCategory = (new DocSubCategory())->setCode(100)->setDescription('Structural')->addDocType($this->docType);

        foreach ([$this->subsidiary, $this->docType, $this->feasibility, $this->installation, $this->subCategory] as $entity) {
            $this->em->persist($entity);
        }
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        $this->em->getConnection()->rollBack();
        parent::tearDown();
    }

    public function testEntryWithoutAlternatesAndNoClashIsValid(): void
    {
        $this->save($this->entry($this->feasibility, 1));

        $this->assertViolations([], $this->entry($this->feasibility, 2));
    }

    public function testSameIdUnderDefaultCategoryClashes(): void
    {
        $this->save($this->entry($this->feasibility, 1));

        $this->assertViolations([$this->id($this->feasibility, 'AAA', 1)], $this->entry($this->feasibility, 1));
    }

    public function testNewEntryClashesWithExistingEntrysAlternate(): void
    {
        $this->save($this->entry($this->feasibility, 1, [$this->installation]));

        $this->assertViolations([$this->id($this->installation, '000', 1)], $this->entry($this->installation, 1));
    }

    public function testTickingAlternateClashesWithExistingEntry(): void
    {
        $this->save($this->entry($this->installation, 1));

        $this->assertViolations([$this->id($this->installation, '000', 1)], $this->entry($this->feasibility, 1, [$this->installation]));
    }

    public function testSameNumberUnderUnrelatedCategoriesIsValid(): void
    {
        $this->save($this->entry($this->feasibility, 1));

        $this->assertViolations([], $this->entry($this->installation, 1));
    }

    public function testDifferentSubCategoriesWithTheSameCodeClash(): void
    {
        $this->save($this->entry($this->feasibility, 1));

        $sameCode = (new DocSubCategory())->setCode(100)->setDescription('Structural (feasibility)')
            ->addDocType($this->docType)->setMainCategory($this->feasibility);
        $this->em->persist($sameCode);
        $this->em->flush();

        $this->assertViolations([$this->id($this->feasibility, 'AAA', 1)], $this->entry($this->feasibility, 1)->setSubCategory($sameCode));
    }

    public function testEditingAnEntryDoesNotClashWithItself(): void
    {
        $entry = $this->entry($this->feasibility, 1);
        $this->save($entry);

        $entry->addAlternateMainCategory($this->installation);

        $this->assertViolations([], $entry);
    }

    public function testAlternatesAreRejectedForScopedSubCategory(): void
    {
        $this->subCategory->setMainCategory($this->feasibility);
        $this->em->flush();

        $violations = $this->validator->validate($this->entry($this->feasibility, 1, [$this->installation]));

        $this->assertCount(1, $violations);
        $this->assertSame('alternateMainCategories', $violations[0]->getPropertyPath());
    }

    public function testDefaultCategoryIsNeverAlsoAnAlternate(): void
    {
        $entry = $this->entry($this->feasibility, 1, [$this->installation]);
        $entry->addAlternateMainCategory($this->feasibility);
        $this->assertSame([$this->feasibility, $this->installation], $entry->getAllowedMainCategories());

        $entry->setMainCategory($this->installation);
        $this->assertSame([$this->installation], $entry->getAllowedMainCategories());
    }

    /** @param DocMainCategory[] $alternates */
    private function entry(DocMainCategory $mainCategory, int $docNumber, array $alternates = []): DocumentEntry
    {
        $entry = (new DocumentEntry())
            ->setSubsidiary($this->subsidiary)
            ->setMainCategory($mainCategory)
            ->setReferenceCode($mainCategory->getReferenceCode())
            ->setDocType($this->docType)
            ->setSubCategory($this->subCategory)
            ->setDocNumber($docNumber)
            ->setTitle('Test document');

        foreach ($alternates as $alternate) {
            $entry->addAlternateMainCategory($alternate);
        }

        return $entry;
    }

    private function save(DocumentEntry $entry): void
    {
        $this->em->persist($entry);
        $this->em->flush();
    }

    private function id(DocMainCategory $mainCategory, string $referenceCode, int $docNumber): string
    {
        return \sprintf('ZT%s-%s-TST-100-%03d-00', $mainCategory->getCode(), $referenceCode, $docNumber);
    }

    /** @param string[] $expectedIds */
    private function assertViolations(array $expectedIds, DocumentEntry $entry): void
    {
        $messages = array_map(fn($v) => $v->getMessage(), iterator_to_array($this->validator->validate($entry)));
        $expected = array_map(fn($id) => "Document ID $id already exists in the ledger.", $expectedIds);

        $this->assertSame($expected, $messages);
    }
}
