<?php

namespace App\Http\Requests;

use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    /**
     * Kepemilikan rekening dicek di controller (abort_unless), jadi di sini cukup true.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(TransactionType::class)],
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'destination_account_number' => ['required_if:type,transfer', 'string', 'size:10'],
            'description' => ['nullable', 'string', 'max:100'],
            'bill_code' => ['required_if:type,bill', 'string', 'max:30'],
        ];
    }
}
