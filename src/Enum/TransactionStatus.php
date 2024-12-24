<?php

declare(strict_types=1);

namespace App\Enum;

enum TransactionStatus: int
{
    public const SUCCESS_KEY = 'success';
    public const FAILURE_KEY = 'failure';

    case FAILURE = 0;
    case SUCCESS = 1;

    public static function fromString(string $status): self
    {
        return match (strtolower($status)) {
            self::SUCCESS_KEY => self::SUCCESS,
            self::FAILURE_KEY => self::FAILURE,
            default => throw new \InvalidArgumentException("Invalid status: {$status}"),
        };
    }

    public function toInt(): int
    {
        return $this->value;
    }

    public function toString(): string
    {
        return match ($this) {
            self::SUCCESS => self::SUCCESS_KEY,
            self::FAILURE => self::FAILURE_KEY,
        };
    }
}
