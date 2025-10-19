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

namespace App\Infrastructure\EventListener;

use App\Application\Exception\WorkoutSessionAccessDeniedException;
use App\Application\Exception\WorkoutSessionNotFoundException;
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

