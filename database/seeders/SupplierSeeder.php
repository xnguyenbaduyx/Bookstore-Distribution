<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Supplier::truncate(); // Không cần truncate ở đây nếu bạn đã tắt foreign key checks ở DatabaseSeeder

        Supplier::create([
            'code' => 'SUP001', // THÊM DÒNG NÀY: Mã nhà cung cấp
            'name' => 'Nhà xuất bản Kim Đồng',
            'contact_person' => 'Nguyễn Thị A',
            'phone' => '0901112233',
            'email' => 'kimdong@example.com',
            'address' => '100 Phố Kim Đồng, Hà Nội',
            'is_active' => true,
        ]);

        Supplier::create([
            'code' => 'SUP002', // THÊM DÒNG NÀY: Mã nhà cung cấp
            'name' => 'Nhà xuất bản Trẻ',
            'contact_person' => 'Trần Văn B',
            'phone' => '0904445566',
            'email' => 'nxbtre@example.com',
            'address' => '200 Đường Trẻ, TP.HCM',
            'is_active' => true,
        ]);

        // Đảm bảo SupplierFactory cũng tạo 'code'
        // Nếu SupplierFactory chưa có, bạn sẽ cần tạo nó:
        // php artisan make:factory SupplierFactory --model=Supplier
        // Và chỉnh sửa nó như sau:
        // app/Database/Factories/SupplierFactory.php
        // public function definition(): array
        // {
        //     return [
        //         'code' => 'SUP' . $this->faker->unique()->randomNumber(3, true), // Hoặc logic tạo code khác
        //         'name' => $this->faker->company . ' Publisher',
        //         'contact_person' => $this->faker->name,
        //         'phone' => $this->faker->phoneNumber,
        //         'email' => $this->faker->unique()->companyEmail,
        //         'address' => $this->faker->address,
        //         'is_active' => $this->faker->boolean(90),
        //     ];
        // }
        Supplier::factory()->count(3)->create();
    }
}