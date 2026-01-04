<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Statistics;

use App\Domain\Entity\Exercise;
use App\Domain\Entity\MuscleCategory;
use App\Domain\Entity\User;
use App\Domain\Entity\WorkoutExercise;
use App\Domain\Entity\WorkoutSession;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

final class GetDoneExercisesControllerTest extends WebTestCase
{
    private const API_URL = '/api/v1/statistics/done-exercises';

    public function testGetDoneExercisesWithoutTokenReturns401(): void
    {
        $client = static::createClient();

        $client->request('GET', self::API_URL);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetDoneExercisesSuccess(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);
        $em = self::getContainer()->get('doctrine')->getManager();

        $userRepo = $em->getRepository(User::class);
        $users = $userRepo->findAll();
        $user = end($users);

        $muscleCategory = MuscleCategory::create('Triceps Test', 'Triceps Test');
        $em->persist($muscleCategory);

        $exercise1 = Exercise::create('Diamentowe pompki', $muscleCategory);
        $exercise2 = Exercise::create('Wyciskanie', $muscleCategory);
        $em->persist($exercise1);
        $em->persist($exercise2);

        $session = WorkoutSession::create(
            Uuid::v4(),
            $user,
            new \DateTimeImmutable('2024-01-15')
        );
        $em->persist($session);
        $em->flush();

        $workoutExercise = WorkoutExercise::create(Uuid::v4(), $session, $exercise1);
        $em->persist($workoutExercise);
        $em->flush();

        $client->request('GET', self::API_URL, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertCount(1, $data);

        $exerciseData = $data[0];
        $this->assertArrayHasKey('id', $exerciseData);
        $this->assertArrayHasKey('name', $exerciseData);
        $this->assertArrayHasKey('workoutsCount', $exerciseData);
        $this->assertArrayHasKey('muscleCategory', $exerciseData);
        $this->assertArrayHasKey('createdAt', $exerciseData);
        $this->assertArrayHasKey('updatedAt', $exerciseData);
        $this->assertSame('Diamentowe pompki', $exerciseData['name']);
        $this->assertSame(1, $exerciseData['workoutsCount']);
    }

    public function testGetDoneExercisesWithDateFilter(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);
        $em = self::getContainer()->get('doctrine')->getManager();

        $userRepo = $em->getRepository(User::class);
        $users = $userRepo->findAll();
        $user = end($users);

        $muscleCategory = MuscleCategory::create('Chest Filter', 'Chest Filter');
        $em->persist($muscleCategory);

        $exercise = Exercise::create('Bench Press Filter', $muscleCategory);
        $em->persist($exercise);
        $em->flush();

        // Session in 2024
        $session2024 = WorkoutSession::create(
            Uuid::v4(),
            $user,
            new \DateTimeImmutable('2024-06-15')
        );
        $em->persist($session2024);
        $em->flush();
        $we2024 = WorkoutExercise::create(Uuid::v4(), $session2024, $exercise);
        $em->persist($we2024);

        // Session in 2025
        $session2025 = WorkoutSession::create(
            Uuid::v4(),
            $user,
            new \DateTimeImmutable('2025-01-15')
        );
        $em->persist($session2025);
        $em->flush();
        $we2025 = WorkoutExercise::create(Uuid::v4(), $session2025, $exercise);
        $em->persist($we2025);

        $em->flush();

        // Filter only 2024 sessions
        $client->request('GET', self::API_URL . '?dateFrom=2024-01-01&dateTo=2024-12-31', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $data);
        $this->assertSame('Bench Press Filter', $data[0]['name']);
        $this->assertSame(1, $data[0]['workoutsCount']);
    }

    public function testGetDoneExercisesSortingByNameAsc(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);
        $em = self::getContainer()->get('doctrine')->getManager();

        $userRepo = $em->getRepository(User::class);
        $users = $userRepo->findAll();
        $user = end($users);

        $muscleCategory = MuscleCategory::create('Arms Sort', 'Arms Sort');
        $em->persist($muscleCategory);

        $exerciseA = Exercise::create('AAA Exercise', $muscleCategory);
        $exerciseZ = Exercise::create('ZZZ Exercise', $muscleCategory);
        $em->persist($exerciseA);
        $em->persist($exerciseZ);
        $em->flush();

