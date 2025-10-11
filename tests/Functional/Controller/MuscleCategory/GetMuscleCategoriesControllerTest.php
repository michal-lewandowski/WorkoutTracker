<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\MuscleCategory;

use App\Domain\Entity\MuscleCategory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class GetMuscleCategoriesControllerTest extends WebTestCase
{
    /**
     * Test podstawowy - endpoint wymaga autoryzacji
     */
    public function testGetMuscleCategoriesWithoutTokenReturns401(): void
    {
        $client = static::createClient();
        
        $client->request(
            method: 'GET',
            uri: '/api/v1/muscle-categories',
        );
        
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
    
    /**
     * Test - endpoint zwraca 200 OK z validnym tokenem
     */
    public function testGetMuscleCategoriesReturns200(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);
        
        $client->request(
            method: 'GET',
            uri: '/api/v1/muscle-categories',
            server: [
                'HTTP_AUTHORIZATION' => "Bearer $token",
                'CONTENT_TYPE' => 'application/json',
            ]
        );
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }
    
    /**
     * Test - odpowiedź jest w formacie JSON
     */
    public function testGetMuscleCategoriesReturnsValidJson(): void
    {
        $client = static::createClient();

        $token = $this->getAuthToken($client);
        $muscleCategory = MuscleCategory::create('Test4Pl', 'Test4En');
        $em = self::getContainer()->get('doctrine')->getManager();
        $em->persist($muscleCategory);
        $em->flush();

        $client->request(
            method: 'GET',
            uri: '/api/v1/muscle-categories',
            server: ['HTTP_AUTHORIZATION' => "Bearer $token"]
        );
        
        $this->assertResponseHeaderSame('content-type', 'application/json');
        
        $content = $client->getResponse()->getContent();
        $this->assertJson($content);
    }
    
    /**
     * Test - każdy element zawiera wszystkie wymagane pola
     */
    public function testGetMuscleCategoriesReturnsAllRequiredFields(): void
    {
        $client = static::createClient();

        $muscleCategory = MuscleCategory::create('Test3Pl', 'Test3En');
        $em = self::getContainer()->get('doctrine')->getManager();
        $em->persist($muscleCategory);
        $em->flush();

        $token = $this->getAuthToken($client);
        
        $client->request(
            method: 'GET',
            uri: '/api/v1/muscle-categories',
            server: ['HTTP_AUTHORIZATION' => "Bearer $token"]
        );
        
        $data = json_decode($client->getResponse()->getContent(), true);
        
        // Sprawdź czy mamy jakieś kategorie
        $this->assertNotEmpty($data, 'Response should contain muscle categories');
        
        // Sprawdź każdą kategorię
        foreach ($data as $category) {
            $this->assertArrayHasKey('id', $category);
            $this->assertArrayHasKey('namePl', $category);
            $this->assertArrayHasKey('nameEn', $category);
            $this->assertArrayHasKey('createdAt', $category);
            
            // Sprawdź typy danych
            $this->assertIsString($category['id']);
            $this->assertIsString($category['namePl']);
            $this->assertIsString($category['nameEn']);
            $this->assertIsString($category['createdAt']);
            
            // Sprawdź czy ID jest ULID (26 znaków)
            $this->assertEquals(26, strlen($category['id']));
            
            // Sprawdź czy nazwy nie są puste
            $this->assertNotEmpty($category['namePl']);
            $this->assertNotEmpty($category['nameEn']);
            
            // Sprawdź format daty ISO 8601
            $this->assertMatchesRegularExpression(
                '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
                $category['createdAt'],
                'createdAt should be in ISO 8601 format'
            );
        }
    }

    /**
     * Test - nieprawidłowa metoda HTTP POST zwraca 405
     */
    public function testGetMuscleCategoriesWithPostMethodReturns405(): void
    {
        $client = static::createClient();
        
        $client->request(
            method: 'POST',
            uri: '/api/v1/muscle-categories'
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }
    
    /**
     * Test - metoda PUT zwraca 405
     */
    public function testGetMuscleCategoriesWithPutMethodReturns405(): void
    {
        $client = static::createClient();
        
        $client->request(
            method: 'PUT',
            uri: '/api/v1/muscle-categories'
        );
        
        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }
    
    /**
     * Test - metoda DELETE zwraca 405
     */
    public function testGetMuscleCategoriesWithDeleteMethodReturns405(): void
    {
        $client = static::createClient();
        
        $client->request(
            method: 'DELETE',
            uri: '/api/v1/muscle-categories'
        );
        
        $this->assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }
    
    /**
     * Test - wszystkie ID są unikalne
     */
    public function testGetMuscleCategoriesReturnsUniqueIds(): void
    {
        $client = static::createClient();
        $token = $this->getAuthToken($client);
        
        $client->request(
            method: 'GET',
            uri: '/api/v1/muscle-categories',
            server: ['HTTP_AUTHORIZATION' => "Bearer $token"]
        );
        
        $data = json_decode($client->getResponse()->getContent(), true);
        
        $ids = array_map(fn($category) => $category['id'], $data);
        $uniqueIds = array_unique($ids);
        
        $this->assertCount(
            count($ids),
            $uniqueIds,
            'All category IDs should be unique'
        );
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

