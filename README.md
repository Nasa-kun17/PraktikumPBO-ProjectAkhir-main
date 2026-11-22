# Mbanking API

Tugas Akhir Semester Pemrograman Berorientasi Objek (PBO)
Program Studi Sistem Informasi, Universitas Tanjungpura

Mbanking API adalah REST API layanan mobile banking berbasis Laravel. Pengguna mendaftar, membuka rekening, top-up, transfer, membayar tagihan, dan melihat mutasi. Kode proyek ini menerapkan prinsip OOP dan SOLID.

## Anggota Kelompok

| Anggota | NIM |
|---------|-----|
| Muhamad Hizbullah Pohan | H1101251017 |
| Syarif Hizra Muhammad Zacky | H1101251031 |
| Felix Faustine | H1101251045 |
| Ibnu Khaidir Rahman | H1101251073 |

| | |
|---|---|
| **Dosen Pengampu** | Syahru Rahmayuda, S.Kom., M.Kom |
| **Asisten Dosen** | 1. Dani Ali<br>2. Andreanus Eko Putra |

## Fitur

- Register dan login dengan token Laravel Sanctum.
- Buka rekening Tabungan (`savings`) atau Giro (`current`), nomor rekening 10 digit dibuat otomatis.
- Top-up, transfer antar rekening, dan bayar tagihan.
- Mutasi per rekening, termasuk transfer masuk.
- Setiap pengguna hanya bisa mengakses rekeningnya sendiri.
- Perubahan saldo memakai row lock di dalam `DB::transaction` agar aman dari request bersamaan.

Stack: PHP ^8.3, Laravel ^13.17, Sanctum ^4.0, SQLite (default), PHPUnit.

## Penerapan OOP

| Konsep | Penerapan | Lokasi |
|--------|-----------|--------|
| Enkapsulasi | `balance` tidak masuk `$fillable`; saldo hanya berubah lewat `deposit()` dan `withdraw()` | `app/Models/Account.php` |
| Abstraksi | `AccountRules` mendefinisikan saldo minimum dan batas transfer | `app/Domain/AccountRules.php` |
| Inheritance | `SavingsRules` dan `CurrentRules` mewarisi `AccountRules` | `app/Domain/` |
| Polimorfisme | `Account::rules()` dan handler transaksi berbagi satu interface | `app/Enums/AccountType.php`, `app/Contracts/TransactionHandler.php` |
| Interface dan DI | Repository dan handler di-bind lewat service provider | `app/Contracts/`, `app/Providers/AppServiceProvider.php` |
| Open-Closed | Jenis transaksi baru cukup menambah satu handler dan satu baris binding | `app/Services/Transactions/` |

Alur request: `Route → Controller → Service → Handler → Repository → Model`

## Instalasi

Prasyarat: PHP 8.3+ dan Composer.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite    # Windows: buat file kosong ini secara manual
php artisan migrate
php artisan serve
```

Base URL: `http://localhost:8000/api/v1`

## Endpoint

Semua endpoint selain `register` dan `login` memakai header `Authorization: Bearer <token>`.

| Method | Endpoint | Keterangan |
|--------|----------|------------|
| POST | `/register` | Daftar, mengembalikan token |
| POST | `/login` | Login, mengembalikan token |
| GET | `/accounts` | Daftar rekening milik pengguna |
| POST | `/accounts` | Buka rekening, body `{"type":"savings"}` atau `"current"` |
| GET | `/accounts/{id}` | Detail rekening |
| GET | `/accounts/{id}/transactions` | Mutasi rekening |
| POST | `/accounts/{id}/transactions` | Buat transaksi `topup`, `transfer`, atau `bill` |

Body transaksi:

```json
{ "type": "topup", "amount": 500000 }
{ "type": "transfer", "amount": 100000, "destination_account_number": "1234567890", "description": "Bayar kos" }
{ "type": "bill", "amount": 50000, "bill_code": "PLN123" }
```

