<?php

declare(strict_types=1);

/*
 * This file is part of the proprietary project.
 *
 * This file and its contents are confidential and protected by copyright law.
 * Unauthorized copying, distribution, or disclosure of this content
 * is strictly prohibited without prior written consent from the author or
 * copyright owner.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Infrastructure\CLI;

use App\Domain\Entity\Exercise;
use App\Domain\Entity\MuscleCategory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed:exercises',
    description: 'Seeds the database with muscle categories and exercises from JSON file'
)]
final class SeedExercisesCommand extends Command
{
    private const JSON_FILE_PATH = __DIR__.'/../../../dbSeed/exercises.json';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'clear',
                null,
                InputOption::VALUE_NONE,
                'Clear existing data before seeding'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Run the command without actually persisting data'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = (bool) $input->getOption('dry-run');
        $shouldClear = (bool) $input->getOption('clear');

        $io->title('Exercise Seeder');

        if ($isDryRun) {
            $io->note('Running in DRY-RUN mode - no data will be persisted');
        }

        // Step 1: Load and validate JSON file
        try {
            $data = $this->loadJsonData();
        } catch (\Exception $e) {
            $io->error('Failed to load JSON file: '.$e->getMessage());

            return Command::FAILURE;
        }

        // Step 2: Clear existing data if requested
        if ($shouldClear && !$isDryRun) {
            $io->section('Clearing existing data...');
            $this->clearExistingData($io);
        }

        // Step 3: Process categories and exercises
        $stats = [
            'categories_added' => 0,
            'categories_skipped' => 0,
            'exercises_added' => 0,
            'exercises_skipped' => 0,
        ];

        $io->section('Processing data...');

        $progressBar = new ProgressBar($output, count($data['categories']));
        $progressBar->start();

        foreach ($data['categories'] as $categoryData) {
            $this->processCategory($categoryData, $stats, $isDryRun);
            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);

        // Step 4: Flush changes to database
        if (!$isDryRun) {
            $io->section('Persisting data to database...');
            $this->entityManager->flush();
            $io->success('Data successfully persisted to database!');
        }

        // Step 5: Display summary
        $this->displaySummary($io, $stats, $isDryRun);

        return Command::SUCCESS;
    }

    /**
     * @return array{categories: array<int, array{name_pl: string, name_en: string, exercises: array<int, array{name_pl: string, name_en: string}>}>}
     */
    private function loadJsonData(): array
    {
        if (!file_exists(self::JSON_FILE_PATH)) {
            throw new \RuntimeException(sprintf('JSON file not found at path: %s', self::JSON_FILE_PATH));
        }

        $jsonContent = file_get_contents(self::JSON_FILE_PATH);
        if (false === $jsonContent) {
            throw new \RuntimeException('Failed to read JSON file');
        }

        $data = json_decode($jsonContent, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \RuntimeException('Invalid JSON: '.json_last_error_msg());
        }

        if (!isset($data['categories']) || !is_array($data['categories'])) {
            throw new \RuntimeException('Invalid JSON structure: missing "categories" key');
        }

        return $data;
    }

    private function clearExistingData(SymfonyStyle $io): void
    {
        $exercisesDeleted = $this->entityManager->createQuery('DELETE FROM App\Domain\Entity\Exercise')->execute();
        $categoriesDeleted = $this->entityManager->createQuery('DELETE FROM App\Domain\Entity\MuscleCategory')->execute();

        $io->info(sprintf('Deleted %d exercises and %d categories', $exercisesDeleted, $categoriesDeleted));
    }

    /**
     * @param array{name_pl: string, name_en: string, exercises: array<int, array{name_pl: string, name_en: string}>} $categoryData
     * @param array{categories_added: int, categories_skipped: int, exercises_added: int, exercises_skipped: int} $stats
     */
    private function processCategory(array $categoryData, array &$stats, bool $isDryRun): void
    {
        // Check if category already exists
        $existingCategory = $this->findCategoryByName($categoryData['name_pl']);

        if (null !== $existingCategory) {
            ++$stats['categories_skipped'];
            $category = $existingCategory;
        } else {
            $category = MuscleCategory::create(
                $categoryData['name_pl'],
                $categoryData['name_en']
            );

            if (!$isDryRun) {
                $this->entityManager->persist($category);
            }

            ++$stats['categories_added'];
        }

        // Process exercises for this category
        foreach ($categoryData['exercises'] as $exerciseData) {
            $this->processExercise($exerciseData, $category, $stats, $isDryRun);
        }
    }

    /**
     * @param array{name_pl: string, name_en: string} $exerciseData
     * @param array{categories_added: int, categories_skipped: int, exercises_added: int, exercises_skipped: int} $stats
     */
    private function processExercise(
        array $exerciseData,
        MuscleCategory $category,
        array &$stats,
        bool $isDryRun
    ): void {
        // Check if exercise already exists
        $existingExercise = $this->findExerciseByName($exerciseData['name_pl']);

        if (null !== $existingExercise) {
            ++$stats['exercises_skipped'];

            return;
        }

        $exercise = Exercise::create(
            $exerciseData['name_pl'],
            $category,
            $exerciseData['name_en']
        );

        if (!$isDryRun) {
            $this->entityManager->persist($exercise);
        }

        ++$stats['exercises_added'];
    }

    private function findCategoryByName(string $name): ?MuscleCategory
    {
        /** @var MuscleCategory|null */
        return $this->entityManager->getRepository(MuscleCategory::class)
            ->createQueryBuilder('mc')
            ->where('mc.namePl = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }

    private function findExerciseByName(string $name): ?Exercise
    {
        /** @var Exercise|null */
        return $this->entityManager->getRepository(Exercise::class)
            ->createQueryBuilder('e')
            ->where('e.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param array{categories_added: int, categories_skipped: int, exercises_added: int, exercises_skipped: int} $stats
     */
    private function displaySummary(SymfonyStyle $io, array $stats, bool $isDryRun): void
    {
        $io->section('Summary');

        $io->table(
            ['Metric', 'Count'],
            [
                ['Categories added', $stats['categories_added']],
                ['Categories skipped (already exist)', $stats['categories_skipped']],
                ['Exercises added', $stats['exercises_added']],
                ['Exercises skipped (already exist)', $stats['exercises_skipped']],
                ['Total categories', $stats['categories_added'] + $stats['categories_skipped']],
                ['Total exercises', $stats['exercises_added'] + $stats['exercises_skipped']],
            ]
        );

        if ($isDryRun) {
            $io->warning('This was a DRY-RUN. No data was persisted to the database.');
        } else {
            $io->success('Seeding completed successfully!');
        }
    }
}

