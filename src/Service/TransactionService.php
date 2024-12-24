<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Transaction;
use App\Entity\User;
use App\Enum\TransactionStatus;
use App\Exception\LimitExceededException;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

class TransactionService
{
    private EntityManagerInterface $entityManager;
    private Connection $connection;
    private UserService $userService;

    public function __construct(
        EntityManagerInterface $entityManager,
        Connection             $connection,
        UserService            $userService
    )
    {
        $this->entityManager = $entityManager;
        $this->connection = $connection;
        $this->userService = $userService;
    }

    public function parseJson(Request $request): array
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

    public function handleTransactionCreation(array $data): Transaction
    {
        $this->connection->beginTransaction();

        try {
            // TODO: внедрить Lock Component для конкурентного доступа
            $existingTransaction = $this->entityManager
                ->getRepository(Transaction::class)
                ->findOneBy(['uuid' => $data['uuid']]);

            if ($existingTransaction) {
                throw new \RuntimeException('Transaction with this UUID already exists');
            }

            $user = $this->userService->getUserById($data['user_id']);
            if (!$user) {
                throw new \RuntimeException('User not found');
            }

            $limit = $this->getUserTransactionLimit($user);
            $sums = $this->getTransactionSums($user, $data);

            $this->checkLimits($sums, $data, $limit);

            $transaction = $this->createNewTransaction($user, $data);

            $this->connection->commit();

            return $transaction;
        } catch (LimitExceededException | Throwable $e) {
            $this->connection->rollBack();

            throw $e;
        }
    }

    private function getUserTransactionLimit(User $user): array
    {
        $sql = 'SELECT daily_limit, monthly_limit FROM user_transactions_limit WHERE user_id = :userId FOR UPDATE';

        return $this->connection->executeQuery($sql, ['userId' => $user->getId()])->fetchAssociative();
    }

    private function getTransactionSums(User $user, array $data): array
    {
        // TODO: запрос неправльиный, для не текущего месяца считается неправильно, ошибка здесь в where DATE_FORMAT(CURDATE(), '%Y-%m')
        $sql = "
            SELECT
                SUM(IF(date = DATE_FORMAT(:date, '%Y-%m-%d'), amount, 0)) AS daily_sum,
                SUM(amount) AS monthly_sum
            FROM transaction
            WHERE user_id = :userId AND status = :status AND DATE_FORMAT(date, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
        ";
        $resultSet = $this->connection->executeQuery($sql, [
            'userId' => $user->getId(),
            'date' => $data['date'],
            'status' => TransactionStatus::fromString(TransactionStatus::SUCCESS_KEY)->toInt(),
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

    private function createNewTransaction(User $user, array $data): Transaction
    {
        $transaction = new Transaction();
        $transaction->setUser($user);
        $transaction->setAmount($data['amount']);
        $transaction->setStatus(TransactionStatus::fromString($data['status'])->toInt());
        $transaction->setUuid($data['uuid']);
        $transaction->setDate(new \DateTime($data['date']));
        $transaction->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        return $transaction;
    }
}