`amount` berupa integer 1 sampai 1.000.000.000. `destination_account_number` wajib untuk transfer (10 karakter). `bill_code` wajib untuk tagihan.

**Aturan bisnis**

| Jenis rekening | Saldo minimum | Batas transfer |
|----------------|---------------|----------------|
| Tabungan | Rp 50.000 | Rp 10.000.000 |
| Giro | Rp 0 | Rp 50.000.000 |

Transfer keluar dan bayar tagihan ditolak jika sisa saldo di bawah saldo minimum. Pelanggaran aturan bisnis mengembalikan HTTP 422 dengan format `{"success": false, "message": "..."}`.

**Kode status:** 200 berhasil, 201 dibuat, 401 belum login atau kredensial salah, 403 rekening milik pengguna lain, 404 tidak ditemukan, 422 validasi atau aturan bisnis gagal.

## Pengujian dengan Postman

**Persiapan**

1. Jalankan `php artisan serve`.
2. Buat Environment `Mbanking` dengan variabel `base_url` (`http://localhost:8000/api/v1`), `token_a`, `token_b`, `acc_a_id`, `acc_a_number`, `acc_b_id`, `acc_b_number`.
3. Tambahkan header `Accept: application/json` di setiap request, body raw JSON.
4. Untuk endpoint yang butuh login, isi Authorization dengan Bearer Token `{{token_a}}` atau `{{token_b}}`.

Script di tab Post-response untuk menyimpan nilai otomatis:

```js
pm.environment.set("token_a", pm.response.json().data.token);   // register / login
const d = pm.response.json().data;                              // buka rekening
pm.environment.set("acc_a_id", d.id);
pm.environment.set("acc_a_number", d.account_number);
```

**Skenario berhasil**

| # | Request | Body | Hasil |
|---|---------|------|-------|
| 1 | `POST /register` | `{"name":"User A","email":"a@mail.com","password":"12345678"}` | 201, ada `data.token` |
| 2 | `POST /login` | `{"email":"a@mail.com","password":"12345678"}` | 200 |
| 3 | `POST /accounts` | `{"type":"savings"}` | 201, `balance` 0 |
| 4 | `POST /accounts/{{acc_a_id}}/transactions` | `{"type":"topup","amount":500000}` | 201 |
| 5 | sama seperti no. 4 | `{"type":"bill","amount":50000,"bill_code":"PLN123"}` | 201 |
| 6 | Daftar user B, buka rekening `current` dengan `token_b` | `{"type":"current"}` | 201, simpan ID dan nomor rekening B |
| 7 | Transfer dari A dengan `token_a` | `{"type":"transfer","amount":100000,"destination_account_number":"{{acc_b_number}}"}` | 201 |
| 8 | `GET /accounts/{{acc_a_id}}` dan `GET /accounts/{{acc_b_id}}` | - | saldo A 350000, saldo B 100000 |
| 9 | `GET /accounts/{{acc_a_id}}/transactions` | - | 3 transaksi, terbaru di atas |


## Pengujian Otomatis

```bash
php artisan test
```

Test ada di `tests/Feature/MbankingTest.php`.

## Pembagian Tugas

| Anggota | Tanggung Jawab | Folder yang di-push ke repositori |
|---------|----------------|-----------------------------------|
| Muhamad Hizbullah Pohan | Diagram (class diagram) | File di root proyek dan sisa file lainnya |
| Syarif Hizra Muhammad Zacky | Laporan | `database`, `public`, `resources` |
| Felix Faustine | Program (kode aplikasi) | `app`, `bootstrap`, `config` |
| Ibnu Khaidir Rahman | Pengujian API (Postman) | `routes`, `storage`, `tests` |

## Lisensi

Dibuat untuk tugas perkuliahan. Laravel berlisensi [MIT](https://opensource.org/licenses/MIT).