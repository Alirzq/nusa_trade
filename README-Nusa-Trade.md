# Nusa Trade: Backend API (Laravel + Laragon)

Backend REST API untuk marketplace **hasil pertanian** Nusantara: petani dan kelompok tani (seller) menjual hasil panen, bibit, dan sarana tani langsung ke pembeli. Fitur:

- Autentikasi token (Laravel Sanctum) dengan **hanya dua jenis akun: `admin` dan `seller`**. Pembeli **tidak punya akun** (checkout tanpa login, memakai token pesanan)
- Katalog produk publik: pencarian, filter kategori/daerah/organik/harga, urutan, dan pagination
- Data khas pertanian: satuan jual (kg, ton, ikat, karung, dan lainnya), minimal pembelian, tanggal panen, penanda organik, serta profil seller (nama kelompok tani dan daerahnya)
- Seller mengelola produk sendiri dan melihat pesanan yang berisi produknya
- Admin mengelola akun, kategori, memantau/menghapus produk, dan memantau semua pesanan
- Pembeli (tanpa akun) membuat pesanan (stok dipotong otomatis dan aman dari pembelian ganda), membayar (simulasi), membatalkan (stok kembali), dan menyelesaikan pesanan
- Seeder berisi akun demo, 7 kategori hasil tani, dan 12 produk dari berbagai daerah (cabai, bawang merah, beras, kopi Gayo, lada Muntok, benih, kompos, dan lainnya)

Kode ini kompatibel dengan Laravel 10 (PHP 8.1) sampai versi yang lebih baru.

---

## Langkah pemasangan (setelah `composer create-project` selesai)

Semua perintah dijalankan di Terminal Laragon, satu baris per perintah.

### 1. Buat database

Klik tombol **Database** di Laragon (HeidiSQL), buat database baru bernama `nusa_trade`.
Atau lewat terminal:

```bash
mysql -u root -e "CREATE DATABASE nusa_trade"
```

### 2. Atur `.env`

Buka `C:\laragon\www\nusa-trade\.env`, ubah bagian ini:

```
APP_NAME="Nusa Trade"
APP_URL=http://nusa-trade.test

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nusa_trade
DB_USERNAME=root
DB_PASSWORD=
```

(MySQL bawaan Laragon memakai user `root` tanpa password.)

### 3. Salin file dari zip

Ekstrak `nusa-trade-backend.zip` ke `C:\laragon\www\nusa-trade` dan pilih **Replace/Timpa** jika ditanya.
File bawaan Laravel yang ditimpa: `app/Models/User.php`, `routes/api.php`, `database/seeders/DatabaseSeeder.php`.
File migrasi `..._000001`, `..._000003`, `..._000004`, dan `..._000005` juga berubah (tabel `users`, `products`, `orders`, `order_items`), jadi database lama harus dibuat ulang (langkah 5).

**Jangan** menjalankan `php artisan install:api`. Perintah itu hanya ada di Laravel 11 ke atas;
di Laravel 10, Sanctum dan `routes/api.php` sudah terpasang.

### 4. Daftarkan middleware JSON (1 baris)

Buka `app/Http/Kernel.php`, cari bagian `'api' => [` dan tambahkan baris `ForceJsonResponse` **di paling atas** grup itu:

```php
'api' => [
    \App\Http\Middleware\ForceJsonResponse::class,   // <-- tambahkan baris ini
    // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    \Illuminate\Routing\Middleware\ThrottleRequests::class.':api',
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
],
```

Ini membuat error validasi, login, dan 404 di `/api/*` selalu berupa JSON.

(Jika nanti memakai Laravel 11 ke atas, tidak ada `Kernel.php`; daftarkan di `bootstrap/app.php`:
`$middleware->api(prepend: [\App\Http\Middleware\ForceJsonResponse::class]);` di dalam `withMiddleware`.)

### 5. Migrasi dan seeder

Karena struktur tabel `users`, `products`, `orders`, dan `order_items` berubah, gunakan `migrate:fresh` (menghapus semua tabel dan data lama, lalu membuat ulang beserta data demo):

