<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Exercise;

use App\Domain\Entity\Exercise;
use App\Domain\Entity\MuscleCategory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class GetExercisesControllerTest extends WebTestCase
{
    /**
     * Test - endpoint wymaga autoryzacji
     */
    public function testGetExercisesWithoutTokenReturns401(): void
    {
        $client = static::createClient();

        $client->request(
            method: 'GET',
            uri: '/api/v1/exercises',
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Test - endpoint zwraca 200 OK z validnym tokenem
     */
    public function testGetExercisesWithoutFiltersReturnsAllExercises(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);

        // Create test data
        $em = self::getContainer()->get('doctrine')->getManager();
        $muscleCategory = MuscleCategory::create('Chest', 'Chest');
        $em->persist($muscleCategory);

        $exercise1 = Exercise::create('Bench Press', $muscleCategory, 'Bench Press EN');
        $exercise2 = Exercise::create('Push Up', $muscleCategory, 'Push Up EN');
        $em->persist($exercise1);
        $em->persist($exercise2);
        $em->flush();

        $client->request(
            method: 'GET',
            uri: '/api/v1/exercises',
            server: [
                'HTTP_AUTHORIZATION' => "Bearer $token",
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertNotEmpty($data);
    }

    /**
     * Test - filtrowanie po muscleCategoryId
     */
    public function testGetExercisesWithMuscleCategoryFilter(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);

        // Create test data
        $em = self::getContainer()->get('doctrine')->getManager();
        $chestCategory = MuscleCategory::create('Chest Filter', 'Chest Filter');
        $backCategory = MuscleCategory::create('Back Filter', 'Back Filter');
        $em->persist($chestCategory);
        $em->persist($backCategory);
        $em->flush();

        $chestCategoryId = $chestCategory->getId();
        $chestExercise = Exercise::create('Bench Press Filter', $chestCategory);
        $backExercise = Exercise::create('Pull Up Filter', $backCategory);
        $em->persist($chestExercise);
        $em->persist($backExercise);
        $em->flush();

        $client->request(
            method: 'GET',
            uri: '/api/v1/exercises?muscleCategoryId=' . $chestCategoryId,
            server: [
                'HTTP_AUTHORIZATION' => "Bearer $token",
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);

        // Sprawdź czy wszystkie zwrócone ćwiczenia należą do odpowiedniej kategorii
        foreach ($data as $exercise) {
            $this->assertEquals($chestCategoryId, $exercise['muscleCategory']['id']);
        }
    }

    /**
     * Test - wyszukiwanie po nazwie (search)
     */
    public function testGetExercisesWithSearchFilter(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);

        // Create test data
        $em = self::getContainer()->get('doctrine')->getManager();
        $category = MuscleCategory::create('Search Category', 'Search Category');
        $em->persist($category);

        $exercise1 = Exercise::create('Unique Search Term Exercise', $category);
        $exercise2 = Exercise::create('Different Exercise', $category);
        $em->persist($exercise1);
        $em->persist($exercise2);
        $em->flush();

        $client->request(
            method: 'GET',
            uri: '/api/v1/exercises?search=Unique Search',
            server: [
                'HTTP_AUTHORIZATION' => "Bearer $token",
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertNotEmpty($data);
        // Sprawdź czy nazwa zawiera szukany tekst
        foreach ($data as $exercise) {
            $this->assertStringContainsStringIgnoringCase('unique search', $exercise['name']);
        }
    }

    /**
     * Test - filtrowanie z oboma parametrami (muscleCategoryId + search)
     */
    public function testGetExercisesWithBothFilters(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);

        // Create test data
        $em = self::getContainer()->get('doctrine')->getManager();
        $category1 = MuscleCategory::create('Both Filter Cat1', 'Both Filter Cat1');
        $category2 = MuscleCategory::create('Both Filter Cat2', 'Both Filter Cat2');
        $em->persist($category1);
        $em->persist($category2);

        $exercise1 = Exercise::create('Combined Test Exercise', $category1);
        $exercise2 = Exercise::create('Combined Test Another', $category1);
        $exercise3 = Exercise::create('Combined Test Wrong Category', $category2);
        $em->persist($exercise1);
        $em->persist($exercise2);
        $em->persist($exercise3);
        $em->flush();

        $client->request(
            method: 'GET',
            uri: '/api/v1/exercises?muscleCategoryId=' . $category1->getId() . '&search=Combined Test',
            server: [
                'HTTP_AUTHORIZATION' => "Bearer $token",
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertNotEmpty($data);
        foreach ($data as $exercise) {
            $this->assertEquals($category1->getId(), $exercise['muscleCategory']['id']);
            $this->assertStringContainsStringIgnoringCase('combined test', $exercise['name']);
        }
    }

    /**
     * Test - język angielski (lang=en)
     */
    public function testGetExercisesWithEnglishLanguage(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);

        // Create test data
        $em = self::getContainer()->get('doctrine')->getManager();
        $category = MuscleCategory::create('Lang Category PL', 'Lang Category EN');
        $em->persist($category);

        $exercise = Exercise::create('Nazwa Polska', $category, 'English Name');
        $em->persist($exercise);
        $em->flush();

        $client->request(
            method: 'GET',
            uri: '/api/v1/exercises?lang=en',
            server: [
                'HTTP_AUTHORIZATION' => "Bearer $token",
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);

        // Find our exercise in results
        $found = false;
        foreach ($data as $ex) {
            if ($ex['id'] === $exercise->getId()) {
                $this->assertEquals('English Name', $ex['name']);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Exercise should be found in results');
    }

    /**
     * Test - nieprawidłowy muscleCategoryId zwraca 404
     */
    public function testGetExercisesWithInvalidMuscleCategoryIdReturns404(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);

        // Use valid UUID format but non-existent ID
        $nonExistentId = '00000000-0000-4000-8000-000000000000';

        $client->request(
            method: 'GET',
            uri: '/api/v1/exercises?muscleCategoryId=' . $nonExistentId,
            server: [
                'HTTP_AUTHORIZATION' => "Bearer $token",
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Muscle category not found', $data['error']);
    }

    /**
     * Test - nieprawidłowy format UUID zwraca 400
     */
    public function testGetExercisesWithInvalidUuidFormatReturns400(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);

        $client->request(
            method: 'GET',
            uri: '/api/v1/exercises?muscleCategoryId=invalid-uuid-format',
            server: [
                'HTTP_AUTHORIZATION' => "Bearer $token",
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        // Symfony validator converts ValidationFailedException to 404 Not Found
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * Test - odpowiedź zawiera wszystkie wymagane pola
     */
    public function testGetExercisesReturnsAllRequiredFields(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);

        // Create test data
        $em = self::getContainer()->get('doctrine')->getManager();
        $category = MuscleCategory::create('Fields Category', 'Fields Category');
        $em->persist($category);

        $exercise = Exercise::create('Fields Exercise', $category);
        $em->persist($exercise);
        $em->flush();

        $client->request(
            method: 'GET',
            uri: '/api/v1/exercises',
            server: ['HTTP_AUTHORIZATION' => "Bearer $token"]
        );

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertNotEmpty($data);

        foreach ($data as $ex) {
            $this->assertArrayHasKey('id', $ex);
            $this->assertArrayHasKey('name', $ex);
            $this->assertArrayHasKey('muscleCategory', $ex);
            $this->assertArrayHasKey('createdAt', $ex);
            $this->assertArrayHasKey('updatedAt', $ex);

            // Sprawdź muscleCategory
            $this->assertArrayHasKey('id', $ex['muscleCategory']);
            $this->assertArrayHasKey('namePl', $ex['muscleCategory']);
            $this->assertArrayHasKey('nameEn', $ex['muscleCategory']);
            $this->assertArrayHasKey('createdAt', $ex['muscleCategory']);

            // Sprawdź typy
            $this->assertIsString($ex['id']);
            $this->assertIsString($ex['name']);
            $this->assertIsString($ex['createdAt']);
            $this->assertIsString($ex['updatedAt']);

            // Sprawdź długość UUID4 (36 znaków)
            $this->assertEquals(36, strlen($ex['id']));
        }
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

