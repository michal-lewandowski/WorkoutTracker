<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Statistics;

use App\Domain\Entity\Exercise;
use App\Domain\Entity\ExerciseSet;
use App\Domain\Entity\MuscleCategory;
use App\Domain\Entity\User;
use App\Domain\Entity\WorkoutExercise;
use App\Domain\Entity\WorkoutSession;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class GetExerciseStatisticsControllerTest extends WebTestCase
{
    /**
     * Test - endpoint wymaga autoryzacji
     */
    public function testGetExerciseStatisticsWithoutTokenReturns401(): void
    {
        $client = static::createClient();
        $exerciseId = '00000000-0000-4000-8000-000000000000';

        $client->request(
            method: 'GET',
            uri: "/api/v1/statistics/exercise/{$exerciseId}",
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Test - nieprawidłowy format UUID zwraca 400
     */
    public function testGetExerciseStatisticsWithInvalidUuidFormatReturns400(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);

        $client->request(
            method: 'GET',
            uri: '/api/v1/statistics/exercise/invalid-uuid-format',
            server: [
                'HTTP_AUTHORIZATION' => "Bearer $token",
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Invalid exercise ID format', $data['error']);
    }

    /**
     * Test - nieistniejące ćwiczenie zwraca 404
     */
    public function testGetExerciseStatisticsWithNonExistentExerciseReturns404(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);
        $nonExistentId = '00000000-0000-4000-8000-000000000000';

        $client->request(
            method: 'GET',
            uri: "/api/v1/statistics/exercise/{$nonExistentId}",
            server: [
                'HTTP_AUTHORIZATION' => "Bearer $token",
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Exercise not found', $data['message']);
    }

    /**
     * Test - zwraca puste statystyki gdy użytkownik nie ma sesji z danym ćwiczeniem
     */
    public function testGetExerciseStatisticsWithNoSessionsReturnsEmptyData(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);

        // Utwórz ćwiczenie ale bez sesji treningowych
        $em = self::getContainer()->get('doctrine')->getManager();
        $category = MuscleCategory::create('Test Category Empty', 'Test Category Empty');
        $em->persist($category);

        $exercise = Exercise::create('Test Exercise Empty', $category, 'Test Exercise Empty EN');
        $em->persist($exercise);
        $em->flush();

        $client->request(
            method: 'GET',
            uri: "/api/v1/statistics/exercise/{$exercise->getId()}",
            server: [
                'HTTP_AUTHORIZATION' => "Bearer $token",
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('exerciseId', $data);
        $this->assertArrayHasKey('exercise', $data);
        $this->assertArrayHasKey('dataPoints', $data);
        $this->assertArrayHasKey('summary', $data);
        
        $this->assertEquals($exercise->getId(), $data['exerciseId']);
        $this->assertEmpty($data['dataPoints']);
        $this->assertNull($data['summary']);
    }

    /**
     * Test - happy path - zwraca poprawne statystyki
     */
    public function testGetExerciseStatisticsReturnsCorrectData(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);
        
        // Pobierz zalogowanego użytkownika
        $em = self::getContainer()->get('doctrine')->getManager();
        $userRepo = $em->getRepository(User::class);
        $users = $userRepo->findAll();
        $user = end($users); // Ostatni utworzony użytkownik (przez getAuthToken)

        // Utwórz testowe dane
        $category = MuscleCategory::create('Test Category Stats', 'Test Category Stats');
        $em->persist($category);

        $exercise = Exercise::create('Bench Press Stats', $category, 'Bench Press Stats EN');
        $em->persist($exercise);
        $em->flush();

        // Utwórz 3 sesje treningowe z różnymi wagami
        $session1 = WorkoutSession::create(
            user: $user,
            date: new \DateTimeImmutable('2025-01-01'),
            name: 'Session 1'
        );
        $em->persist($session1);

        $session2 = WorkoutSession::create(
            user: $user,
            date: new \DateTimeImmutable('2025-01-08'),
            name: 'Session 2'
        );
        $em->persist($session2);

        $session3 = WorkoutSession::create(
            user: $user,
            date: new \DateTimeImmutable('2025-01-15'),
            name: 'Session 3'
        );
        $em->persist($session3);

        $em->flush();

        // Dodaj workout exercises z setami
        // Session 1: max 70kg
        $workoutEx1 = WorkoutExercise::create($session1, $exercise);
        $em->persist($workoutEx1);
        $em->flush();
        
        $set1_1 = ExerciseSet::create($workoutEx1, 3, 10, 70000); // 70kg
        $em->persist($set1_1);

        // Session 2: max 75kg
        $workoutEx2 = WorkoutExercise::create($session2, $exercise);
        $em->persist($workoutEx2);
        $em->flush();
        
        $set2_1 = ExerciseSet::create($workoutEx2, 3, 10, 72500); // 72.5kg
        $set2_2 = ExerciseSet::create($workoutEx2, 2, 8, 75000); // 75kg (max)
        $em->persist($set2_1);
        $em->persist($set2_2);

        // Session 3: max 77.5kg
        $workoutEx3 = WorkoutExercise::create($session3, $exercise);
        $em->persist($workoutEx3);
        $em->flush();
        
        $set3_1 = ExerciseSet::create($workoutEx3, 3, 10, 77500); // 77.5kg
        $em->persist($set3_1);

        $em->flush();

        // Wykonaj żądanie
        $client->request(
            method: 'GET',
            uri: "/api/v1/statistics/exercise/{$exercise->getId()}",
            server: [
                'HTTP_AUTHORIZATION' => "Bearer $token",
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode($client->getResponse()->getContent(), true);
        
        // Sprawdź strukturę odpowiedzi
        $this->assertArrayHasKey('exerciseId', $data);
        $this->assertArrayHasKey('exercise', $data);
        $this->assertArrayHasKey('dataPoints', $data);
        $this->assertArrayHasKey('summary', $data);
        
        // Sprawdź exerciseId
        $this->assertEquals($exercise->getId(), $data['exerciseId']);
        
        // Sprawdź exercise
        $this->assertEquals($exercise->getId(), $data['exercise']['id']);
        $this->assertEquals('Bench Press Stats', $data['exercise']['name']);
        $this->assertEquals('Bench Press Stats EN', $data['exercise']['nameEn']);
        
        // Sprawdź dataPoints
        $this->assertCount(3, $data['dataPoints']);
        
        $this->assertEquals('2025-01-01', $data['dataPoints'][0]['date']);
        $this->assertEquals($session1->getId(), $data['dataPoints'][0]['sessionId']);
        $this->assertEquals(70.0, $data['dataPoints'][0]['maxWeightKg']);
        
        $this->assertEquals('2025-01-08', $data['dataPoints'][1]['date']);
        $this->assertEquals($session2->getId(), $data['dataPoints'][1]['sessionId']);
        $this->assertEquals(75.0, $data['dataPoints'][1]['maxWeightKg']);
        
        $this->assertEquals('2025-01-15', $data['dataPoints'][2]['date']);
        $this->assertEquals($session3->getId(), $data['dataPoints'][2]['sessionId']);
        $this->assertEquals(77.5, $data['dataPoints'][2]['maxWeightKg']);
        
        // Sprawdź summary
        $this->assertNotNull($data['summary']);
        $this->assertEquals(3, $data['summary']['totalSessions']);
        $this->assertEquals(77.5, $data['summary']['personalRecord']);
        $this->assertEquals('2025-01-15', $data['summary']['prDate']);
        $this->assertEquals(70.0, $data['summary']['firstWeight']);
        $this->assertEquals(77.5, $data['summary']['latestWeight']);
        
        // Sprawdź postęp procentowy: ((77.5 - 70) / 70) * 100 = 10.71%
        $this->assertEquals(10.71, $data['summary']['progressPercentage']);
    }

    /**
     * Test - filtrowanie po dacie (dateFrom, dateTo)
     */
    public function testGetExerciseStatisticsWithDateFilters(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);
        
        $em = self::getContainer()->get('doctrine')->getManager();
        $userRepo = $em->getRepository(User::class);
        $users = $userRepo->findAll();
        $user = end($users);

        $category = MuscleCategory::create('Test Category Date Filter', 'Test Category Date Filter');
        $em->persist($category);

        $exercise = Exercise::create('Squat Date Filter', $category);
        $em->persist($exercise);
        $em->flush();

        // Utwórz 3 sesje: 2024-12-01, 2025-01-15, 2025-02-01
        $session1 = WorkoutSession::create($user, new \DateTimeImmutable('2024-12-01'));
        $session2 = WorkoutSession::create($user, new \DateTimeImmutable('2025-01-15'));
        $session3 = WorkoutSession::create($user, new \DateTimeImmutable('2025-02-01'));
        $em->persist($session1);
        $em->persist($session2);
        $em->persist($session3);
        $em->flush();

        $workoutEx1 = WorkoutExercise::create($session1, $exercise);
        $workoutEx2 = WorkoutExercise::create($session2, $exercise);
        $workoutEx3 = WorkoutExercise::create($session3, $exercise);
        $em->persist($workoutEx1);
        $em->persist($workoutEx2);
        $em->persist($workoutEx3);
        $em->flush();

        $em->persist(ExerciseSet::create($workoutEx1, 3, 10, 100000));
        $em->persist(ExerciseSet::create($workoutEx2, 3, 10, 110000));
        $em->persist(ExerciseSet::create($workoutEx3, 3, 10, 120000));
        $em->flush();

        // Filtruj tylko do 2025-01-01 do 2025-01-31
        $client->request(
            method: 'GET',
            uri: "/api/v1/statistics/exercise/{$exercise->getId()}?dateFrom=2025-01-01&dateTo=2025-01-31",
            server: [
                'HTTP_AUTHORIZATION' => "Bearer $token",
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        
        // Powinien zwrócić tylko session2 (2025-01-15)
        $this->assertCount(1, $data['dataPoints']);
        $this->assertEquals('2025-01-15', $data['dataPoints'][0]['date']);
        $this->assertEquals(110.0, $data['dataPoints'][0]['maxWeightKg']);
    }

    /**
     * Test - parametr limit ogranicza liczbę wyników
     */
    public function testGetExerciseStatisticsWithLimit(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);
        
        $em = self::getContainer()->get('doctrine')->getManager();
        $userRepo = $em->getRepository(User::class);
        $users = $userRepo->findAll();
        $user = end($users);

        $category = MuscleCategory::create('Test Category Limit', 'Test Category Limit');
        $em->persist($category);

        $exercise = Exercise::create('Deadlift Limit', $category);
        $em->persist($exercise);
        $em->flush();

        // Utwórz 5 sesji
        for ($i = 1; $i <= 5; ++$i) {
            $session = WorkoutSession::create(
                $user, 
                new \DateTimeImmutable("2025-01-" . sprintf("%02d", $i * 2))
            );
            $em->persist($session);
            $em->flush();

            $workoutEx = WorkoutExercise::create($session, $exercise);
            $em->persist($workoutEx);
            $em->flush();

            $set = ExerciseSet::create($workoutEx, 3, 10, 100000 + ($i * 5000));
            $em->persist($set);
        }
        $em->flush();

        // Ogranicz do 3 wyników
        $client->request(
            method: 'GET',
            uri: "/api/v1/statistics/exercise/{$exercise->getId()}?limit=3",
            server: [
                'HTTP_AUTHORIZATION' => "Bearer $token",
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        
        // Powinien zwrócić tylko 3 pierwsze sesje
        $this->assertCount(3, $data['dataPoints']);
    }

    /**
     * Test - odpowiedź zawiera wszystkie wymagane pola
     */
    public function testGetExerciseStatisticsReturnsAllRequiredFields(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);
        
        $em = self::getContainer()->get('doctrine')->getManager();
        $userRepo = $em->getRepository(User::class);
        $users = $userRepo->findAll();
        $user = end($users);

        $category = MuscleCategory::create('Test Fields', 'Test Fields');
        $em->persist($category);

        $exercise = Exercise::create('Test Fields Exercise', $category, 'Test Fields Exercise EN');
        $em->persist($exercise);
        $em->flush();

        $session = WorkoutSession::create($user, new \DateTimeImmutable());
        $em->persist($session);
        $em->flush();

        $workoutEx = WorkoutExercise::create($session, $exercise);
        $em->persist($workoutEx);
        $em->flush();

        $set = ExerciseSet::create($workoutEx, 3, 10, 100000);
        $em->persist($set);
        $em->flush();

        $client->request(
            method: 'GET',
            uri: "/api/v1/statistics/exercise/{$exercise->getId()}",
            server: ['HTTP_AUTHORIZATION' => "Bearer $token"]
        );

        $data = json_decode($client->getResponse()->getContent(), true);
        
        // Sprawdź główne pola
        $this->assertArrayHasKey('exerciseId', $data);
        $this->assertArrayHasKey('exercise', $data);
        $this->assertArrayHasKey('dataPoints', $data);
        $this->assertArrayHasKey('summary', $data);
        
        // Sprawdź exercise
        $this->assertArrayHasKey('id', $data['exercise']);
        $this->assertArrayHasKey('name', $data['exercise']);
        $this->assertArrayHasKey('nameEn', $data['exercise']);
        
        // Sprawdź dataPoints
        $this->assertNotEmpty($data['dataPoints']);
        $this->assertArrayHasKey('date', $data['dataPoints'][0]);
        $this->assertArrayHasKey('sessionId', $data['dataPoints'][0]);
        $this->assertArrayHasKey('maxWeightKg', $data['dataPoints'][0]);
        
        // Sprawdź summary
        $this->assertNotNull($data['summary']);
        $this->assertArrayHasKey('totalSessions', $data['summary']);
        $this->assertArrayHasKey('personalRecord', $data['summary']);
        $this->assertArrayHasKey('prDate', $data['summary']);
        $this->assertArrayHasKey('firstWeight', $data['summary']);
        $this->assertArrayHasKey('latestWeight', $data['summary']);
        $this->assertArrayHasKey('progressPercentage', $data['summary']);
    }

    /**
     * Helper method to get JWT token for authenticated requests.
     */
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

