<?php

namespace App\Command;

use App\Entity\DocMainCategory;
use App\Entity\DocPredefinedNumber;
use App\Entity\DocSubCategory;
use App\Entity\DocSubsidiary;
use App\Entity\DocType;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'docledger:import-excel',
    description: 'Seed configuration tables from the NUMBERINGSCHEME_INPUT.xlsx file',
)]
class ImportExcelCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Path to NUMBERINGSCHEME_INPUT.xlsx')
            ->addOption('clear', null, InputOption::VALUE_NONE, 'Clear existing config data before importing');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filePath = $input->getArgument('file');

        if (!file_exists($filePath)) {
            $io->error("File not found: $filePath");
            return Command::FAILURE;
        }

        if ($input->getOption('clear')) {
            $this->clearConfigData($io);
        }

        $io->info("Reading: $filePath");

        try {
            $spreadsheet = IOFactory::load($filePath);
        } catch (\Exception $e) {
            $io->error("Could not open Excel file: {$e->getMessage()}");
            return Command::FAILURE;
        }

        $this->importSubsidiaries($spreadsheet, $io);
        $this->importMainCategories($spreadsheet, $io);
        $this->importDocTypes($spreadsheet, $io);
        $this->importSubCategories($spreadsheet, $io);
        $this->importPredefinedNumbers($spreadsheet, $io);

        $this->em->flush();

        $io->success('Import complete.');
        return Command::SUCCESS;
    }

    private function importSubsidiaries(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, SymfonyStyle $io): void
    {
        $sheet = $spreadsheet->getSheetByName('PREFIX');
        if (!$sheet) {
            $io->warning('Sheet "PREFIX" not found — skipping subsidiaries.');
            return;
        }

        $count = 0;
        foreach ($sheet->getRowIterator(2) as $row) {
            $cells = $row->getCellIterator();
            $cells->setIterateOnlyExistingCells(false);
            $data = $this->rowToArray($cells);

            $description = trim((string) ($data[0] ?? ''));
            $code        = trim((string) ($data[1] ?? ''));

            if (!$code || !$description) continue;

            $existing = $this->em->getRepository(DocSubsidiary::class)->findOneBy(['code' => $code]);
            if ($existing) continue;

            $entity = (new DocSubsidiary())->setCode($code)->setDescription($description)->setSortOrder($count);
            $this->em->persist($entity);
            $count++;
        }

        $io->writeln("  Subsidiaries: +$count");
    }

    private function importMainCategories(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, SymfonyStyle $io): void
    {
        $sheet = $spreadsheet->getSheetByName('MAINCAT');
        if (!$sheet) {
            $io->warning('Sheet "MAINCAT" not found — skipping main categories.');
            return;
        }

        $count = 0;
        foreach ($sheet->getRowIterator(2) as $row) {
            $cells = $row->getCellIterator();
            $cells->setIterateOnlyExistingCells(false);
            $data = $this->rowToArray($cells);

            $description = trim((string) ($data[0] ?? ''));
            $code        = trim((string) ($data[1] ?? ''));

            if ($code === '' || !$description) continue;

            $existing = $this->em->getRepository(DocMainCategory::class)->findOneBy(['code' => $code]);
            if ($existing) continue;

            $entity = (new DocMainCategory())->setCode($code)->setDescription($description);
            $this->em->persist($entity);
            $count++;
        }

        $io->writeln("  Main categories: +$count");
    }

    private function importDocTypes(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, SymfonyStyle $io): void
    {
        $sheet = $spreadsheet->getSheetByName('DOCTYPE');
        if (!$sheet) {
            $io->warning('Sheet "DOCTYPE" not found — skipping doc types.');
            return;
        }

        $count = 0;
        foreach ($sheet->getRowIterator(2) as $row) {
            $cells = $row->getCellIterator();
            $cells->setIterateOnlyExistingCells(false);
            $data = $this->rowToArray($cells);

            $description = trim((string) ($data[0] ?? ''));
            $code        = trim((string) ($data[1] ?? ''));

            if (!$code || !$description) continue;

            $existing = $this->em->getRepository(DocType::class)->findOneBy(['code' => $code]);
            if ($existing) continue;

            $entity = (new DocType())->setCode($code)->setDescription($description)->setSortOrder($count);
            $this->em->persist($entity);
            $count++;
        }

        $this->em->flush();
        $io->writeln("  Doc types: +$count");
    }

    private function importSubCategories(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, SymfonyStyle $io): void
    {
        $sheet = $spreadsheet->getSheetByName('SUBCAT');
        if (!$sheet) {
            $io->warning('Sheet "SUBCAT" not found — skipping sub categories.');
            return;
        }

        $count = 0;
        foreach ($sheet->getRowIterator(2) as $row) {
            $cells = $row->getCellIterator();
            $cells->setIterateOnlyExistingCells(false);
            $data = $this->rowToArray($cells);

            // Expected columns: DESCRIPTION, CODE, DOCTYPE
            $description = trim((string) ($data[0] ?? ''));
            $code        = (int) ($data[1] ?? 0);
            $docTypeCode = trim((string) ($data[2] ?? ''));

            if (!$docTypeCode || !$description) continue;

            $docType = $this->em->getRepository(DocType::class)->findOneBy(['code' => $docTypeCode]);
            if (!$docType) continue;

            $existing = $this->em->getRepository(DocSubCategory::class)->findOneBy(['code' => $code, 'docType' => $docType]);
            if ($existing) continue;

            $entity = (new DocSubCategory())->setCode($code)->setDescription($description)->setDocType($docType);
            $this->em->persist($entity);
            $count++;
        }

        $this->em->flush();
        $io->writeln("  Sub categories: +$count");
    }

    private function importPredefinedNumbers(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, SymfonyStyle $io): void
    {
        $sheet = $spreadsheet->getSheetByName('DOCNUMBERS');
        if (!$sheet) {
            $io->warning('Sheet "DOCNUMBERS" not found — skipping predefined numbers.');
            return;
        }

        $count = 0;
        foreach ($sheet->getRowIterator(2) as $row) {
            $cells = $row->getCellIterator();
            $cells->setIterateOnlyExistingCells(false);
            $data = $this->rowToArray($cells);

            // Expected columns: SUBCAT, DESCRIPTION, CODE, DOCTYPE
            $subCatCode  = (int) ($data[0] ?? 0);
            $description = trim((string) ($data[1] ?? ''));
            $code        = (int) ($data[2] ?? 0);
            $docTypeCode = trim((string) ($data[3] ?? ''));

            if (!$docTypeCode || !$description) continue;

            $docType = $this->em->getRepository(DocType::class)->findOneBy(['code' => $docTypeCode]);
            if (!$docType) continue;

            $subCat = $this->em->getRepository(DocSubCategory::class)->findOneBy(['code' => $subCatCode, 'docType' => $docType]);
            if (!$subCat) continue;

            $existing = $this->em->getRepository(DocPredefinedNumber::class)->findOneBy(['code' => $code, 'subCategory' => $subCat]);
            if ($existing) continue;

            $entity = (new DocPredefinedNumber())->setCode($code)->setDescription($description)->setSubCategory($subCat);
            $this->em->persist($entity);
            $count++;
        }

        $io->writeln("  Predefined numbers: +$count");
    }

    private function clearConfigData(SymfonyStyle $io): void
    {
        $io->warning('Clearing existing config data…');
        $connection = $this->em->getConnection();
        $connection->executeStatement('DELETE FROM doc_predefined_number');
        $connection->executeStatement('DELETE FROM doc_sub_category');
        $connection->executeStatement('DELETE FROM doc_type');
        $connection->executeStatement('DELETE FROM doc_main_category');
        $connection->executeStatement('DELETE FROM doc_subsidiary');
        $this->em->clear();
    }

    private function rowToArray(\PhpOffice\PhpSpreadsheet\Cell\CellIterator $cells): array
    {
        $data = [];
        foreach ($cells as $cell) {
            $data[] = $cell->getValue();
        }
        return $data;
    }
}
