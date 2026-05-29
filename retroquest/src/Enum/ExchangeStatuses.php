<?php

namespace App\Enum;

enum ExchangeStatuses: string
{
    case PENDING = 'pending';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case FINISHED = 'finished'; //items swap have been done (confirmed by both)
}
