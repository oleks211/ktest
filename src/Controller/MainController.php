<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Entity\User;
use App\Entity\UserTransactionsLimit;
use App\Enum\TransactionStatus;
use App\Exception\LimitExceededException;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    #[Route('/limits/{user}', name: 'get_limits', methods: ['GET'])]
    public function getLimits(?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'error' => 'User not found',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $limits = $user->getUserTransactionsLimit();
        if (!$limits) {
            return $this->json([
                'error' => 'Limits not found for this user',
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        return $this->json([
            'daily_limit' => $limits->getDailyLimit(),
            'monthly_limit' => $limits->getMonthlyLimit(),
            'created_at' => $limits->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at' => $limits->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ], JsonResponse::HTTP_OK);
    }

    #[Route('/transactions', name: 'create_transaction', methods: ['POST'])]
    public function createTransaction(
        Request                $request,
        EntityManagerInterface $entityManager,
    ): JsonResponse
    {
        try {
            $data = $this->parseJson($request);

            $existingTransaction = $entityManager->getRepository(Transaction::class)
                ->findOneBy(['uuid' => $data['uuid']]);

            if ($existingTransaction !== null) {
                return $this->json([
                    'error' => 'Transaction with this UUID already exists',
                ], JsonResponse::HTTP_CONFLICT);
            }

            $user = $this->getUserById($entityManager, $data['user_id']);
            if ($user === null) {
                return $this->json([
                    'error' => 'User not found',
                ], JsonResponse::HTTP_NOT_FOUND);
            }

            $connection = $entityManager->getConnection();
            $connection->beginTransaction();

            try {
                $limit = $this->getUserTransactionLimit($connection, $user);
                $sums = $this->getTransactionSums($connection, $user, $data);

                $this->checkLimits($sums, $data, $limit);

                $this->createNewTransaction($entityManager, $user, $data);

                $connection->commit();

                return new JsonResponse([
                    'message' => 'Transaction created successfully.'
                ], JsonResponse::HTTP_CREATED);
            } catch (LimitExceededException $e) {
                $connection->rollBack();

                return new JsonResponse([
                    'error' => $e->getMessage()
                ], JsonResponse::HTTP_FORBIDDEN);
            } catch (\Throwable $e) {
                $connection->rollBack();

                return new JsonResponse([
                    'error' => $e->getMessage()
                ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
            }

        } catch (\Throwable $e) {
            return $this->json([
                'error' => $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function parseJson(Request $request): array
    {
        $json = $request->getContent();
        if (!json_validate($json)) {
            throw new \RuntimeException('Invalid JSON');
        }

        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON');
        }

        // TODO: temporarily, it needs to be moved.
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

    private function getUserTransactionLimit(Connection $connection, User $user): array
    {
        $sql = '
            SELECT daily_limit, monthly_limit
            FROM user_transactions_limit
            WHERE user_id = :userId
            FOR UPDATE
        ';
        $resultSet = $connection->executeQuery($sql, ['userId' => $user->getId()]);

        return $resultSet->fetchAssociative();
    }

    private function getTransactionSums(Connection $connection, User $user, array $data): array
    {
        $sql = "
            SELECT
                SUM(IF(date = DATE_FORMAT(:date, '%Y-%m-%d'), amount, 0)) AS daily_sum,
                SUM(amount) AS monthly_sum
            FROM transaction
            WHERE user_id = :userId AND status = 1 AND DATE_FORMAT(date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
        ";
        $resultSet = $connection->executeQuery($sql, [
            'userId' => $user->getId(),
            'date' => $data['date'],
        ]);

        return $resultSet->fetchAssociative();
    }

    private function checkLimits(array $sums, array $data, array $limit): void
    {
        $dailySum = $sums['daily_sum'] ?? 0;
        $monthlySum = $sums['monthly_sum'] ?? 0;

        if (bcadd($dailySum, $data['amount']) > $limit['daily_limit']) {
            throw new LimitExceededException('Daily limit exceeded');
        }

        if (bcadd($monthlySum, $data['amount']) > $limit['monthly_limit']) {
            throw new LimitExceededException('Monthly limit exceeded');
        }
    }

    private function createNewTransaction(EntityManagerInterface $entityManager, User $user, array $data): void
    {
        $status = TransactionStatus::fromString($data['status'])->toInt();

        $transaction = new Transaction();
        $transaction->setUser($user);
        $transaction->setAmount($data['amount']);
        $transaction->setStatus($status);
        $transaction->setUuid($data['uuid']);
        $transaction->setDate(new \DateTime($data['date']));
        $transaction->setCreatedAt(new \DateTimeImmutable());

        $entityManager->persist($transaction);
        $entityManager->flush();
    }
}
