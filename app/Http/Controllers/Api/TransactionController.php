<?php

namespace App\Http\Controllers\Api;

use App\Contracts\TransactionRepositoryInterface;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Account;
use App\Services\TransactionService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(
        private TransactionService $service,
        private TransactionRepositoryInterface $transactions,
    ) {}

    public function store(StoreTransactionRequest $r, Account $account)
    {
        abort_unless($account->user_id === $r->user()->id, 403);

        $trx = $this->service->execute(
            TransactionType::from($r->validated('type')),
            $account,
            $r->validated(),
        );

        return (new TransactionResource($trx))->response()->setStatusCode(201);
    }

    public function index(Request $r, Account $account)
    {
        abort_unless($account->user_id === $r->user()->id, 403);

        return TransactionResource::collection($this->transactions->historyFor($account->id));
    }
}