```bash
cd C:\laragon\www\nusa-trade
php artisan migrate:fresh --seed
```

### 6. Jalankan

- Lewat virtual host Laragon: klik kanan ikon Laragon > **Apache** (atau Nginx) > **Reload**, lalu buka
  `http://nusa-trade.test/api/products`.
  Jika alamat `.test` belum terbaca, jalankan Laragon sebagai Administrator sekali.
- Atau lewat server bawaan Laravel: `php artisan serve`, lalu buka `http://127.0.0.1:8000/api/products`.

Jika `/api/products` menampilkan JSON berisi daftar produk, backend sudah berjalan.

Akun demo (password `password123`):

| Peran | Email |
|---|---|
| Admin | admin@nusatrade.test |
| Seller (Kelompok Tani Subur Makmur) | seller@nusatrade.test |

Hanya ada dua peran akun. Pendaftaran publik (`/auth/register`) **hanya membuat seller**; mengirim `role: admin` atau `role: buyer` ditolak (422). Akun admin dibuat lewat seeder atau oleh admin lain.

---

## Daftar endpoint

Awali semua URL dengan `/api`. Endpoint bertanda Bearer butuh header `Authorization: Bearer <token>`.

| Method | URL | Akses | Keterangan |
|---|---|---|---|
| POST | /auth/register | publik | name, email, phone, farm_name, region, password, password_confirmation. Selalu membuat **seller** |
| POST | /auth/login | publik | email, password. Mengembalikan token |
| POST | /auth/logout | Bearer | Hapus token saat ini |
| GET | /auth/me | Bearer | Data pengguna login |
| GET | /categories | publik | Daftar kategori |
| GET | /units | publik | Daftar satuan jual yang diizinkan (untuk dropdown) |
| GET | /products | publik | Query: `q`, `category_id`, `region`, `organic` (`1`/`0`), `min_price`, `max_price`, `sort` (`newest`/`price_asc`/`price_desc`), `per_page`, `page` |
| GET | /products/{id} | publik | Detail produk |
| POST | /orders | publik | `buyer_name`, `buyer_phone`, `shipping_address`, `items[]` (`product_id`, `quantity`). Mengembalikan `access_token` **sekali saja** |
| GET | /orders/{code} | token pesanan | Detail pesanan |
| POST | /orders/{code}/pay | token pesanan | Simulasi bayar: `pending` menjadi `paid` |
| POST | /orders/{code}/cancel | token pesanan | Batalkan pesanan `pending`, stok kembali |
| POST | /orders/{code}/complete | token pesanan | `shipped` menjadi `completed` |
| GET | /seller/products | seller | Produk milik sendiri |
| POST | /seller/products | seller | Wajib: category_id, name, price (per satuan), stock. Opsional: unit (default `kg`), min_order (default 1), harvest_date, is_organic, description, origin_region, image_url |
| PUT/PATCH | /seller/products/{id} | seller | Ubah produk sendiri |
| DELETE | /seller/products/{id} | seller | Hapus produk sendiri (soft delete) |
| GET | /seller/orders | seller | Pesanan yang berisi produk seller |
| PATCH | /seller/orders/{id}/ship | seller | `paid` menjadi `shipped` |
| GET | /admin/users | admin | Daftar akun. Query: `role` (`admin`/`seller`), `q` |
| POST | /admin/users | admin | Buat akun: name, email, phone, farm_name, region, password, `role` (`admin`/`seller`) |
| POST | /admin/categories | admin | `name` |
| PUT/PATCH | /admin/categories/{id} | admin | Ubah nama kategori |
| DELETE | /admin/categories/{id} | admin | Hapus kategori (ditolak jika masih dipakai produk) |
| GET | /admin/products | admin | Semua produk. Query: `q`, `seller_id`, `is_active` |
| DELETE | /admin/products/{id} | admin | Hapus produk (soft delete) |
| GET | /admin/orders | admin | Semua pesanan. Query: `status` |
| GET | /admin/orders/{id} | admin | Detail pesanan |

