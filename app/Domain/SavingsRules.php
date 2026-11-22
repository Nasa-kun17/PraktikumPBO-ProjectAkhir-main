<?php

namespace App\Domain;

/** INHERITANCE: aturan rekening tabungan. */
final class SavingsRules extends AccountRules
{
    public function minimumBalance(): int
    {
        return 50_000;
    }

    public function transferLimit(): int
    {
        return 10_000_000;
    }
}
