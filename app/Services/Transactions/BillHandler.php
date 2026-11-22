<?php

namespace App\Services\Transactions;

use App\Contracts\AccountRepositoryInterface;
use App\Contracts\TransactionHandler;
use App\Contracts\TransactionRepositoryInterface;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class BillHandler implements TransactionHandler
{
    public function __construct(
        private AccountRepositoryInterface $accounts,
        private TransactionRepositoryInterface $transactions,
    ) {}

    public function handle(Account $source, array $data): Transaction
    {
        $amount = (int) $data['amount'];

        return DB::transaction(function () use ($source, $amount, $data) {
            $account = $this->accounts->lock($source->id);
            $account->withdraw($amount);

            return $this->transactions->create([
                'account_id' => $account->id,
                'type' => TransactionType::Bill,
                'amount' => $amount,
                'description' => 'Tagihan ' . ($data['bill_code'] ?? '-'),
            ]);
        });
    }
}
