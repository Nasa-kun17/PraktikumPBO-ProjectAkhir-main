<?php

namespace App\Contracts;

use App\Models\Transaction;
use Illuminate\Support\Collection;

interface TransactionRepositoryInterface
{
    public function create(array $data): Transaction;

    public function historyFor(int $accountId): Collection;
}
