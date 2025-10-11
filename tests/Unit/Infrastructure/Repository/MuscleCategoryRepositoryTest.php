<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Repository;

use App\Domain\Repository\MuscleCategoryRepositoryInterface;
use App\Infrastructure\Repository\MuscleCategoryRepository;
use PHPUnit\Framework\TestCase;

/**
 * Note: Repository tests with Doctrine are better suited for integration tests
 * rather than unit tests due to the complexity of mocking Doctrine infrastructure.
 * These tests will be covered by functional tests of the endpoint.
 */
final class MuscleCategoryRepositoryTest extends TestCase
{
    public function testRepositoryImplementsInterface(): void
    {
        $reflection = new \ReflectionClass(MuscleCategoryRepository::class);
        
        $this->assertTrue(
            $reflection->implementsInterface(MuscleCategoryRepositoryInterface::class),
            'MuscleCategoryRepository must implement MuscleCategoryRepositoryInterface'
        );
    }

    public function testRepositoryHasFindAllMethod(): void
    {
        $reflection = new \ReflectionClass(MuscleCategoryRepository::class);
        
        $this->assertTrue(
            $reflection->hasMethod('findAll'),
            'MuscleCategoryRepository must have findAll method'
        );
        
        $method = $reflection->getMethod('findAll');
        $this->assertTrue(
            $method->isPublic(),
            'findAll method must be public'
        );
    }
}

