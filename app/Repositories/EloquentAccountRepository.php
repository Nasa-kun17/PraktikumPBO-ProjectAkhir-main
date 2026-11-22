<?php

namespace App\Repositories;

use App\Contracts\AccountRepositoryInterface;
use App\Models\Account;
use Illuminate\Support\Collection;

class EloquentAccountRepository implements AccountRepositoryInterface
{
    public function create(array $data): Account
    {
        return Account::create($data);
    }

    public function forUser(int $userId): Collection
    {
        return Account::where('user_id', $userId)->get();
    }

    public function findByNumber(string $number): ?Account
    {
        return Account::where('account_number', $number)->first();
    }

    public function lock(int $id): Account
    {
        return Account::whereKey($id)->lockForUpdate()->firstOrFail();
    }

    public function lockPair(int $idA, int $idB): Collection
    {
        return Account::whereIn('id', [$idA, $idB])
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }
}
