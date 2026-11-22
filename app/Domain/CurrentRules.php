<?php

namespace App\Domain;

/** INHERITANCE: aturan rekening giro. */
final class CurrentRules extends AccountRules
{
    public function minimumBalance(): int
    {
        return 0;
    }

    public function transferLimit(): int
    {
        return 50_000_000;
    }
}
