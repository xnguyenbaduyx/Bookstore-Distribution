<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Category; // Import Category
use App\Models\Supplier; // Import Supplier (nếu có supplier_id trong bảng books)
use Illuminate\Database\Eloquent\Factories\Factory;

class BookFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Book::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Lấy category_id và supplier_id từ các bản ghi đã tồn tại
        // Nếu không có category/supplier nào được seed, điều này sẽ gây lỗi.
        // Tuy nhiên, logic trong BookSeeder đã đảm bảo Category/Supplier có dữ liệu.
        $category = Category::inRandomOrder()->first();
        // CHỈ LẤY SUPPLIER NẾU BẠN CÓ CỘT 'supplier_id' TRONG BẢNG BOOKS VÀ CÓ FOREIGN KEY
        // $supplier = Supplier::inRandomOrder()->first();

        return [
            'title' => $this->faker->sentence(3),
            'author' => $this->faker->name(),
            'publisher' => $this->faker->company(),
            'isbn' => $this->faker->unique()->isbn13(),
            'category_id' => $category ? $category->id : null, // Gán id hoặc null nếu không tìm thấy (mặc dù BookSeeder đã xử lý)
            'price' => $this->faker->numberBetween(50000, 300000),
            'description' => $this->faker->paragraph(3),
            'published_date' => $this->faker->date(),
            'image' => null,
            'is_active' => $this->faker->boolean(90),
            // 'supplier_id' => $supplier ? $supplier->id : null, // CHỈ THÊM DÒNG NÀY NẾU CÓ CỘT 'supplier_id' TRONG BẢNG BOOKS
        ];
    }
}