<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Category::truncate();

        $categories = [
            'Tiểu thuyết',
            'Truyện ngắn',
            'Văn học',
            'Khoa học',
            'Kinh tế',
            'Lịch sử',
            'Thiếu nhi',
            'Tâm lý',
            'Kỹ năng sống',
            'Nghệ thuật',
        ];

        foreach ($categories as $categoryName) {
            Category::create(['name' => $categoryName]);
        }
    }
}