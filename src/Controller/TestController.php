<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class TestController extends AbstractController
{
    #[Route('/api/health')]
    public function apiTest()
    {
        return new JsonResponse(['status' => 'ok']);
    }

    #[Route('/api/test')]
    public function test()
    {
        return new JsonResponse([
            'message' => 'Połączenie z Symfony API działa!',
            'timestamp' => date('Y-m-d H:i:s'),
            'backend' => 'Symfony',
            'version' => '6.4'
        ]);
    }
}
