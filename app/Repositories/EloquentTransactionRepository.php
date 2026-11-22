<?php

namespace App\Repositories;

use App\Contracts\TransactionRepositoryInterface;
use App\Models\Transaction;
use Illuminate\Support\Collection;

class EloquentTransactionRepository implements TransactionRepositoryInterface
{
    public function create(array $data): Transaction
    {
        return Transaction::create($data);
    }

    public function historyFor(int $accountId): Collection
    {
        return Transaction::where(function ($q) use ($accountId) {
                $q->where('account_id', $accountId)
                  ->orWhere('destination_account_id', $accountId);
            })
            ->latest('id')
            ->get();
    }
}
