<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Service\Auth;

use App\Application\Command\Auth\RegisterUserCommand;
use App\Application\Exception\EmailAlreadyExistsException;
use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use App\Infrastructure\Service\Auth\UserRegistrationService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserRegistrationServiceTest extends TestCase
{
    private UserRepositoryInterface $userRepository;
    private UserPasswordHasherInterface $passwordHasher;
    private LoggerInterface $logger;
    private UserRegistrationService $service;
    
    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->service = new UserRegistrationService(
            $this->userRepository,
            $this->passwordHasher,
            $this->logger
        );
    }
    
    public function testSuccessfulRegistration(): void
    {
        $command = new RegisterUserCommand(
            email: 'test@example.com',
            plainPassword: 'SecurePass123'
        );
        
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('test@example.com')
            ->willReturn(null);
        
        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn('$hashed$password$');
        
        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (User $user) {
                return $user->getEmail() === 'test@example.com'
                    && $user->getPasswordHash() === '$hashed$password$';
            }));
        
        $this->logger
            ->expects($this->exactly(2))
            ->method('info')
            ->with(
                $this->logicalOr(
                    $this->equalTo('User registration attempt'),
                    $this->equalTo('User registered successfully')
                )
            );
        
        $this->service->register($command);
    }
    
    public function testRegistrationFailsWhenEmailExists(): void
    {
        $command = new RegisterUserCommand(
            email: 'existing@example.com',
            plainPassword: 'SecurePass123'
        );
        
        $existingUser = User::create('existing@example.com');
        
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('existing@example.com')
            ->willReturn($existingUser);
        
        $this->passwordHasher
            ->expects($this->never())
            ->method('hashPassword');
        
        $this->userRepository
            ->expects($this->never())
            ->method('save');
        
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Registration failed - email already exists',
                ['email' => 'existing@example.com']
            );
        
        $this->expectException(EmailAlreadyExistsException::class);
        $this->expectExceptionMessage('Email "existing@example.com" is already registered');
        
        $this->service->register($command);
    }
    
    public function testRegistrationFailsOnDatabaseError(): void
    {
        $command = new RegisterUserCommand(
            email: 'test@example.com',
            plainPassword: 'SecurePass123'
        );
        
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->willReturn(null);
        
        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn('$hashed$password$');
        
        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->willThrowException(new \Exception('Database connection failed'));
        
        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Failed to save user to database',
                $this->arrayHasKey('email')
            );
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to create user account');
        
        $this->service->register($command);
    }
    
    public function testPasswordIsHashed(): void
    {
        $command = new RegisterUserCommand(
            email: 'test@example.com',
            plainPassword: 'PlainPassword123'
        );
        
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->willReturn(null);
        
        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->with(
                $this->callback(fn(User $user) => $user->getEmail() === 'test@example.com'),
                'PlainPassword123'
            )
            ->willReturn('$2y$13$hashed.password.here');
        
        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (User $user) {
                return $user->getPasswordHash() === '$2y$13$hashed.password.here';
            }));
        
        $this->service->register($command);
    }
    
    public function testEmailIsNormalizedToLowercase(): void
    {
        $command = new RegisterUserCommand(
            email: 'Test@EXAMPLE.COM',
            plainPassword: 'SecurePass123'
        );
        
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with('Test@EXAMPLE.COM')
            ->willReturn(null);
        
        $this->passwordHasher
            ->method('hashPassword')
            ->willReturn('$hashed$');
        
        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (User $user) {
                // User entity normalizes email to lowercase in constructor
                return $user->getEmail() === 'test@example.com';
            }));
        
        $this->service->register($command);
    }
}

