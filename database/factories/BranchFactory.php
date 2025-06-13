<?php

namespace Database\Factories;

use App\Models\Branch; // Import Model Branch
use Illuminate\Database\Eloquent\Factories\Factory;

class BranchFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Branch::class; // Đảm bảo rằng model được định nghĩa đúng

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'BR' . $this->faker->unique()->randomNumber(3, true), // Tạo mã chi nhánh duy nhất, 3 chữ số, không bắt đầu bằng 0
            'name' => $this->faker->company() . ' Chi nhánh', // Tên công ty/chi nhánh
            'address' => $this->faker->address(), // Địa chỉ giả
            'phone' => $this->faker->phoneNumber(), // Số điện thoại giả
            'email' => $this->faker->unique()->safeEmail(), // Email duy nhất
            'manager_name' => $this->faker->name(), // Tên người quản lý giả
            'is_active' => $this->faker->boolean(90), // 90% là true
        ];
    }
}