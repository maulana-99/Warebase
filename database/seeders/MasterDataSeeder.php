<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Department;
use App\Models\Location;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect([
            'ATK' => 'Alat tulis kantor',
            'Kertas' => 'Kertas cetak dan fotokopi',
            'Tinta & Toner' => 'Tinta printer dan toner',
            'Kebersihan' => 'Perlengkapan kebersihan',
            'Elektronik' => 'Perlengkapan elektronik habis pakai',
        ])->map(fn ($description, $name) => Category::create(compact('name', 'description')));

        $locations = collect([
            ['code' => 'A-01', 'name' => 'Rak A Baris 1'],
            ['code' => 'A-02', 'name' => 'Rak A Baris 2'],
            ['code' => 'B-01', 'name' => 'Rak B Baris 1'],
            ['code' => 'B-02', 'name' => 'Rak B Baris 2'],
            ['code' => 'C-01', 'name' => 'Rak C (Barang Besar)'],
        ])->mapWithKeys(fn ($data) => [$data['code'] => Location::create($data)]);

        foreach ([
            ['name' => 'CV Sinar Jaya ATK', 'phone' => '0812-1111-2222', 'email' => 'sales@sinarjaya.test', 'address' => 'Jl. Merdeka No. 10, Bandung'],
            ['name' => 'PT Kertas Nusantara', 'phone' => '021-555-0101', 'email' => 'order@kertasnusantara.test', 'address' => 'Jl. Industri Raya 5, Bekasi'],
            ['name' => 'Toko Bersih Selalu', 'phone' => '0813-3333-4444', 'email' => null, 'address' => 'Jl. Pasar Baru 21, Bandung'],
        ] as $supplier) {
            Supplier::create($supplier);
        }

        foreach ([
            'Bagian Keuangan',
            'Bagian Akademik',
            'Fakultas Teknik',
            'Fakultas Ekonomi',
            'Laboratorium Komputer',
            'Perpustakaan',
        ] as $name) {
            Department::create(['name' => $name]);
        }

        // Stock starts at 0; it is filled through goods-in transactions, never set directly.
        foreach ([
            ['ATK', 'A-01', 'ATK-001', 'Pulpen Hitam', 'pcs', 50],
            ['ATK', 'A-01', 'ATK-002', 'Spidol Whiteboard', 'pcs', 30],
            ['ATK', 'A-02', 'ATK-003', 'Map Plastik', 'pcs', 40],
            ['ATK', 'A-02', 'ATK-004', 'Stapler', 'pcs', 5],
            ['Kertas', 'B-01', 'KRT-001', 'Kertas HVS A4 70gsm', 'rim', 20],
            ['Kertas', 'B-01', 'KRT-002', 'Kertas HVS F4 70gsm', 'rim', 10],
            ['Tinta & Toner', 'B-02', 'TNT-001', 'Tinta Printer Hitam', 'botol', 10],
            ['Tinta & Toner', 'B-02', 'TNT-002', 'Toner Laser', 'pcs', 3],
            ['Kebersihan', 'C-01', 'KBR-001', 'Tisu Gulung', 'pak', 15],
            ['Kebersihan', 'C-01', 'KBR-002', 'Cairan Pembersih Lantai', 'botol', 6],
            ['Elektronik', 'C-01', 'ELK-001', 'Baterai AA', 'pak', 10],
        ] as [$category, $location, $sku, $name, $unit, $minStock]) {
            Product::create([
                'category_id' => $categories[$category]->id,
                'location_id' => $locations[$location]->id,
                'sku' => $sku,
                'name' => $name,
                'unit' => $unit,
                'min_stock' => $minStock,
            ]);
        }
    }
}
