<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminUserTest extends WebTestCase
{
    private const EMAILS = ['user-name-admin@example.com', 'user-name-other@example.com'];

    private KernelBrowser $client;
    private EntityManagerInterface $em;
    private User $other;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $admin = (new User())->setEmail(self::EMAILS[0])->setRoles(['ROLE_ADMIN'])->setDisplayName('Admin');
        $this->other = (new User())->setEmail(self::EMAILS[1])->setRoles(['ROLE_USER']);
        $this->em->persist($admin);
        $this->em->persist($this->other);
        $this->em->flush();
        $this->client->loginUser($admin);
    }

    protected function tearDown(): void
    {
        static::getContainer()->get(EntityManagerInterface::class)->createQuery('DELETE FROM App\Entity\User u WHERE u.email IN (:emails)')
            ->setParameter('emails', self::EMAILS)
            ->execute();

        parent::tearDown();
    }

    public function testSetsAndClearsDisplayName(): void
    {
        $id = (string) $this->other->getId();

        $this->client->request('POST', "/admin/user/$id/update", ['displayName' => '  Nick  ']);
        self::assertResponseRedirects("/admin#user-$id");
        self::assertSame('Nick', $this->fetchOther()->getDisplayName());

        $this->client->request('GET', '/admin');
        self::assertSelectorTextContains("#user-$id", 'Nick');
        // Header shows the logged-in admin's display name instead of the email
        self::assertSelectorTextSame('button[aria-haspopup="true"] span', 'Admin');

        $this->client->request('POST', "/admin/user/$id/update", ['displayName' => '']);
        self::assertNull($this->fetchOther()->getDisplayName());
    }

    /** The client reboots the kernel between requests, so fetch through the current entity manager */
    private function fetchOther(): User
    {
        return static::getContainer()->get(EntityManagerInterface::class)
            ->getRepository(User::class)->findOneBy(['email' => self::EMAILS[1]]);
    }

    public function testEditRowRendersInput(): void
    {
        $id = (string) $this->other->getId();
        $this->client->request('GET', '/admin', ['editUser' => $id]);

        self::assertResponseIsSuccessful();
        self::assertSelectorExists("#user-$id input[name=displayName]");
    }
}
