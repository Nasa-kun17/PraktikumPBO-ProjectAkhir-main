<?php

namespace App\Http\Controllers\Api;

use App\Contracts\AccountRepositoryInterface;
use App\Enums\AccountType;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\AccountService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function __construct(
        private AccountService $service,
        private AccountRepositoryInterface $accounts,
    ) {}

    public function index(Request $r)
    {
        return response()->json([
            'success' => true,
            'data' => $this->accounts->forUser($r->user()->id),
        ]);
    }

    public function store(Request $r)
    {
        $data = $r->validate(['type' => ['required', Rule::enum(AccountType::class)]]);
        $account = $this->service->open($r->user()->id, AccountType::from($data['type']));

        return response()->json([
            'success' => true,
            'message' => 'Rekening dibuka',
            'data' => $account,
        ], 201);
    }

    public function show(Request $r, Account $account)
    {
        abort_unless($account->user_id === $r->user()->id, 403);

        return response()->json(['success' => true, 'data' => $account]);
    }
}
