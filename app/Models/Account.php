<?php

namespace App\Models;

use App\Domain\AccountRules;
use App\Enums\AccountType;
use App\Exceptions\BusinessRuleException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    // 'balance' sengaja TIDAK masuk fillable → tidak bisa diisi massal
    protected $fillable = ['user_id', 'account_number', 'type'];

    // nilai awal di sisi PHP, supaya response setelah create() sudah memuat balance
    protected $attributes = ['balance' => 0];

    protected $casts = [
        'type' => AccountType::class,
        'balance' => 'integer',
    ];

    public function rules(): AccountRules
    {
        return $this->type->rules();
    }

    public function deposit(int $amount): void
    {
        if ($amount <= 0) {
            throw new BusinessRuleException('Nominal harus lebih dari 0');
        }
        $this->balance += $amount;
        $this->save();
    }

    public function withdraw(int $amount): void
    {
        if ($amount <= 0) {
            throw new BusinessRuleException('Nominal harus lebih dari 0');
        }
        if (!$this->rules()->canWithdraw($this->balance, $amount)) {
            throw new BusinessRuleException('Saldo tidak mencukupi');
        }
        $this->balance -= $amount;
        $this->save();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
