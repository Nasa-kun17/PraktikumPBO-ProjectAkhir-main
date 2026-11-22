<?php

namespace App\Providers;

use App\Contracts\AccountRepositoryInterface;
use App\Contracts\TransactionRepositoryInterface;
use App\Enums\TransactionType;
use App\Repositories\EloquentAccountRepository;
use App\Repositories\EloquentTransactionRepository;
use App\Services\TransactionService;
use App\Services\Transactions\BillHandler;
use App\Services\Transactions\TopUpHandler;
use App\Services\Transactions\TransferHandler;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AccountRepositoryInterface::class, EloquentAccountRepository::class);
        $this->app->bind(TransactionRepositoryInterface::class, EloquentTransactionRepository::class);

        // OCP: jenis transaksi baru = satu class handler + satu baris di sini
        $this->app->bind(TransactionService::class, fn ($app) => new TransactionService([
            TransactionType::Transfer->value => $app->make(TransferHandler::class),
            TransactionType::TopUp->value    => $app->make(TopUpHandler::class),
            TransactionType::Bill->value     => $app->make(BillHandler::class),
        ]));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
