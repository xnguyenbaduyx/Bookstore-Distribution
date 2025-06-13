<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Inventory;
use App\Models\Book;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Inventory::truncate();

        $books = Book::all();

        if ($books->isEmpty()) {
            $this->call(BookSeeder::class); // Đảm bảo có sách
            $books = Book::all();
        }

        foreach ($books as $book) {
            // Đặt số lượng tồn kho ngẫu nhiên cho mỗi cuốn sách
            Inventory::create([
                'book_id' => $book->id,
                'quantity' => rand(50, 200), // Số lượng tổng
                'reserved_quantity' => 0,
                'available_quantity' => rand(50, 200), // Ban đầu available = quantity
            ]);
        }
    }
}