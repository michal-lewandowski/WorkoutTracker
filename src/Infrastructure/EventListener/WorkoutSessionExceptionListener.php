<?php

declare(strict_types=1);

namespace App\Infrastructure\EventListener;

use App\Domain\Exception\WorkoutSessionAccessDeniedException;
use App\Domain\Exception\WorkoutSessionNotFoundException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::EXCEPTION, priority: 10)]
final readonly class WorkoutSessionExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof WorkoutSessionNotFoundException) {
            $response = new JsonResponse(
                [
                    'message' => 'Workout session not found',
                    'code' => Response::HTTP_NOT_FOUND,
                ],
                Response::HTTP_NOT_FOUND
            );

            $event->setResponse($response);

            return;
        }

        if ($exception instanceof WorkoutSessionAccessDeniedException) {
            $response = new JsonResponse(
                [
                    'message' => 'Access denied',
                    'code' => Response::HTTP_FORBIDDEN,
                ],
                Response::HTTP_FORBIDDEN
            );

            $event->setResponse($response);
        }
    }
}
