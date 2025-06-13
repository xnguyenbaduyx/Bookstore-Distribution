<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Branch; // Cần import Branch để gán branch_id
use Illuminate\Support\Facades\Hash;
use App\Enums\UserRole; // Đảm bảo bạn đã định nghĩa Enum này

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Xóa dữ liệu cũ nếu có
        User::truncate();

        // Tạo Admin User
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'), // Mật khẩu là 'password'
            'role' => UserRole::ADMIN,
            'is_active' => true,
        ]);

        // Tạo Manager User
        User::create([
            'name' => 'Manager User',
            'email' => 'manager@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::MANAGER,
            'is_active' => true,
        ]);

        // Tạo Warehouse User
        User::create([
            'name' => 'Warehouse User',
            'email' => 'warehouse@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::WAREHOUSE,
            'is_active' => true,
        ]);

        // Tạo Branch User (cần có ít nhất một chi nhánh)
        $branch1 = Branch::firstOrCreate(['name' => 'Chi nhánh chính'], ['address' => '123 Đường ABC, Quận 1', 'phone' => '02812345678']);
        $branch2 = Branch::firstOrCreate(['name' => 'Chi nhánh phụ'], ['address' => '456 Đường XYZ, Quận 2', 'phone' => '02887654321']);

        User::create([
            'name' => 'Branch User 1',
            'email' => 'branch1@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::BRANCH,
            'branch_id' => $branch1->id,
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Branch User 2',
            'email' => 'branch2@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::BRANCH,
            'branch_id' => $branch2->id,
            'is_active' => true,
        ]);

        // Tạo thêm một số user khác
        User::factory()->count(5)->create();
    }
}