<?php

namespace App\Tests\Controller\Api;

use App\Entity\DocSubsidiary;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FeasibilityCodeControllerTest extends WebTestCase
{
    protected function tearDown(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM App\Entity\FeasibilityCode f')->execute();
        $em->createQuery('DELETE FROM App\Entity\DocSubsidiary s WHERE s.code IN (:codes)')
            ->setParameter('codes', ['VG', 'VH'])
            ->execute();

        parent::tearDown();
    }

    private function createSubsidiaryWithKey(EntityManagerInterface $em, string $code, string $plaintextKey): DocSubsidiary
    {
        $subsidiary = (new DocSubsidiary())
            ->setCode($code)
            ->setDescription('Test subsidiary')
            ->setApiKey($plaintextKey);

        $em->persist($subsidiary);
        $em->flush();

        return $subsidiary;
    }

    public function testAllocatesFirstCodeForValidApiKey(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $this->createSubsidiaryWithKey($em, 'VG', 'test-secret-key');

        $client->request(
            'POST',
            '/api/feasibility-codes',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer test-secret-key',
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode(['title' => 'New Plant Feasibility', 'requestor' => 'jane@example.com']),
        );

        self::assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertMatchesRegularExpression('/^[A-Z]{3}$/', $data['code']);
        self::assertSame('New Plant Feasibility', $data['title']);
        self::assertSame('jane@example.com', $data['requestor']);
        self::assertSame('VG', $data['subsidiary']);
    }

    public function testRejectsMissingApiKey(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/feasibility-codes',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['title' => 'X', 'requestor' => 'Y']),
        );

        self::assertResponseStatusCodeSame(401);
    }

    public function testRejectsWrongApiKey(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $this->createSubsidiaryWithKey($em, 'VH', 'correct-key');

        $client->request(
            'POST',
            '/api/feasibility-codes',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer wrong-key',
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode(['title' => 'X', 'requestor' => 'Y']),
        );

        self::assertResponseStatusCodeSame(401);
    }
}
