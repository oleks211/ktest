<?php

declare(strict_types=1);

namespace App\Serializer;

use App\Entity\User;
use App\Entity\UserTransactionsLimit;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class UserTransactionsLimitDenormalizer implements DenormalizerInterface
{

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === UserTransactionsLimit::class;
    }

    public function denormalize(mixed $data, string $class, ?string $format = null, array $context = []): mixed
    {
        $userTransactionsLimit = $context['object_to_populate'] ?? new $class();

        $userTransactionsLimit->setDailyLimit($data['daily_limit']);
        $userTransactionsLimit->setMonthlyLimit($data['monthly_limit']);
        $userTransactionsLimit->setUpdatedAt(new \DateTime());

        if ($userTransactionsLimit->getCreatedAt() !== null) {
            return $userTransactionsLimit;
        }

        // new userTransactionsLimit
        if (!isset($data['user_id'])) {
            throw new \RuntimeException('User not found');
        }

        $user = $this->entityManager->getRepository(User::class)->find($data['user_id']);
        if (!$user) {
            throw new \RuntimeException("User not found.");
        }

        $userTransactionsLimit->setUser($user);
        $userTransactionsLimit->setCreatedAt(new \DateTimeImmutable());

        return $userTransactionsLimit;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            UserTransactionsLimit::class => true,
        ];
    }
}
