<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Xóa dữ liệu cũ (đã được bọc trong DB::statement trong DatabaseSeeder)
        // Branch::truncate(); // Không cần truncate ở đây nếu bạn đã tắt foreign key checks ở DatabaseSeeder

        // Tạo các chi nhánh với đầy đủ thông tin, bao gồm 'manager_name'
        Branch::create([
            'code' => 'BR001',
            'name' => 'Chi nhánh chính',
            'address' => '123 Đường ABC, Quận 1, TP.HCM',
            'phone' => '02812345678',
            'email' => 'chinhanhchinh@example.com',
            'manager_name' => 'Nguyễn Văn A', // Đảm bảo trường này có giá trị
            'is_active' => true,
        ]);

        Branch::create([
            'code' => 'BR002',
            'name' => 'Chi nhánh phụ',
            'address' => '456 Đường XYZ, Quận 2, TP.HCM',
            'phone' => '02887654321',
            'email' => 'chinhanhphu@example.com',
            'manager_name' => 'Trần Thị B', // Đảm bảo trường này có giá trị
            'is_active' => true,
        ]);

        // Nếu bạn có BranchFactory, hãy đảm bảo rằng Factory này cũng tạo ra 'manager_name'
        // app/Database/Factories/BranchFactory.php
        // public function definition(): array
        // {
        //     return [
        //         'code' => 'BR' . $this->faker->unique()->randomNumber(3),
        //         'name' => $this->faker->company . ' Branch',
        //         'address' => $this->faker->address,
        //         'phone' => $this->faker->phoneNumber,
        //         'email' => $this->faker->unique()->companyEmail,
        //         'manager_name' => $this->faker->name(), // Đảm bảo Factory tạo manager_name
        //         'is_active' => $this->faker->boolean(90),
        //     ];
        // }
        Branch::factory()->count(3)->create();
    }
}