<?php

namespace Database\Factories;

use App\Models\Supplier; // Import Model Supplier
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Supplier::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'SUP' . $this->faker->unique()->randomNumber(3, true), // Tạo mã nhà cung cấp duy nhất
            'name' => $this->faker->company() . ' Co., Ltd.', // Tên công ty
            'contact_person' => $this->faker->name(), // Người liên hệ
            'phone' => $this->faker->phoneNumber(), // Số điện thoại
            'email' => $this->faker->unique()->safeEmail(), // Email duy nhất
            'address' => $this->faker->address(), // Địa chỉ
            'is_active' => $this->faker->boolean(90), // 90% là true
        ];
    }
}