        $session = WorkoutSession::create(
            Uuid::v4(),
            $user,
            new \DateTimeImmutable('2024-01-15')
        );
        $em->persist($session);
        $em->flush();

        $weA = WorkoutExercise::create(Uuid::v4(), $session, $exerciseA);
        $em->persist($weA);
        $weZ = WorkoutExercise::create(Uuid::v4(), $session, $exerciseZ);
        $em->persist($weZ);
        $em->flush();

        // Test ascending order
        $client->request('GET', self::API_URL . '?sortBy=name&sortOrder=asc', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(2, $data);
        $this->assertSame('AAA Exercise', $data[0]['name']);
        $this->assertSame('ZZZ Exercise', $data[1]['name']);
    }

    public function testGetDoneExercisesSortingByNameDesc(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);
        $em = self::getContainer()->get('doctrine')->getManager();

        $userRepo = $em->getRepository(User::class);
        $users = $userRepo->findAll();
        $user = end($users);

        $muscleCategory = MuscleCategory::create('Legs Sort Desc', 'Legs Sort Desc');
        $em->persist($muscleCategory);

        $exerciseB = Exercise::create('BBB Exercise', $muscleCategory);
        $exerciseY = Exercise::create('YYY Exercise', $muscleCategory);
        $em->persist($exerciseB);
        $em->persist($exerciseY);
        $em->flush();

        $session = WorkoutSession::create(
            Uuid::v4(),
            $user,
            new \DateTimeImmutable('2024-01-15')
        );
        $em->persist($session);
        $em->flush();

        $weB = WorkoutExercise::create(Uuid::v4(), $session, $exerciseB);
        $em->persist($weB);
        $weY = WorkoutExercise::create(Uuid::v4(), $session, $exerciseY);
        $em->persist($weY);
        $em->flush();

        // Test descending order
        $client->request('GET', self::API_URL . '?sortBy=name&sortOrder=desc', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(2, $data);
        $this->assertSame('YYY Exercise', $data[0]['name']);
        $this->assertSame('BBB Exercise', $data[1]['name']);
    }

    public function testEmptyResultWhenNoExercisesPerformed(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);

        $client->request('GET', self::API_URL, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertCount(0, $data);
    }

    public function testWorkoutsCountWithMultipleSessions(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);
        $em = self::getContainer()->get('doctrine')->getManager();

        $userRepo = $em->getRepository(User::class);
        $users = $userRepo->findAll();
        $user = end($users);

        $muscleCategory = MuscleCategory::create('Legs Count', 'Legs Count');
        $em->persist($muscleCategory);

        $exercise = Exercise::create('Squats Count', $muscleCategory);
        $em->persist($exercise);
        $em->flush();

        // Create 5 different workout sessions with the same exercise
        for ($i = 1; $i <= 5; $i++) {
            $session = WorkoutSession::create(
                Uuid::v4(),
                $user,
                new \DateTimeImmutable("2024-01-0{$i}")
            );
            $em->persist($session);
            $em->flush();

            $workoutExercise = WorkoutExercise::create(Uuid::v4(), $session, $exercise);
            $em->persist($workoutExercise);
        }

        $em->flush();

