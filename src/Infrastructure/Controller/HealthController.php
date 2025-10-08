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

namespace App\Infrastructure\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class HealthController extends AbstractController
{
    #[Route('/api/health')]
    public function health(): JsonResponse
    {
        return new JsonResponse([
            'status ' => 'ok',
        ]);
    }

    #[Route('/api/test')]
    public function test(): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Połączenie z Symfony API działa!',
            'timestamp' => date('Y-m-d H:i:s'),
            'backend' => 'Symfony',
            'version' => '6.4',
            'a' => 'b',
            'c' => 'd',
        ]);
    }
}
