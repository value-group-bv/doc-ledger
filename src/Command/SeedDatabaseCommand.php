<?php

namespace App\Command;

use App\Entity\DocMainCategory;
use App\Entity\DocSubsidiary;
use App\Entity\DocType;
use App\Entity\DocSubCategory;
use App\Entity\DocumentEntry;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:seed-database',
    description: 'Seeds the database with test data',
)]
class SeedDatabaseCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new \Symfony\Component\Console\Style\SymfonyStyle($input, $output);

        try {
            $io->info('Clearing database...');
            $this->clearDatabase();

            $io->info('Creating superadmin user...');
            $this->createSuperAdminUser();

            $io->info('Creating subsidiaries...');
            $subsidiaries = $this->createSubsidiaries();

            $io->info('Creating main categories...');
            $mainCategories = $this->createMainCategories();

            $io->info('Creating document types...');
            $docTypes = $this->createDocTypes();

            $io->info('Creating sub categories...');
            $subCategories = $this->createSubCategories($docTypes);

            $io->info('Creating document entries...');
            $this->createDocumentEntries($subsidiaries, $mainCategories, $docTypes, $subCategories);

            $this->entityManager->flush();

            $io->success('Database seeded successfully!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error seeding database: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function clearDatabase(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DELETE FROM document_entry');
        $connection->executeStatement('DELETE FROM doc_sub_category');
        $connection->executeStatement('DELETE FROM doc_type');
        $connection->executeStatement('DELETE FROM doc_main_category');
        $connection->executeStatement('DELETE FROM doc_subsidiary');
        $connection->executeStatement('DELETE FROM "user"');
    }

    private function createSuperAdminUser(): void
    {
        $user = new User();
        $user->setEmail('rvollenberg@valuemaritime.com');
        $user->setDisplayName('Russ Vollenberg');
        $user->setRoles(['ROLE_ADMIN', 'ROLE_SUPERADMIN']);
        $user->setPassword(null);

        $this->entityManager->persist($user);
    }

    private function createSubsidiaries(): array
    {
        $subsidiaries = [
            ['code' => 'VM', 'description' => 'Value Maritime Main', 'sortOrder' => 1],
            ['code' => 'VY', 'description' => 'Value Yachts', 'sortOrder' => 2],
            ['code' => 'VS', 'description' => 'Value Services', 'sortOrder' => 3],
        ];

        $result = [];
        foreach ($subsidiaries as $data) {
            $subsidiary = new DocSubsidiary();
            $subsidiary->setCode($data['code']);
            $subsidiary->setDescription($data['description']);
            $subsidiary->setSortOrder($data['sortOrder']);
            $this->entityManager->persist($subsidiary);
            $result[$data['code']] = $subsidiary;
        }

        return $result;
    }

    private function createMainCategories(): array
    {
        $categories = [
            ['code' => '0', 'description' => 'Design & Engineering', 'referenceCode' => '000'],
            ['code' => '1', 'description' => 'Project Management', 'referenceCode' => '000'],
            ['code' => '2', 'description' => 'Quality Assurance', 'referenceCode' => 'AAA'],
        ];

        $result = [];
        foreach ($categories as $data) {
            $category = new DocMainCategory();
            $category->setCode($data['code']);
            $category->setDescription($data['description']);
            $category->setReferenceCode($data['referenceCode']);
            $this->entityManager->persist($category);
            $result[$data['code']] = $category;
        }

        return $result;
    }

    private function createDocTypes(): array
    {
        $types = [
            ['code' => 'DWG', 'description' => 'Drawing'],
            ['code' => 'RPT', 'description' => 'Report'],
            ['code' => 'SPC', 'description' => 'Specification'],
        ];

        $result = [];
        foreach ($types as $data) {
            $type = new DocType();
            $type->setCode($data['code']);
            $type->setDescription($data['description']);
            $this->entityManager->persist($type);
            $result[$data['code']] = $type;
        }

        return $result;
    }

    private function createSubCategories(array $docTypes): array
    {
        $subCategories = [
            ['code' => 100, 'description' => 'Hull Design', 'docType' => 'DWG'],
            ['code' => 200, 'description' => 'Machinery', 'docType' => 'DWG'],
            ['code' => 300, 'description' => 'Electrical', 'docType' => 'SPC'],
        ];

        $result = [];
        foreach ($subCategories as $data) {
            $subCategory = new DocSubCategory();
            $subCategory->setCode($data['code']);
            $subCategory->setDescription($data['description']);
            $subCategory->setDocType($docTypes[$data['docType']]);
            $this->entityManager->persist($subCategory);
            $result[$data['code']] = $subCategory;
        }

        return $result;
    }

    private function createDocumentEntries(array $subsidiaries, array $mainCategories, array $docTypes, array $subCategories): void
    {
        $documents = [
            [
                'subsidiary' => 'VM',
                'mainCategory' => '0',
                'referenceCode' => '001',
                'docType' => 'DWG',
                'subCategory' => '100',
                'docNumber' => 1,
                'title' => 'Hull Outline Drawing',
                'comments' => 'General arrangement of vessel hull',
            ],
            [
                'subsidiary' => 'VM',
                'mainCategory' => '0',
                'referenceCode' => '001',
                'docType' => 'SPC',
                'subCategory' => '200',
                'docNumber' => 2,
                'title' => 'Main Engine Specifications',
                'comments' => 'Technical specifications for main propulsion engine',
            ],
            [
                'subsidiary' => 'VY',
                'mainCategory' => '1',
                'referenceCode' => '002',
                'docType' => 'RPT',
                'subCategory' => '300',
                'docNumber' => 3,
                'title' => 'Project Status Report',
                'comments' => 'Monthly progress update',
            ],
            [
                'subsidiary' => 'VY',
                'mainCategory' => '0',
                'referenceCode' => '002',
                'docType' => 'DWG',
                'subCategory' => '100',
                'docNumber' => 4,
                'title' => 'Accommodation Layout',
                'comments' => 'Crew quarters and passenger spaces',
            ],
            [
                'subsidiary' => 'VS',
                'mainCategory' => '2',
                'referenceCode' => 'AAA',
                'docType' => 'RPT',
                'subCategory' => '200',
                'docNumber' => 5,
                'title' => 'Quality Control Report',
                'comments' => 'Final inspection results',
            ],
        ];

        foreach ($documents as $data) {
            $entry = new DocumentEntry();
            $entry->setSubsidiary($subsidiaries[$data['subsidiary']]);
            $entry->setMainCategory($mainCategories[$data['mainCategory']]);
            $entry->setReferenceCode($data['referenceCode']);
            $entry->setDocType($docTypes[$data['docType']]);
            $entry->setSubCategory($subCategories[$data['subCategory']]);
            $entry->setDocNumber($data['docNumber']);
            $entry->setTitle($data['title']);
            $entry->setComments($data['comments']);
            $entry->setRevision('00');

            $this->entityManager->persist($entry);
        }
    }
}
