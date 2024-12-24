<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\TransactionResponseDTO;
use App\Service\TransactionService;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    private UserService $userService;
    private TransactionService $transactionService;

    public function __construct(UserService $userService, TransactionService $transactionService)
    {
        $this->userService = $userService;
        $this->transactionService = $transactionService;
    }

    #[Route('/limits/{userId}', name: 'get_limits', methods: ['GET'], requirements: ['userId' => '\d+'])]
    public function getLimits(int $userId): JsonResponse
    {
        $user = $this->userService->getUserById($userId);
        $limits = $this->userService->getUserLimits($user);

        return $this->json($limits, JsonResponse::HTTP_OK);
    }

    #[Route('/transactions', name: 'create_transaction', methods: ['POST'])]
    public function createTransaction(Request $request): JsonResponse
    {
        $data = $this->transactionService->parseJson($request);

        $transaction = $this->transactionService->handleTransactionCreation($data);
        $transactionDto = new TransactionResponseDTO($transaction);

        return $this->json($transactionDto, JsonResponse::HTTP_CREATED);
    }
}
