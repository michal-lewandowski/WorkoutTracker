<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Exercise;

use App\Domain\Entity\Exercise;
use App\Domain\Entity\MuscleCategory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class GetExerciseByIdControllerTest extends WebTestCase
{
    /**
     * Test - endpoint wymaga autoryzacji
     */
    public function testGetExerciseByIdWithoutAuthenticationReturns401(): void
    {
        $client = static::createClient();

        // Use a valid UUID format
        $client->request(
            method: 'GET',
            uri: '/api/v1/exercises/123e4567-e89b-12d3-a456-426614174000',
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Test - endpoint zwraca 200 OK z prawidłowym ID
     */
    public function testGetExerciseByIdReturnsExercise(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);

        // Create test data
        $em = self::getContainer()->get('doctrine')->getManager();
        $category = MuscleCategory::create('Test Category', 'Test Category EN');
        $em->persist($category);

        $exercise = Exercise::create('Test Exercise', $category, 'Test Exercise EN');
        $em->persist($exercise);
        $em->flush();

        $client->request(
            method: 'GET',
            uri: '/api/v1/exercises/' . $exercise->getId(),
            server: [
                'HTTP_AUTHORIZATION' => "Bearer $token",
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertEquals($exercise->getId(), $data['id']);
        $this->assertEquals('Test Exercise', $data['name']); // default lang=pl
    }

    /**
     * Test - nieistniejące ID zwraca 404
     */
    public function testGetExerciseByIdWithNonExistentIdReturns404(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);

        // Use valid UUID format but non-existent ID
        $nonExistentId = '00000000-0000-4000-8000-000000000000';

        $client->request(
            method: 'GET',
            uri: '/api/v1/exercises/' . $nonExistentId,
            server: [
                'HTTP_AUTHORIZATION' => "Bearer $token",
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Exercise not found', $data['error']);
    }

    /**
     * Test - nieprawidłowy format UUID zwraca 400
     */
    public function testGetExerciseByIdWithInvalidUuidFormatReturns400(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);

        $client->request(
            method: 'GET',
            uri: '/api/v1/exercises/invalid-uuid-format',
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
     * Test - odpowiedź zawiera wszystkie wymagane pola
     */
    public function testGetExerciseByIdReturnsAllRequiredFields(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);

        // Create test data
        $em = self::getContainer()->get('doctrine')->getManager();
        $category = MuscleCategory::create('Fields Category', 'Fields Category EN');
        $em->persist($category);

        $exercise = Exercise::create('Fields Test', $category, 'Fields Test EN');
        $em->persist($exercise);
        $em->flush();

        $client->request(
            method: 'GET',
            uri: '/api/v1/exercises/' . $exercise->getId(),
            server: ['HTTP_AUTHORIZATION' => "Bearer $token"]
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);

        // Sprawdź wymagane pola
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('muscleCategory', $data);
        $this->assertArrayHasKey('createdAt', $data);
        $this->assertArrayHasKey('updatedAt', $data);

        // Sprawdź muscleCategory
        $this->assertArrayHasKey('id', $data['muscleCategory']);
        $this->assertArrayHasKey('namePl', $data['muscleCategory']);
        $this->assertArrayHasKey('nameEn', $data['muscleCategory']);
        $this->assertArrayHasKey('createdAt', $data['muscleCategory']);

        // Sprawdź typy
        $this->assertIsString($data['id']);
        $this->assertIsString($data['name']);
        $this->assertIsString($data['createdAt']);
        $this->assertIsString($data['updatedAt']);

        // Sprawdź długość UUID4 (36 znaków)
        $this->assertEquals(36, strlen($data['id']));

        // Sprawdź wartości
        $this->assertEquals($exercise->getId(), $data['id']);
        $this->assertEquals('Fields Test', $data['name']);
    }

    /**
     * Test - odpowiedź jest w formacie JSON
     */
    public function testGetExerciseByIdReturnsValidJson(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);

        // Create test data
        $em = self::getContainer()->get('doctrine')->getManager();
        $category = MuscleCategory::create('JSON Category', 'JSON Category');
        $em->persist($category);

        $exercise = Exercise::create('JSON Exercise', $category);
        $em->persist($exercise);
        $em->flush();

        $client->request(
            method: 'GET',
            uri: '/api/v1/exercises/' . $exercise->getId(),
            server: ['HTTP_AUTHORIZATION' => "Bearer $token"]
        );

        $this->assertResponseHeaderSame('content-type', 'application/json');

        $content = $client->getResponse()->getContent();
        $this->assertJson($content);
    }

    /**
     * Test - nieprawidłowa metoda HTTP POST zwraca 405
     */
    public function testGetExerciseByIdWithPostMethodReturns405(): void
    {
        $client = static::createClient();

        $client->request(
            method: 'POST',
            uri: '/api/v1/exercises/123e4567-e89b-12d3-a456-426614174000'
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
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

