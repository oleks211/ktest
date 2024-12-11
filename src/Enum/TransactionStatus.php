<?php

namespace App\Enum;

enum TransactionStatus: int
{
    private const SUCCESS_KEY = 'success';
    private const ERROR_KEY = 'error';

    case ERROR = 0;
    case SUCCESS = 1;

    public static function fromString(string $status): self
    {
        return match (strtolower($status)) {
            self::SUCCESS_KEY => self::SUCCESS,
            self::ERROR_KEY => self::ERROR,
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
            self::ERROR => self::ERROR_KEY,
        };
    }
}
