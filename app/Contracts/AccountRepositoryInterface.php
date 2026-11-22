<?php

namespace App\Contracts;

use App\Models\Account;
use Illuminate\Support\Collection;

interface AccountRepositoryInterface
{
    public function create(array $data): Account;

    public function forUser(int $userId): Collection;

    public function findByNumber(string $number): ?Account;

    /** Ambil satu rekening dengan row lock (harus dipanggil di dalam DB::transaction). */
    public function lock(int $id): Account;

    /** @return Collection<int, Account> keyed by id, terkunci urut id agar tidak deadlock */
    public function lockPair(int $idA, int $idB): Collection;
}
