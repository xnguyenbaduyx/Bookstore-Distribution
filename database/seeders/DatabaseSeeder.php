<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; // Thêm dòng này

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // TẮT kiểm tra khóa ngoại
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $this->call([
            BranchSeeder::class,
            UserSeeder::class,
            CategorySeeder::class,
            SupplierSeeder::class,
            BookSeeder::class, // BookSeeder phải chạy trước
            InventorySeeder::class,
            BranchInventorySeeder::class,
            OrderRequestSeeder::class, // OrderRequestSeeder cần Book để chọn sách
            ImportSeeder::class,
            DistributionSeeder::class,
        ]);

        // BẬT lại kiểm tra khóa ngoại
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}