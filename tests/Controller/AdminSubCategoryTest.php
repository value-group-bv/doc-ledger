<?php

namespace App\Tests\Controller;

use App\Entity\DocMainCategory;
use App\Entity\DocSubCategory;
use App\Entity\DocSubsidiary;
use App\Entity\DocType;
use App\Entity\DocumentEntry;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminSubCategoryTest extends WebTestCase
{
    private const EMAIL = 'subcat-test@example.com';

    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private DocType $typeA;
    private DocType $typeB;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $admin = (new User())->setEmail(self::EMAIL)->setRoles(['ROLE_ADMIN']);
        $this->typeA = (new DocType())->setCode('ZZA')->setDescription('Test type A');
        $this->typeB = (new DocType())->setCode('ZZB')->setDescription('Test type B');
        foreach ([$admin, $this->typeA, $this->typeB] as $entity) {
            $this->em->persist($entity);
        }
        $this->em->flush();
        $this->client->loginUser($admin);
    }

    protected function tearDown(): void
    {
        $this->em->createQuery('DELETE FROM App\Entity\DocumentEntry e WHERE e.title = :title')->setParameter('title', 'Subcat test')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\DocMainCategory m WHERE m.description = :d')->setParameter('d', 'Subcat test')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\DocSubsidiary s WHERE s.code = :code')->setParameter('code', 'ZS')->execute();
        // Removing the sub categories through the ORM clears their doc type links too
        $this->em->clear();
        foreach ($this->em->getRepository(DocSubCategory::class)->findBy(['code' => [950, 951]]) as $subCategory) {
            $this->em->remove($subCategory);
        }
        $this->em->flush();
        $this->em->createQuery('DELETE FROM App\Entity\DocType t WHERE t.code IN (:codes)')->setParameter('codes', ['ZZA', 'ZZB'])->execute();
        $this->em->createQuery('DELETE FROM App\Entity\User u WHERE u.email = :email')->setParameter('email', self::EMAIL)->execute();

        parent::tearDown();
    }

    public function testAddsSubCategoryForSeveralDocTypes(): void
    {
        $this->client->request('POST', '/admin/subcat/new', [
            'code' => '950', 'description' => 'Shared', 'docTypeIds' => [$this->typeA->getId(), $this->typeB->getId()],
        ]);
        self::assertResponseRedirects('/admin');

        $subCategory = $this->em->getRepository(DocSubCategory::class)->findOneBy(['code' => 950]);
        self::assertNotNull($subCategory);
        self::assertSame(['ZZA', 'ZZB'], $subCategory->getDocTypes()->map(fn(DocType $dt) => $dt->getCode())->getValues());

        $crawler = $this->client->request('GET', '/admin?editSubcat=' . $subCategory->getId());
        $checked = $crawler->filter("#subcat-{$subCategory->getId()} input[name='docTypeIds[]']:checked")->each(fn($input) => (int) $input->attr('value'));
        self::assertSame([$this->typeA->getId(), $this->typeB->getId()], $checked);
    }

    public function testRequiresADocType(): void
    {
        $this->client->request('POST', '/admin/subcat/new', ['code' => '950', 'description' => 'None']);
        $this->client->followRedirect();

        self::assertSelectorTextContains('body', 'Select at least one document type.');
        self::assertNull($this->em->getRepository(DocSubCategory::class)->findOneBy(['code' => 950]));
    }

    public function testRejectsCodeAlreadyUsedForOneOfTheDocTypes(): void
    {
        $this->em->persist((new DocSubCategory())->setCode(950)->setDescription('Existing')->addDocType($this->typeA));
        $this->em->flush();

        $this->client->request('POST', '/admin/subcat/new', [
            'code' => '950', 'description' => 'Clash', 'docTypeIds' => [$this->typeA->getId(), $this->typeB->getId()],
        ]);
        $this->client->followRedirect();
        self::assertSelectorTextContains('body', 'Sub category 950 already exists for ZZA');

        // Another doc type, or another scope, may reuse the code
        $this->client->request('POST', '/admin/subcat/new', ['code' => '950', 'description' => 'Other type', 'docTypeIds' => [$this->typeB->getId()]]);
        self::assertCount(2, $this->em->getRepository(DocSubCategory::class)->findBy(['code' => 950]));
    }

    public function testKeepsDocTypeThatDocumentsStillUse(): void
    {
        $taken = array_map(fn(DocMainCategory $mc) => $mc->getCode(), $this->em->getRepository(DocMainCategory::class)->findAll());
        $mainCategory = (new DocMainCategory())->setCode((string) array_values(array_diff(range(0, 9), $taken))[0])->setDescription('Subcat test');
        $subsidiary = (new DocSubsidiary())->setCode('ZS')->setDescription('Subcat test');
        $subCategory = (new DocSubCategory())->setCode(951)->setDescription('In use')->addDocType($this->typeA)->addDocType($this->typeB);
        $entry = (new DocumentEntry())->setSubsidiary($subsidiary)->setMainCategory($mainCategory)->setReferenceCode('000')
            ->setDocType($this->typeB)->setSubCategory($subCategory)->setDocNumber(1)->setTitle('Subcat test');
        foreach ([$mainCategory, $subsidiary, $subCategory, $entry] as $entity) {
            $this->em->persist($entity);
        }
        $this->em->flush();

        // The list shows the usage and offers no Delete for it
        $row = $this->client->request('GET', '/admin')->filter("#subcat-{$subCategory->getId()}");
        self::assertSame('ZZA ZZB', trim(preg_replace('/\s+/', ' ', $row->filter('td')->eq(2)->text())));
        self::assertSame('1', trim($row->filter('td')->eq(5)->text()));
        self::assertCount(0, $row->filter('form[action$="/delete"]'));

        $this->client->request('POST', "/admin/subcat/{$subCategory->getId()}/update", [
            'code' => '951', 'description' => 'In use', 'docTypeIds' => [$this->typeA->getId()],
        ]);
        $this->client->followRedirect();
        self::assertSelectorTextContains('body', 'Cannot remove doc type ZZB from sub category 951: it is still used by 1 ZZB document(s).');

        // Removing the unused one is fine
        $this->client->request('POST', "/admin/subcat/{$subCategory->getId()}/update", [
            'code' => '951', 'description' => 'In use', 'docTypeIds' => [$this->typeB->getId()],
        ]);
        $this->em->clear();
        $reloaded = $this->em->find(DocSubCategory::class, $subCategory->getId());
        self::assertSame(['ZZB'], $reloaded->getDocTypes()->map(fn(DocType $dt) => $dt->getCode())->getValues());
    }
}
