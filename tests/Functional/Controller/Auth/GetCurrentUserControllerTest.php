<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Auth;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class GetCurrentUserControllerTest extends WebTestCase
{
    public function testGetCurrentUserWithoutToken(): void
    {
        $client = static::createClient();
        
        $client->request(
            method: 'GET',
            uri: '/api/v1/auth/me',
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetCurrentUserWithInvalidToken(): void
    {
        $client = static::createClient();
        
        $client->request(
            method: 'GET',
            uri: '/api/v1/auth/me',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer invalid_token_abc123',
            ],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetCurrentUserSuccess(): void
    {
        $client = static::createClient();
        
        // Arrange: Create user and get valid JWT token
        $email = 'testuser' . time() . '@example.com';
        $password = 'SecurePass123';
        
        $token = $this->registerAndGetToken($client, $email, $password);

        // Act: Request current user profile
        $client->request(
            method: 'GET',
            uri: '/api/v1/auth/me',
            server: [
                'HTTP_AUTHORIZATION' => "Bearer $token",
            ],
        );

        // Assert: Response structure and data
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $responseData = json_decode($content, true);
        $this->assertIsArray($responseData);

        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayHasKey('email', $responseData);
        $this->assertArrayHasKey('createdAt', $responseData);

        $this->assertSame($email, $responseData['email']);

        // Verify UUID4 format (36 characters with hyphens)
        $this->assertIsString($responseData['id']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $responseData['id']);

        // Verify ISO 8601 datetime format
        $this->assertIsString($responseData['createdAt']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $responseData['createdAt']);

        // Assert: Password is NOT in response
        $this->assertArrayNotHasKey('password', $responseData);
        $this->assertArrayNotHasKey('passwordHash', $responseData);
    }

    public function testGetCurrentUserReturnsCorrectUser(): void
    {
        $client = static::createClient();
        
        // Arrange: Create two users
        $user1Email = 'user1' . time() . '@example.com';
        $user2Email = 'user2' . time() . '@example.com';
        $password = 'SecurePass123';
        
        $token1 = $this->registerAndGetToken($client, $user1Email, $password);
        $token2 = $this->registerAndGetToken($client, $user2Email, $password);

        // Act & Assert: User 1 sees only their profile
        $client->request(
            method: 'GET',
            uri: '/api/v1/auth/me',
            server: [
                'HTTP_AUTHORIZATION' => "Bearer $token1",
            ],
        );

        $this->assertResponseIsSuccessful();
        $content1 = $client->getResponse()->getContent();
        $this->assertIsString($content1);
        $response1 = json_decode($content1, true);
        $this->assertIsArray($response1);
        $this->assertArrayHasKey('email', $response1);
        $this->assertSame($user1Email, $response1['email']);

        // Act & Assert: User 2 sees only their profile
        $client->request(
            method: 'GET',
            uri: '/api/v1/auth/me',
            server: [
                'HTTP_AUTHORIZATION' => "Bearer $token2",
            ],
        );

        $this->assertResponseIsSuccessful();
        $content2 = $client->getResponse()->getContent();
        $this->assertIsString($content2);
        $response2 = json_decode($content2, true);
        $this->assertIsArray($response2);
        $this->assertArrayHasKey('email', $response2);
        $this->assertSame($user2Email, $response2['email']);

        // Users have different IDs
        $this->assertArrayHasKey('id', $response1);
        $this->assertArrayHasKey('id', $response2);
        $this->assertNotSame($response1['id'], $response2['id']);
    }

    public function testGetCurrentUserResponseDoesNotContainSensitiveData(): void
    {
        $client = static::createClient();
        
        $email = 'sensitive' . time() . '@example.com';
        $password = 'SecurePass123';
        
        $token = $this->registerAndGetToken($client, $email, $password);

        $client->request(
            method: 'GET',
            uri: '/api/v1/auth/me',
            server: [
                'HTTP_AUTHORIZATION' => "Bearer $token",
            ],
        );

        $this->assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        $this->assertIsString($content);
        $responseData = json_decode($content, true);
        $this->assertIsArray($responseData);

        // Ensure no sensitive data is exposed
        $this->assertArrayNotHasKey('password', $responseData);
        $this->assertArrayNotHasKey('passwordHash', $responseData);
        $this->assertArrayNotHasKey('updatedAt', $responseData);
        $this->assertArrayNotHasKey('workoutSessions', $responseData);

        // Only public data should be present
        $this->assertCount(3, $responseData);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayHasKey('email', $responseData);
        $this->assertArrayHasKey('createdAt', $responseData);
    }

    /**
     * Helper method to register a user and get JWT token.
     */
    private function registerAndGetToken(KernelBrowser $client, string $email, string $password): string
    {
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

