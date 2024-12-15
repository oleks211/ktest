<?php

namespace App\Controller;

use App\Exception\LimitExceededException;
use App\Service\TransactionService;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
        try {
            $user = $this->userService->getUserById($userId);
            $limits = $this->userService->getUserLimits($user);

            return $this->json($limits, JsonResponse::HTTP_OK);
        } catch (NotFoundHttpException $e) {
            return $this->json([
                'error' => $e->getMessage(),
            ], JsonResponse::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/transactions', name: 'create_transaction', methods: ['POST'])]
    public function createTransaction(Request $request): JsonResponse
    {
        try {
            $data = $this->transactionService->parseJson($request);

            return $this->transactionService->handleTransactionCreation($data);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
