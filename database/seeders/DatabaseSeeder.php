<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Akun demo (password: password123). Hanya ada dua jenis akun: admin dan seller.
        User::firstOrCreate(
            ['email' => 'admin@nusatrade.test'],
            [
                'name' => 'Admin Nusa Trade',
                'phone' => '081200000000',
                'password' => Hash::make('password123'),
                'role' => User::ROLE_ADMIN,
            ]
        );

        $seller = User::firstOrCreate(
            ['email' => 'seller@nusatrade.test'],
            [
                'name' => 'Slamet Raharjo',
                'phone' => '081200000001',
                'farm_name' => 'Kelompok Tani Subur Makmur',
                'region' => 'Karanganyar, Jawa Tengah',
                'password' => Hash::make('password123'),
                'role' => User::ROLE_SELLER,
            ]
        );

        // Kategori hasil tani
        $categories = [];
        foreach ([
            'Padi & Biji-bijian', 'Sayuran', 'Buah-buahan', 'Rempah-rempah',
            'Kopi & Perkebunan', 'Bibit & Benih', 'Pupuk & Sarana Tani',
        ] as $name) {
            $categories[$name] = Category::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
        }

        // [nama, kategori, harga per satuan, stok, satuan, min. order, hari sejak panen, organik, asal daerah, deskripsi]
        $products = [
            ['Cabai Rawit Merah', 'Sayuran', 55000, 80, 'kg', 1, 2, false, 'Kediri, Jawa Timur', 'Cabai rawit merah segar, dipanen langsung dari kebun.'],
            ['Bawang Merah Brebes', 'Sayuran', 38000, 300, 'kg', 1, 7, false, 'Brebes, Jawa Tengah', 'Bawang merah umbi besar, kering panen.'],
            ['Beras Pandan Wangi Cianjur', 'Padi & Biji-bijian', 16000, 500, 'kg', 1, 14, false, 'Cianjur, Jawa Barat', 'Beras pulen beraroma pandan.'],
            ['Beras Merah Organik', 'Padi & Biji-bijian', 22000, 200, 'kg', 2, 14, true, 'Magelang, Jawa Tengah', 'Beras merah ditanam tanpa pestisida kimia.'],
            ['Jagung Pipil Kering', 'Padi & Biji-bijian', 6500, 1000, 'kg', 20, 10, false, 'Lamongan, Jawa Timur', 'Jagung pipil kering untuk pakan ternak dan olahan.'],
            ['Benih Padi Inpari 32 (kemasan 5 kg)', 'Bibit & Benih', 85000, 200, 'pak', 1, null, false, 'Sukamandi, Jawa Barat', 'Benih padi varietas unggul bersertifikat.'],
            ['Pupuk Kompos Organik 25 kg', 'Pupuk & Sarana Tani', 45000, 300, 'karung', 5, null, true, 'Karanganyar, Jawa Tengah', 'Kompos dari kotoran ternak dan sisa panen.'],
            ['Pisang Cavendish', 'Buah-buahan', 14000, 120, 'kg', 1, 1, false, 'Lampung Timur, Lampung', 'Pisang cavendish matang pohon.'],
            ['Kopi Gayo Arabika Green Bean', 'Kopi & Perkebunan', 95000, 150, 'kg', 5, 30, false, 'Aceh Tengah, Aceh', 'Biji kopi arabika Gayo belum sangrai.'],
            ['Kakao Fermentasi Kolaka', 'Kopi & Perkebunan', 48000, 100, 'kg', 10, 21, false, 'Kolaka, Sulawesi Tenggara', 'Biji kakao fermentasi, kering jemur.'],
            ['Lada Putih Muntok', 'Rempah-rempah', 120000, 60, 'kg', 1, 45, false, 'Bangka Barat, Kepulauan Bangka Belitung', 'Lada putih khas Muntok.'],
            ['Pala Biji Kering Maluku', 'Rempah-rempah', 130000, 40, 'kg', 1, 60, false, 'Banda Neira, Maluku', 'Biji pala kering pilihan.'],
        ];

        foreach ($products as [$name, $category, $price, $stock, $unit, $minOrder, $daysAgo, $organic, $region, $description]) {
            Product::firstOrCreate(
                ['seller_id' => $seller->id, 'name' => $name],
                [
                    'category_id' => $categories[$category]->id,
                    'description' => $description,
                    'price' => $price,
                    'stock' => $stock,
                    'unit' => $unit,
                    'min_order' => $minOrder,
                    'harvest_date' => $daysAgo === null ? null : now()->subDays($daysAgo)->toDateString(),
                    'is_organic' => $organic,
                    'origin_region' => $region,
                    'is_active' => true,
                ]
            );
        }
    }
}
