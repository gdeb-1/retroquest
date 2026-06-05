<?php

declare(strict_types=1);

namespace App\Message\Command\Exchange;

readonly class CancelExchangeCommand
{
    public function __construct(
        public int $exchangeId,
        public int $userId
    ) {}
}
