<?php

namespace App\Enum;

enum CollectionItemStates: string
{
    case MINT = 'Mint';
    case GOOD = 'Good';
    case FAIR = 'Fair';
    case POOR = 'Poor';
}