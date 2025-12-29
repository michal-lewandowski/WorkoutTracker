<?php

declare(strict_types=1);

namespace App\Infrastructure\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[AsEventListener(event: KernelEvents::EXCEPTION, priority: 10)]
final readonly class ValidationExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof UnprocessableEntityHttpException) {
            return;
        }

        $previous = $exception->getPrevious();

        if (!$previous instanceof ValidationFailedException) {
            return;
        }

        $violations = $previous->getViolations();
        $errors = [];

        foreach ($violations as $violation) {
            $propertyPath = $violation->getPropertyPath();
            if (!isset($errors[$propertyPath])) {
                $errors[$propertyPath] = [];
            }
            $errors[$propertyPath][] = $violation->getMessage();
        }

        $response = new JsonResponse([
            'message' => 'Validation failed',
            'errors' => $errors,
        ], Response::HTTP_BAD_REQUEST);

        $event->setResponse($response);
    }
}
