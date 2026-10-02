<?php

namespace App\Tests\Controller;

use App\Entity\DocSubsidiary;
use App\Entity\FeasibilityCode;
use App\Entity\ReservedFeasibilityCode;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminReservedCodeTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $admin = (new User())->setEmail('reserved-code-test@example.com')->setRoles(['ROLE_ADMIN']);
        $this->em->persist($admin);
        $this->em->flush();
        $this->client->loginUser($admin);
    }

    protected function tearDown(): void
    {
        $this->em->createQuery('DELETE FROM App\Entity\ReservedFeasibilityCode r WHERE r.code IN (:codes)')
            ->setParameter('codes', ['QQQ', 'QQR'])
            ->execute();
        $this->em->createQuery('DELETE FROM App\Entity\FeasibilityCode f WHERE f.code = :code')
            ->setParameter('code', 'QQR')
            ->execute();
        $this->em->createQuery('DELETE FROM App\Entity\DocSubsidiary s WHERE s.code = :code')
            ->setParameter('code', 'QX')
            ->execute();
        $this->em->createQuery('DELETE FROM App\Entity\User u WHERE u.email = :email')
            ->setParameter('email', 'reserved-code-test@example.com')
            ->execute();

        parent::tearDown();
    }

    public function testAddsAndListsReservedCode(): void
    {
        $this->client->request('POST', '/admin/reserved-code/new', ['code' => 'qqq', 'description' => 'Test Product']);
        self::assertResponseRedirects('/admin');

        $reserved = $this->em->getRepository(ReservedFeasibilityCode::class)->findOneBy(['code' => 'QQQ']);
        self::assertNotNull($reserved);

        $this->client->followRedirect();
        self::assertSelectorTextContains('body', 'Reserved Codes');
        self::assertSelectorTextContains('body', 'Test Product');
    }

    public function testRejectsCodeAlreadyUsedByFeasibilityProject(): void
    {
        $subsidiary = (new DocSubsidiary())->setCode('QX')->setDescription('Test subsidiary');
        $this->em->persist($subsidiary);
        $fc = (new FeasibilityCode())->setCode('QQR')->setTitle('Existing Project')->setRequestor('x')->setSubsidiary($subsidiary);
        $this->em->persist($fc);
        $this->em->flush();

        $this->client->request('POST', '/admin/reserved-code/new', ['code' => 'QQR', 'description' => 'Test Product']);
        $this->client->followRedirect();

        self::assertSelectorTextContains('body', "already in use by feasibility project 'Existing Project'");
        self::assertNull($this->em->getRepository(ReservedFeasibilityCode::class)->findOneBy(['code' => 'QQR']));
    }
}
