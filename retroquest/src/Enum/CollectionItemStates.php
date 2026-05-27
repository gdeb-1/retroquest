<?php

namespace App\Enum;

enum States: string
{
    case MINT = 'Mint';
    case GOOD = 'Good';
    case FAIR = 'Fair';
    case POOR = 'Poor';
}