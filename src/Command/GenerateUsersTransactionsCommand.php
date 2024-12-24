<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Entity\Transaction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:generate-users-transactions',
    description: 'Generate 100,000 users with 1-100 transactions each.',
)]
class GenerateUsersTransactionsCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $startTime = microtime(true);
        $output->writeln('Starting to generate users and transactions...');

        $batchSize = 500;
        $totalTransactions = 0;

        for ($i = 1; $i <= 100000; $i++) {
            $user = new User();
            $user->setName('User ' . $i);
            $user->setEmail('user' . $i . '@example.com');
            $user->setPhoneNumber($this->generatePhoneNumber());
            $user->setCreatedAt($this->generateCreatedAt());

            $this->entityManager->persist($user);

            $transactionCount = random_int(1, 100);
            $totalTransactions += $transactionCount;

            $transactions = [];
            for ($j = 1; $j <= $transactionCount; $j++) {
                $transaction = new Transaction();
                $transaction->setUser($user);
                $transaction->setAmount(random_int(100, 10000) / 100);
                $transaction->setStatus(random_int(0, 3));
                $transaction->setUuid($this->generateUuid());
                $transaction->setDate(new \DateTimeImmutable('now - ' . random_int(0, 365) . ' days'));
                $transaction->setCreatedAt(new \DateTimeImmutable());

                $transactions[] = $transaction;

                if (count($transactions) >= $batchSize) {
                    foreach ($transactions as $transaction) {
                        $this->entityManager->persist($transaction);
                    }
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                    $transactions = [];
                }
            }

            foreach ($transactions as $transaction) {
                $this->entityManager->persist($transaction);
            }

            if ($i % $batchSize === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }

            $elapsedTime = microtime(true) - $startTime;
            $output->writeln("Processed $i users with $totalTransactions transactions in " . round($elapsedTime, 2) . " seconds.");
        }

        $elapsedTime = microtime(true) - $startTime;
        $output->writeln("Generation completed successfully!");
        $output->writeln("Total users: 100,000");
        $output->writeln("Total transactions: $totalTransactions");
        $output->writeln("Execution time: " . round($elapsedTime, 2) . " seconds.");

        return Command::SUCCESS;
    }

    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    private function generatePhoneNumber(): string
    {
        return '8' . substr(str_shuffle('0123456789'), 0, 11);
    }

    private function generateCreatedAt(): \DateTimeImmutable
    {
        $randomDays = random_int(0, 365);
        return new \DateTimeImmutable("-$randomDays days");
    }
}
