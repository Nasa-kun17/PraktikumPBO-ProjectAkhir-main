<?php

namespace App\Services;

use App\Contracts\AccountRepositoryInterface;
use App\Enums\AccountType;
use App\Models\Account;

class AccountService
{
    public function __construct(private AccountRepositoryInterface $accounts) {}

    public function open(int $userId, AccountType $type): Account
    {
        do {
            $number = (string) random_int(1_000_000_000, 9_999_999_999);
        } while ($this->accounts->findByNumber($number));

        return $this->accounts->create([
            'user_id' => $userId,
            'account_number' => $number,
            'type' => $type,
        ]);
    }
}