        $client->request('GET', self::API_URL, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $data);
        $this->assertSame('Squats Count', $data[0]['name']);
        $this->assertSame(5, $data[0]['workoutsCount']);
    }

    public function testInvalidSortByParameter(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);

        $client->request('GET', self::API_URL . '?sortBy=invalid', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testInvalidSortOrderParameter(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);

        $client->request('GET', self::API_URL . '?sortOrder=invalid', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testMuscleCategoryDataIsIncluded(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);
        $em = self::getContainer()->get('doctrine')->getManager();

        $userRepo = $em->getRepository(User::class);
        $users = $userRepo->findAll();
        $user = end($users);

        $muscleCategory = MuscleCategory::create('Barki Test', 'Shoulders Test');
        $em->persist($muscleCategory);

        $exercise = Exercise::create('Press Test', $muscleCategory);
        $em->persist($exercise);
        $em->flush();

        $session = WorkoutSession::create(
            Uuid::v4(),
            $user,
            new \DateTimeImmutable('2024-01-15')
        );
        $em->persist($session);
        $em->flush();

        $workoutExercise = WorkoutExercise::create(Uuid::v4(), $session, $exercise);
        $em->persist($workoutExercise);
        $em->flush();

        $client->request('GET', self::API_URL, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertCount(1, $data);
        $this->assertArrayHasKey('muscleCategory', $data[0]);
        $this->assertArrayHasKey('id', $data[0]['muscleCategory']);
        $this->assertArrayHasKey('namePl', $data[0]['muscleCategory']);
        $this->assertArrayHasKey('nameEn', $data[0]['muscleCategory']);
        $this->assertArrayHasKey('createdAt', $data[0]['muscleCategory']);
        $this->assertSame('Barki Test', $data[0]['muscleCategory']['namePl']);
        $this->assertSame('Shoulders Test', $data[0]['muscleCategory']['nameEn']);
    }

    public function testSortByLastUsed(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);
        $em = self::getContainer()->get('doctrine')->getManager();

        $userRepo = $em->getRepository(User::class);
        $users = $userRepo->findAll();
        $user = end($users);

        $muscleCategory = MuscleCategory::create('Last Used Test', 'Last Used Test');
        $em->persist($muscleCategory);

        $exercise1 = Exercise::create('Oldest Used', $muscleCategory);
        $exercise2 = Exercise::create('Newest Used', $muscleCategory);
        $em->persist($exercise1);
        $em->persist($exercise2);
        $em->flush();

        // Exercise1 used on older date
        $session1 = WorkoutSession::create(
            Uuid::v4(),
            $user,
            new \DateTimeImmutable('2024-01-01')
        );
        $em->persist($session1);
        $em->flush();
        $we1 = WorkoutExercise::create(Uuid::v4(), $session1, $exercise1);
        $em->persist($we1);

        // Exercise2 used on newer date
        $session2 = WorkoutSession::create(
            Uuid::v4(),
            $user,
            new \DateTimeImmutable('2024-12-31')
        );
        $em->persist($session2);
        $em->flush();
        $we2 = WorkoutExercise::create(Uuid::v4(), $session2, $exercise2);
        $em->persist($we2);

        $em->flush();

        // Sort by lastUsed desc (newest first)
        $client->request('GET', self::API_URL . '?sortBy=lastUsed&sortOrder=desc', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(2, $data);
        $this->assertSame('Newest Used', $data[0]['name']);
        $this->assertSame('Oldest Used', $data[1]['name']);
    }

    public function testUserOnlySeesTheirOwnExercises(): void
    {
        $client = static::createClient();
        $token1 = $this->getAuthToken($client);
        $em = self::getContainer()->get('doctrine')->getManager();

        $userRepo = $em->getRepository(User::class);
        $users = $userRepo->findAll();
        $user1 = end($users);

        $muscleCategory = MuscleCategory::create('Isolation Test', 'Isolation Test');
        $em->persist($muscleCategory);

        $exercise = Exercise::create('User1 Exercise', $muscleCategory);
        $em->persist($exercise);
        $em->flush();

        // User1 performs exercise
        $session1 = WorkoutSession::create(
            Uuid::v4(),
            $user1,
            new \DateTimeImmutable('2024-01-15')
        );
        $em->persist($session1);
        $em->flush();
        $we1 = WorkoutExercise::create(Uuid::v4(), $session1, $exercise);
        $em->persist($we1);
        $em->flush();

        // User1 sees their exercise
        $client->request('GET', self::API_URL, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token1,
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $data);
        $this->assertSame('User1 Exercise', $data[0]['name']);

        // Create second user and verify they don't see user1's exercises
        $token2 = $this->getAuthToken($client);
        
        $client->request('GET', self::API_URL, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token2,
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(0, $data); // User2 has no exercises
    }

    private function getAuthToken(KernelBrowser $client): string
    {
        $email = 'testuser' . uniqid() . time() . '@example.com';
        $password = 'SecurePass123';

        $jsonContent = json_encode([
            'email' => $email,
            'password' => $password,
            'passwordConfirmation' => $password,
        ]);
        $this->assertIsString($jsonContent);

        $client->request(
            method: 'POST',
            uri: '/api/v1/auth/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $jsonContent,
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $responseData = json_decode($content, true);
        $this->assertIsArray($responseData);

        $this->assertArrayHasKey('token', $responseData);
        $this->assertIsString($responseData['token']);

        return $responseData['token'];
    }
}


