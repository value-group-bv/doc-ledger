<?php

namespace App\Tests\Form;

use App\Entity\DocMainCategory;
use App\Entity\DocSubCategory;
use App\Entity\DocSubsidiary;
use App\Entity\DocType;
use App\Entity\DocumentEntry;
use App\Entity\User;
use App\Form\DocumentEntryType;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

/**
 * Runs inside a transaction that is rolled back afterwards, so the fixtures never reach the database.
 */
class DocumentEntryTypeTest extends WebTestCase
{
    use InteractsWithLiveComponents;

    private EntityManagerInterface $em;
    private DocSubsidiary $own;
    private DocSubsidiary $other;
    private DocSubCategory $global;
    private DocSubCategory $ownOnly;
    private DocSubCategory $otherOnly;
    private DocSubCategory $saved;
    private DocType $otherDocType;
    private DocSubCategory $otherDocTypeOnly;
    private DocumentEntry $entry;

    protected function setUp(): void
    {
        static::createClient()->disableReboot();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->em->getConnection()->beginTransaction();

        $this->own = (new DocSubsidiary())->setCode('ZT')->setDescription('Test subsidiary');
        $this->other = (new DocSubsidiary())->setCode('ZU')->setDescription('Other subsidiary');
        $docType = (new DocType())->setCode('TST')->setDescription('Test type');
        $this->otherDocType = (new DocType())->setCode('TSU')->setDescription('Other test type');
        $taken = array_map(fn(DocMainCategory $mc) => $mc->getCode(), $this->em->getRepository(DocMainCategory::class)->findAll());
        $mainCategory = (new DocMainCategory())->setCode((string) array_values(array_diff(range(0, 9), $taken))[0])->setDescription('Test');

        $this->global = (new DocSubCategory())->setCode(100)->setDescription('Global')->addDocType($docType);
        $this->ownOnly = (new DocSubCategory())->setCode(200)->setDescription('Own')->addDocType($docType)->setSubsidiary($this->own);
        $this->otherOnly = (new DocSubCategory())->setCode(300)->setDescription('Other')->addDocType($docType)->setSubsidiary($this->other);
        // Saved before sub categories were limited per subsidiary: belongs to the other one
        $this->saved = (new DocSubCategory())->setCode(400)->setDescription('Saved')->addDocType($docType)->setSubsidiary($this->other);
        $this->otherDocTypeOnly = (new DocSubCategory())->setCode(500)->setDescription('Other doc type')->addDocType($this->otherDocType);

        $this->entry = (new DocumentEntry())->setSubsidiary($this->own)->setMainCategory($mainCategory)->setReferenceCode('000')
            ->setDocType($docType)->setSubCategory($this->saved)->setDocNumber(1)->setTitle('Test document');

        $admin = (new User())->setEmail('entry-form-test@example.com')->setRoles(['ROLE_ADMIN']);

        foreach ([$this->own, $this->other, $docType, $mainCategory, $this->global, $this->ownOnly, $this->otherOnly, $this->saved, $this->otherDocType, $this->otherDocTypeOnly, $this->entry, $admin] as $entity) {
            $this->em->persist($entity);
        }
        $this->em->flush();
        static::getClient()->loginUser($admin);
    }

    protected function tearDown(): void
    {
        $this->em->getConnection()->rollBack();
        parent::tearDown();
    }

    public function testSubCategoriesAreLimitedToTheEntrysSubsidiary(): void
    {
        $offered = $this->offeredSubCategories($this->form());

        $this->assertContains($this->global, $offered);
        $this->assertContains($this->ownOnly, $offered);
        $this->assertContains($this->saved, $offered, 'The saved sub category stays selectable');
        $this->assertNotContains($this->otherOnly, $offered);
        $this->assertNotContains($this->otherDocTypeOnly, $offered);
    }

    public function testSubCategoriesFollowTheSubmittedSubsidiary(): void
    {
        $form = $this->form();
        $form->submit(['subsidiary' => (string) $this->other->getId(), 'docType' => (string) $this->entry->getDocType()->getId()], false);

        $offered = $this->offeredSubCategories($form);

        $this->assertContains($this->global, $offered);
        $this->assertContains($this->otherOnly, $offered);
        $this->assertNotContains($this->ownOnly, $offered);
    }

