<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getUserById(int $userId): ?User
    {
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if ($user === null) {
            throw new NotFoundHttpException('User not found');
        }

        return $user;
    }

    public function getUserLimits(User $user): ?array
    {
        $limits = $user->getUserTransactionsLimit();
        if (!$limits) {
            throw new NotFoundHttpException('User transactions limit not found');
        }

        return [
            'daily_limit' => $limits->getDailyLimit(),
            'monthly_limit' => $limits->getMonthlyLimit(),
            'created_at' => $limits->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at' => $limits->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
