<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\Transaction;

class TransactionResponseDTO
{
    public int $id;
    public string $uuid;
    public int $userId;
    public string $amount;
    public int $status;
    public string $date;
    public string $createdAt;

    public function __construct(Transaction $transaction)
    {
        $this->id = $transaction->getId();
        $this->uuid = $transaction->getUuid();
        $this->user = $transaction->getUser()->getId();
        $this->amount = $transaction->getAmount();
        $this->status = $transaction->getStatus();
        $this->date = $transaction->getDate()->format('Y-m-d');
        $this->createdAt = $transaction->getCreatedAt()->format('Y-m-d H:i:s');
    }
}
