<?php

namespace App\Contracts;

use App\Models\Account;
use App\Models\Transaction;

interface TransactionHandler
{
    public function handle(Account $source, array $data): Transaction;
}
