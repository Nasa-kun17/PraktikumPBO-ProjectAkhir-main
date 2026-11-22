<?php

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MbankingTest extends TestCase
{
    use RefreshDatabase;

    private function makeAccount(User $user, AccountType $type = AccountType::Savings, int $balance = 0): Account
    {
        $account = Account::create([
            'user_id' => $user->id,
            'account_number' => (string) random_int(1_000_000_000, 9_999_999_999),
            'type' => $type,
        ]);
        // balance tidak mass-assignable, jadi diisi lewat method domain
        if ($balance > 0) {
            $account->deposit($balance);
        }

        return $account->fresh();
    }

    public function test_register_dan_login_mengembalikan_token(): void
    {
        $this->postJson('/api/v1/register', [
            'name' => 'Felix', 'email' => 'f@mail.com', 'password' => '12345678',
        ])->assertCreated()->assertJsonStructure(['data' => ['token']]);

        $this->assertStringStartsWith('$2y$', User::first()->password);

        $this->postJson('/api/v1/login', ['email' => 'f@mail.com', 'password' => '12345678'])
            ->assertOk()->assertJsonStructure(['data' => ['token']]);

        $this->postJson('/api/v1/login', ['email' => 'f@mail.com', 'password' => 'salah'])
            ->assertStatus(401);
    }

    public function test_buka_rekening_dan_topup(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $id = $this->postJson('/api/v1/accounts', ['type' => 'savings'])
            ->assertCreated()
            ->assertJsonPath('data.balance', 0)
            ->json('data.id');

        $this->postJson("/api/v1/accounts/$id/transactions", ['type' => 'topup', 'amount' => 500_000])
            ->assertCreated();

        $this->assertSame(500_000, Account::find($id)->balance);
    }

    public function test_transfer_berhasil_memindahkan_saldo(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $from = $this->makeAccount($a, AccountType::Savings, 500_000);
        $to = $this->makeAccount($b, AccountType::Current);

        Sanctum::actingAs($a);
        $this->postJson("/api/v1/accounts/{$from->id}/transactions", [
            'type' => 'transfer', 'amount' => 100_000,
            'destination_account_number' => $to->account_number,
        ])->assertCreated();

        $this->assertSame(400_000, $from->fresh()->balance);
        $this->assertSame(100_000, $to->fresh()->balance);
    }

    public function test_transfer_saldo_kurang_ditolak_dan_saldo_tidak_berubah(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $from = $this->makeAccount($a, AccountType::Savings, 100_000);
        $to = $this->makeAccount($b, AccountType::Current);

        Sanctum::actingAs($a);
        // sisa 40.000 < saldo minimum tabungan 50.000
        $this->postJson("/api/v1/accounts/{$from->id}/transactions", [
            'type' => 'transfer', 'amount' => 60_000,
            'destination_account_number' => $to->account_number,
        ])->assertStatus(422)->assertJsonPath('message', 'Saldo tidak mencukupi');

        $this->assertSame(100_000, $from->fresh()->balance);
        $this->assertSame(0, $to->fresh()->balance);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_transfer_ke_rekening_sendiri_dan_rekening_tidak_ada_ditolak(): void
    {
        $a = User::factory()->create();
        $from = $this->makeAccount($a, AccountType::Current, 100_000);

        Sanctum::actingAs($a);
        $this->postJson("/api/v1/accounts/{$from->id}/transactions", [
            'type' => 'transfer', 'amount' => 1_000,
            'destination_account_number' => $from->account_number,
        ])->assertStatus(422);

        $this->postJson("/api/v1/accounts/{$from->id}/transactions", [
            'type' => 'transfer', 'amount' => 1_000,
            'destination_account_number' => '0000000000',
        ])->assertStatus(422);
    }

    public function test_batas_transfer_mengikuti_jenis_rekening(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $savings = $this->makeAccount($a, AccountType::Savings, 900_000_000);
        $to = $this->makeAccount($b, AccountType::Current);

        Sanctum::actingAs($a);
        $this->postJson("/api/v1/accounts/{$savings->id}/transactions", [
            'type' => 'transfer', 'amount' => 20_000_000,
            'destination_account_number' => $to->account_number,
        ])->assertStatus(422)->assertJsonPath('message', 'Melebihi batas transfer');
    }

    public function test_bayar_tagihan_dan_mutasi(): void
    {
        $a = User::factory()->create();
        $acc = $this->makeAccount($a, AccountType::Current, 200_000);

        Sanctum::actingAs($a);
        $this->postJson("/api/v1/accounts/{$acc->id}/transactions", [
            'type' => 'bill', 'amount' => 50_000, 'bill_code' => 'PLN123',
        ])->assertCreated();

        $this->assertSame(150_000, $acc->fresh()->balance);

        $this->getJson("/api/v1/accounts/{$acc->id}/transactions")
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'bill');
    }

    public function test_validasi_input_dan_otorisasi(): void
    {
        $a = User::factory()->create();
        $other = User::factory()->create();
        $mine = $this->makeAccount($a);
        $theirs = $this->makeAccount($other);

        $this->getJson("/api/v1/accounts/{$mine->id}")->assertStatus(401);

        Sanctum::actingAs($a);
        $this->postJson("/api/v1/accounts", ['type' => 'xxx'])->assertStatus(422);
        $this->postJson("/api/v1/accounts/{$mine->id}/transactions", ['type' => 'topup', 'amount' => 0])->assertStatus(422);
        $this->postJson("/api/v1/accounts/{$mine->id}/transactions", ['type' => 'topup', 'amount' => -5])->assertStatus(422);

        $this->getJson("/api/v1/accounts/{$theirs->id}")->assertForbidden();
        $this->getJson("/api/v1/accounts/{$theirs->id}/transactions")->assertForbidden();
        $this->postJson("/api/v1/accounts/{$theirs->id}/transactions", ['type' => 'topup', 'amount' => 1000])->assertForbidden();
    }
}
