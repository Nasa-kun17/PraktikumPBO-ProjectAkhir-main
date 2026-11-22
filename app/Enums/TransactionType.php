<?php

namespace App\Enums;

enum TransactionType: string
{
    case Transfer = 'transfer';
    case TopUp = 'topup';
    case Bill = 'bill';
}