**Token pesanan (pembeli tanpa akun).** Respons `POST /orders` berisi `access_token`. Kirim lewat header `X-Order-Token: <token>` pada endpoint `/orders/{code}...`. Token hilang atau salah menghasilkan 404 (sama dengan kode yang tidak ada). Di database hanya hash token yang disimpan, jadi token tidak bisa dilihat lagi setelah respons pertama.

Alur status pesanan: `pending` → `paid` → `shipped` → `completed` (atau `pending` → `cancelled`).

## Aturan khusus hasil pertanian

- **Harga** adalah harga per satuan (`unit`), dalam Rupiah bulat. Contoh: `price: 55000`, `unit: "kg"` berarti Rp55.000 per kg.
- **Satuan** yang diizinkan: `kg`, `ton`, `kuintal`, `ikat`, `buah`, `butir`, `liter`, `karung`, `pak` (lihat `GET /api/units`). Stok dan jumlah pesanan dihitung dalam satuan itu dan berupa bilangan bulat.
- **Minimal pembelian** (`min_order`): pesanan dengan jumlah di bawahnya ditolak (422), misalnya jagung pipil minimal 20 kg.
- **Tanggal panen** (`harvest_date`, format `YYYY-MM-DD`) tidak boleh di masa depan. Boleh dikosongkan, misalnya untuk benih dan pupuk.
- Item pesanan menyimpan salinan satuan, nama, dan harga saat dibeli.

## Uji di Postman

1. Di Postman: **Import** → pilih `NusaTrade.postman_collection.json`.
2. Buka tab **Variables** koleksi. Isi `base_url`: `http://nusa-trade.test/api` (Laragon) atau `http://127.0.0.1:8000/api` (`php artisan serve`).
3. Pastikan sudah `php artisan migrate:fresh --seed`.
4. Klik kanan koleksi → **Run collection** → **Run**. Semua request (54) berjalan berurutan; token, ID produk, dan kode pesanan tersimpan otomatis. Tab **Test Results** menunjukkan lulus/gagal per pengecekan.

Isi koleksi: login admin/seller, register (role `admin` dan `buyer` ditolak), akses lintas peran (seller ke endpoint admin dan sebaliknya harus 403, tanpa token 401), CRUD produk seller dan kepemilikan produk, pesanan tanpa akun (token, stok berkurang, bayar, kirim, selesai, batal dan stok kembali), serta fitur admin.

Catatan: setelah tema pertanian, produk punya `min_order`. Jika ada request pesanan di koleksi yang memesan jumlah di bawah `min_order` produknya, hasilnya 422. Dalam data demo, produk dengan ID 1 sampai 3 dan ID 8 memakai `min_order` 1.

Jalankan manual satu per satu juga bisa, tetapi urutannya harus dari atas karena request berikutnya memakai variabel dari request sebelumnya.

## Catatan desain

- Harga disimpan sebagai bilangan bulat Rupiah (tanpa desimal) agar tidak ada masalah pembulatan.
- Item pesanan menyimpan salinan nama dan harga saat dibeli, jadi riwayat tetap benar walau produk diubah atau dihapus.
- Pembayaran masih simulasi. Untuk produksi, ganti `pay` dengan integrasi payment gateway (misalnya Midtrans atau Xendit) dan konfirmasi lewat webhook.
- Pembeli tidak punya akun. Jika nanti ingin riwayat pesanan per pembeli, tambahkan lagi peran akun pembeli atau simpan token pesanan di sisi aplikasi pembeli.
- Satu pesanan boleh berisi produk dari beberapa seller, tetapi status pesanan hanya satu. Jika ingin status per seller, pindahkan kolom `status` ke tabel `order_items`.
- Jika frontend berjalan di domain atau port lain, CORS bawaan Laravel sudah mengizinkan `/api/*`. Untuk membatasi asalnya, ubah `config/cors.php`.
- Laravel 10 sudah tidak menerima update keamanan lagi. Untuk lomba di laptop sendiri tidak masalah; untuk dipakai publik, naikkan PHP ke 8.3 dan gunakan Laravel versi terbaru.
- Sebelum presentasi atau deploy, set `APP_DEBUG=false` di `.env`.
