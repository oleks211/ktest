<?php

namespace App\Serializer;

use App\Entity\Transaction;
use App\Entity\User;
use App\Enum\TransactionStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class TransactionDenormalizer implements DenormalizerInterface
{

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === Transaction::class;
    }

    public function denormalize(mixed $data, string $class, ?string $format = null, array $context = []): mixed
    {
        if (isset($data['status'])) {
            try {
                // Преобразуем строковый статус в Enum
                $data['status'] = TransactionStatus::fromString($data['status'])->toInt();
            } catch (\InvalidArgumentException $e) {
                throw new InvalidArgumentException('Invalid status value');
            }
        }

        $transaction = new $class();

        // Получаем ID пользователя из данных
        if (isset($data['user_id'])) {
            // Ищем пользователя в базе данных по ID
            $user = $this->entityManager->getRepository(User::class)->find($data['user_id']);
            if ($user) {
                // Устанавливаем пользователя в транзакцию
                $transaction->setUser($user);
            } else {
                throw new \Exception("User not found.");
            }
        }

        // Создаем объект Transaction, заполняя его данными
        $transaction->setAmount($data['amount']);
        $transaction->setStatus($data['status']);
        $transaction->setDate(new \DateTime($data['date']));
        $transaction->setCreatedAt(new \DateTimeImmutable());

        return $transaction;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Transaction::class => true,
        ];
    }
}
