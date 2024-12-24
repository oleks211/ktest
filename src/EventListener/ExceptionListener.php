<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Psr\Log\LoggerInterface;

class ExceptionListener
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        // TODO: добавить корректную обработку эксепшенов
        $exception = $event->getThrowable();

        // TODO: добавтиь логирование
        // $this->logger->error($exception->getMessage(), ['exception' => $exception]);

        $statusCode = JsonResponse::HTTP_INTERNAL_SERVER_ERROR;
        $message = 'An unexpected error occurred';

        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $message = $exception->getMessage();
        }

        $response = new JsonResponse(
            ['error' => $message],
            $statusCode
        );

        $event->setResponse($response);
    }
}
