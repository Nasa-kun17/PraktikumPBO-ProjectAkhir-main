<?php

namespace App\Domain;

/** ABSTRAKSI: aturan umum rekening, detailnya ditentukan class turunan. */
abstract class AccountRules
{
    abstract public function minimumBalance(): int;

    abstract public function transferLimit(): int;

    public function canWithdraw(int $balance, int $amount): bool
    {
        return $balance - $amount >= $this->minimumBalance();
    }
}
