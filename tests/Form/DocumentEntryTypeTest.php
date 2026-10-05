<?php

namespace App\Tests\Form;

use App\Entity\DocMainCategory;
use App\Entity\DocSubCategory;
use App\Entity\DocSubsidiary;
use App\Entity\DocType;
use App\Entity\DocumentEntry;
use App\Form\DocumentEntryType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * Runs inside a transaction that is rolled back afterwards, so the fixtures never reach the database.
 */
class DocumentEntryTypeTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->em->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->em->getConnection()->rollBack();
        parent::tearDown();
    }

    public function testSubCategoriesAreLimitedToTheEntrysSubsidiary(): void
    {
        $own = (new DocSubsidiary())->setCode('ZT')->setDescription('Test subsidiary');
        $other = (new DocSubsidiary())->setCode('ZU')->setDescription('Other subsidiary');
        $docType = (new DocType())->setCode('TST')->setDescription('Test type');
        $taken = array_map(fn(DocMainCategory $mc) => $mc->getCode(), $this->em->getRepository(DocMainCategory::class)->findAll());
        $mainCategory = (new DocMainCategory())->setCode((string) array_values(array_diff(range(0, 9), $taken))[0])->setDescription('Test');

        $global = (new DocSubCategory())->setCode(100)->setDescription('Global')->setDocType($docType);
        $ownOnly = (new DocSubCategory())->setCode(200)->setDescription('Own')->setDocType($docType)->setSubsidiary($own);
        $otherOnly = (new DocSubCategory())->setCode(300)->setDescription('Other')->setDocType($docType)->setSubsidiary($other);
        $current = (new DocSubCategory())->setCode(400)->setDescription('Current')->setDocType($docType)->setSubsidiary($other);

        $entry = (new DocumentEntry())->setSubsidiary($own)->setMainCategory($mainCategory)->setReferenceCode('000')
            ->setDocType($docType)->setSubCategory($current)->setDocNumber(1)->setTitle('Test document');

        foreach ([$own, $other, $docType, $mainCategory, $global, $ownOnly, $otherOnly, $current, $entry] as $entity) {
            $this->em->persist($entity);
        }
        $this->em->flush();

        $form = static::getContainer()->get(FormFactoryInterface::class)->create(DocumentEntryType::class, $entry);
        $offered = array_map(fn($choice) => $choice->data, $form->get('subCategory')->createView()->vars['choices']);

        $this->assertContains($global, $offered);
        $this->assertContains($ownOnly, $offered);
        $this->assertContains($current, $offered, 'The current sub category stays selectable');
        $this->assertNotContains($otherOnly, $offered);
    }
}
