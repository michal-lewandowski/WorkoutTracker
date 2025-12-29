<?php

declare(strict_types=1);

namespace App\Infrastructure\EventListener;

use App\Domain\Exception\ExerciseNotFoundException;
use App\Domain\Exception\WorkoutExerciseNotFoundException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::EXCEPTION, priority: 10)]
final readonly class WorkoutExerciseExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof WorkoutExerciseNotFoundException) {
            $response = new JsonResponse(
                [
                    'message' => 'Workout exercise not found',
                    'code' => Response::HTTP_NOT_FOUND,
                ],
                Response::HTTP_NOT_FOUND
            );

            $event->setResponse($response);

            return;
        }

        if ($exception instanceof ExerciseNotFoundException) {
            $response = new JsonResponse(
                [
                    'message' => 'Exercise not found',
                    'code' => Response::HTTP_NOT_FOUND,
                ],
                Response::HTTP_NOT_FOUND
            );

            $event->setResponse($response);
        }
    }
}
