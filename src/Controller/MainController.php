<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Entity\User;
use App\Entity\UserTransactionsLimit;
use App\Enum\TransactionStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
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

    #[Route('/transactions', name: 'create_transaction', methods: ['POST'])]
    public function createTransaction(
        Request                $request,
        EntityManagerInterface $entityManager,
    ): JsonResponse
    {
        try {
            $data = $this->parseJson($request->getContent());

            $user = $this->getUserById($entityManager, $data['user_id']);
            if ($user === null) {
                throw new \RuntimeException('User not found');
            }

            $userId = $data['user_id'];
            $amount = $data['amount'];

            $connection = $entityManager->getConnection();
            $connection->beginTransaction();

            try {
                $limit = $this->getUserTransactionLimit($connection, $userId);
                $sums = $this->getTransactionSums($connection, $userId);

                $this->checkLimits($sums, $amount, $limit);

                $this->createNewTransaction($entityManager, $user, $data, $amount);

                $connection->commit();

                return new JsonResponse(['message' => 'Transaction created successfully.'], 201);
            } catch (\Throwable $e) {
                $connection->rollBack();

                return new JsonResponse(['error' => $e->getMessage()], 400);
            }
        } catch (\Throwable $e) {
            return $this->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    private function parseJson(string $json): array
    {
        if (!json_validate($json)) {
            throw new \RuntimeException('Invalid JSON');
        }

        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON');
        }

        $requiredFields = ['uuid', 'user_id', 'amount', 'status', 'date'];
        $diff = array_diff($requiredFields, array_keys($data));
        if (count($diff) > 0) {
            throw new \RuntimeException('Not enough params');
        }

        return $data;
    }

    private function getUserById(EntityManagerInterface $entityManager, int $userId)
    {
        return $entityManager->getRepository(User::class)->find($userId);
    }

    private function getUserTransactionLimit($connection, int $userId): array
    {
        $sql = '
            SELECT daily_limit, monthly_limit
            FROM user_transactions_limit
            WHERE user_id = :userId
            FOR UPDATE
        ';

        $resultSet = $connection->executeQuery($sql, ['userId' => $userId]);
        return $resultSet->fetchAssociative();
    }

    private function getTransactionSums($connection, int $userId): array
    {
        $sql = '
            SELECT
                SUM(IF(DATE(created_at) = CURDATE(), amount, 0)) AS daily_sum,
                SUM(amount) AS monthly_sum
            FROM transaction
            WHERE user_id = :userId AND status = 1 AND MONTH(created_at) = MONTH(CURDATE());
        ';
        $resultSet = $connection->executeQuery($sql, ['userId' => $userId]);

        return $resultSet->fetchAssociative();
    }

    private function checkLimits(array $sums, float $amount, array $limit): void
    {
        $dailySum = $sums['daily_sum'] ?? 0;
        $monthlySum = $sums['monthly_sum'] ?? 0;

        if (bcadd($dailySum, $amount) > $limit['daily_limit']) {
            throw new \RuntimeException('Daily limit exceeded');
        }

        if (bcadd($monthlySum, $amount) > $limit['monthly_limit']) {
            throw new \RuntimeException('Monthly limit exceeded');
        }
    }

    private function createNewTransaction(EntityManagerInterface $entityManager, User $user, array $data): void
    {
        $status = TransactionStatus::fromString($data['status'])->toInt();

        $transaction = new Transaction();
        $transaction->setUser($user);
        $transaction->setAmount($data['amount']);
        $transaction->setStatus($status);
        $transaction->setDate(new \DateTime($data['date']));
        $transaction->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($transaction);
        $entityManager->flush();
    }
}
