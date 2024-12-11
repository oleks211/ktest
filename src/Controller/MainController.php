<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Entity\User;
use App\Entity\UserTransactionsLimit;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MainController extends AbstractController
{

    #[Route('/transactions', name: 'create_transaction', methods: ['POST'])]
    public function createTransaction(
        Request                $request,
        SerializerInterface    $serializer,
        ValidatorInterface     $validator,
        EntityManagerInterface $entityManager,
        TransactionRepository  $transactionRepository
    ): JsonResponse
    {
        try {
            $data = $request->getContent();
            $transaction = $serializer->deserialize($data, Transaction::class, 'json');

            $errors = $validator->validate($transaction);
            if (count($errors) > 0) {
                return new JsonResponse(['errors' => (string)$errors], 400);
            }

            $successfulTransactions = $transactionRepository->findBy(['user' => $transaction->getUser(), 'status' => 'success']);
            $usedLimit = array_reduce($successfulTransactions, function ($carry, $item) {
                return $carry + (float)$item->getAmount();
            }, 0);

            $limit = 1000.00; // TODO:
            if (($usedLimit + (float)$transaction->getAmount()) > $limit) {
                return $this->json([
                    'error' => 'Transaction limit exceeded',
                    'limit' => $limit,
                    'used' => $usedLimit
                ], 400);
            }

            $entityManager->persist($transaction);
            $entityManager->flush();

            return $this->json([
                'message' => 'Transaction created successfully',
                'transaction_id' => $transaction->getId(),
            ], 201); //TODO: статус уточнить

        } catch (\Throwable $e) {
            return $this->json([
                'error' => $e->getMessage(),
            ], 400);
        }


    }

    #[Route('/limits/{userId}', name: 'get_limits', methods: ['GET'])]
    public function getLimits(int $userId, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $entityManager->getRepository(User::class)->find($userId);

        if (!$user) {
            return $this->json([
                'error' => 'User not found',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $limits = $entityManager->getRepository(UserTransactionsLimit::class)->findOneBy(['user' => $user]);

        if (!$limits) {
            return $this->json([
                'error' => 'Limits not found for this user',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        return $this->json([
            'daily_limit' => $limits->getDailyLimit(),
            'monthly_limit' => $limits->getMonthlyLimit(),
            'updated_at' => $limits->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ]);
    }
}
