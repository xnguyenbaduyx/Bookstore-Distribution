<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BranchInventory;
use App\Models\Branch;
use App\Models\Book;

class BranchInventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        BranchInventory::truncate();

        $branches = Branch::all();
        $books = Book::all();

        if ($branches->isEmpty()) {
            $this->call(BranchSeeder::class);
            $branches = Branch::all();
        }
        if ($books->isEmpty()) {
            $this->call(BookSeeder::class);
            $books = Book::all();
        }

        foreach ($branches as $branch) {
            // Mỗi chi nhánh sẽ có một số sách nhất định
            $selectedBooks = $books->random(rand(5, 10)); // Chọn ngẫu nhiên 5-10 cuốn sách

            foreach ($selectedBooks as $book) {
                BranchInventory::create([
                    'branch_id' => $branch->id,
                    'book_id' => $book->id,
                    'quantity' => rand(5, 30), // Số lượng ngẫu nhiên cho chi nhánh
                ]);
            }
        }
    }
}