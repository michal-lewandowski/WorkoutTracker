<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Auth;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class RegisterControllerTest extends WebTestCase
{
    public function testSuccessfulRegistration(): void
    {
        $client = static::createClient();
        
        $uniqueEmail = 'newuser' . time() . '@example.com';
        
        $client->request(
            method: 'POST',
            uri: '/api/v1/auth/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => $uniqueEmail,
                'password' => 'SecurePass123',
                'passwordConfirmation' => 'SecurePass123'
            ])
        );
        
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        
        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('token', $data);
        $this->assertSame($uniqueEmail, $data['user']['email']);
        $this->assertNotEmpty($data['token']);
        $this->assertArrayHasKey('id', $data['user']);
        $this->assertArrayHasKey('createdAt', $data['user']);
    }
    
    public function testRegistrationFailsWithDuplicateEmail(): void
    {
        $client = static::createClient();
        
        $email = 'duplicate' . time() . '@example.com';
        
        // First registration
        $client->request(
            method: 'POST',
            uri: '/api/v1/auth/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => $email,
                'password' => 'SecurePass123',
                'passwordConfirmation' => 'SecurePass123'
            ])
        );
        
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        
        // Second registration with same email
        $client->request(
            method: 'POST',
            uri: '/api/v1/auth/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => $email,
                'password' => 'SecurePass123',
                'passwordConfirmation' => 'SecurePass123'
            ])
        );
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('email', $data['errors']);
        $this->assertContains('Email is already registered', $data['errors']['email']);
    }
    
    public function testRegistrationFailsWithInvalidEmail(): void
    {
        $client = static::createClient();
        
        $client->request(
            method: 'POST',
            uri: '/api/v1/auth/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => 'invalid-email',
                'password' => 'SecurePass123',
                'passwordConfirmation' => 'SecurePass123'
            ])
        );
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('email', $data['errors']);
    }
    
    public function testRegistrationFailsWithWeakPassword(): void
    {
        $client = static::createClient();
        
        $client->request(
            method: 'POST',
            uri: '/api/v1/auth/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => 'test@example.com',
                'password' => 'weak',
                'passwordConfirmation' => 'weak'
            ])
        );
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('password', $data['errors']);
        $this->assertGreaterThan(0, count($data['errors']['password']));
    }
    
    public function testRegistrationFailsWithMismatchedPasswords(): void
    {
        $client = static::createClient();
        
        $client->request(
            method: 'POST',
            uri: '/api/v1/auth/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => 'test@example.com',
                'password' => 'SecurePass123',
                'passwordConfirmation' => 'DifferentPass456'
            ])
        );
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('passwordConfirmation', $data['errors']);
    }
    
    public function testRegistrationFailsWithMissingFields(): void
    {
        $client = static::createClient();
        
        $client->request(
            method: 'POST',
            uri: '/api/v1/auth/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => 'test@example.com'
                // Missing password and passwordConfirmation
            ])
        );
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('errors', $data);
    }
    
    public function testRegistrationFailsWithEmptyEmail(): void
    {
        $client = static::createClient();
        
        $client->request(
            method: 'POST',
            uri: '/api/v1/auth/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => '',
                'password' => 'SecurePass123',
                'passwordConfirmation' => 'SecurePass123'
            ])
        );
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('email', $data['errors']);
    }
    
    public function testRegistrationFailsWithTooLongEmail(): void
    {
        $client = static::createClient();
        
        $longEmail = str_repeat('a', 250) . '@example.com'; // > 255 chars
        
        $client->request(
            method: 'POST',
            uri: '/api/v1/auth/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => $longEmail,
                'password' => 'SecurePass123',
                'passwordConfirmation' => 'SecurePass123'
            ])
        );
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('email', $data['errors']);
    }
    
    public function testPasswordIsHashedInDatabase(): void
    {
        $client = static::createClient();
        $container = static::getContainer();
        
        $uniqueEmail = 'hashed' . time() . '@example.com';
        $plainPassword = 'SecurePass123';
        
        $client->request(
            method: 'POST',
            uri: '/api/v1/auth/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => $uniqueEmail,
                'password' => $plainPassword,
                'passwordConfirmation' => $plainPassword
            ])
        );
        
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        
        // Verify password is hashed in database
        $userRepository = $container->get('App\Domain\Repository\UserRepositoryInterface');
        $user = $userRepository->findByEmail($uniqueEmail);
        
        $this->assertNotNull($user);
        $this->assertNotEquals($plainPassword, $user->getPasswordHash());
        $this->assertStringStartsWith('$', $user->getPasswordHash()); // bcrypt/argon2 format
    }
    
    public function testEmailIsCaseInsensitive(): void
    {
        $client = static::createClient();
        
        $email = 'CaseSensitive' . time() . '@EXAMPLE.COM';
        
        // Register with mixed case
        $client->request(
            method: 'POST',
            uri: '/api/v1/auth/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => $email,
                'password' => 'SecurePass123',
                'passwordConfirmation' => 'SecurePass123'
            ])
        );
        
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        
        $data = json_decode($client->getResponse()->getContent(), true);
        
        // Email should be stored as lowercase
        $this->assertSame(strtolower($email), $data['user']['email']);
    }
}

