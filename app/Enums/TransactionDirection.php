<?php

namespace App\Enums;

enum TransactionDirection: string
{
    case In  = 'in';
    case Out = 'out';
}