    public function testSubCategoriesFollowTheSubmittedDocType(): void
    {
        $form = $this->form();
        $form->submit(['subsidiary' => (string) $this->own->getId(), 'docType' => (string) $this->otherDocType->getId()], false);

        $this->assertSame([$this->otherDocTypeOnly], array_values($this->offeredSubCategories($form)));
    }

    public function testSubCategoryOfSeveralDocTypesIsOfferedForEach(): void
    {
        $this->global->addDocType($this->otherDocType);
        $this->em->flush();

        $this->assertContains($this->global, $this->offeredSubCategories($this->form()));

        $form = $this->form();
        $form->submit(['subsidiary' => (string) $this->own->getId(), 'docType' => (string) $this->otherDocType->getId()], false);
        $this->assertSame([$this->global, $this->otherDocTypeOnly], array_values($this->offeredSubCategories($form)));
    }

    public function testSavedSubCategoryOfAnotherDocTypeIsNotOffered(): void
    {
        $this->entry->setDocType($this->otherDocType);
        $this->em->flush();

        $this->assertNotContains($this->saved, $this->offeredSubCategories($this->form()));
    }

    public function testLiveFormUpdatesSubCategoriesWhenTheDocTypeChanges(): void
    {
        $component = $this->createLiveComponent('DocumentEntryForm', ['entry' => $this->entry], static::getClient());

        $html = (string) $component->set('document_entry.docType', (string) $this->otherDocType->getId())->render();

        $this->assertStringContainsString('500 - Other doc type', $html);
        $this->assertStringNotContainsString('200 - Own', $html);
    }

    public function testLiveFormUpdatesSubCategoriesWhenTheSubsidiaryChanges(): void
    {
        $component = $this->createLiveComponent('DocumentEntryForm', ['entry' => $this->entry], static::getClient());
        $this->assertStringContainsString('200 - Own', (string) $component->render());

        $html = (string) $component->set('document_entry.subsidiary', (string) $this->other->getId())->render();

        $this->assertStringContainsString('300 - Other', $html);
        $this->assertStringNotContainsString('200 - Own', $html);
    }

    public function testEditPageSavesASubCategoryOfTheNewSubsidiary(): void
    {
        $client = static::getClient();
        $crawler = $client->request('GET', "/ledger/{$this->entry->getId()}/edit");
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('[data-controller~="live"] form');

        // The browser would have the re-rendered options; the test client only knows the initial ones
        $client->submit($crawler->selectButton('Save changes')->form()->disableValidation(), [
            'document_entry[subsidiary]' => (string) $this->other->getId(),
            'document_entry[subCategory]' => (string) $this->otherOnly->getId(),
        ]);

        $this->assertResponseRedirects('/');
        $saved = $this->em->find(DocumentEntry::class, $this->entry->getId());
        $this->assertSame($this->otherOnly->getId(), $saved->getSubCategory()->getId());
    }

    #[DataProvider('requiredFields')]
    public function testLiveFormSurvivesAnEmptiedRequiredField(string $field): void
    {
        $component = $this->createLiveComponent('DocumentEntryForm', ['entry' => $this->entry], static::getClient());

        $html = (string) $component->set("document_entry.$field", '')->render();

        $this->assertStringContainsString('Save changes', $html);
    }

    /** @return array<string, array{string}> */
    public static function requiredFields(): array
    {
        $fields = ['subsidiary', 'mainCategory', 'docType', 'subCategory', 'docNumber', 'title'];

        return array_combine($fields, array_map(fn($f) => [$f], $fields));
    }

    public function testSavingWithoutASubCategoryShowsAnError(): void
    {
        $client = static::getClient();
        $crawler = $client->request('GET', "/ledger/{$this->entry->getId()}/edit");

        $client->submit($crawler->selectButton('Save changes')->form(), ['document_entry[subCategory]' => '']);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('form', 'This value should not be blank.');
        $saved = $this->em->find(DocumentEntry::class, $this->entry->getId());
        $this->assertSame($this->saved->getId(), $saved->getSubCategory()->getId());
    }

    private function form(): FormInterface
    {
        return static::getContainer()->get(FormFactoryInterface::class)->create(DocumentEntryType::class, $this->entry, ['csrf_protection' => false]);
    }

    /** @return DocSubCategory[] */
    private function offeredSubCategories(FormInterface $form): array
    {
        return array_map(fn($choice) => $choice->data, $form->get('subCategory')->createView()->vars['choices']);
    }
}
