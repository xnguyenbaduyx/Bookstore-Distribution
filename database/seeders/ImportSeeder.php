<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Import;
use App\Models\ImportDetail;
use App\Models\Supplier;
use App\Models\User; // Để lấy người tạo
use App\Models\Book;
use App\Enums\ImportStatus;

class ImportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Import::truncate();
        ImportDetail::truncate();

        $suppliers = Supplier::all();
        $managerUsers = User::where('role', 'manager')->get();
        $books = Book::all();

        if ($suppliers->isEmpty() || $managerUsers->isEmpty() || $books->isEmpty()) {
            $this->command->warn('Không thể tạo Import vì thiếu Supplier, Manager Users hoặc Books.');
            // Gọi các seeder phụ thuộc nếu chưa có
            $this->call(SupplierSeeder::class);
            $this->call(BookSeeder::class);
            $suppliers = Supplier::all();
            $books = Book::all();
            if ($suppliers->isEmpty() || $books->isEmpty()) return;
        }

        // Tạo 3 phiếu nhập hàng mẫu
        for ($i = 0; $i < 3; $i++) {
            $supplier = $suppliers->random();
            $manager = $managerUsers->random();

            $import = Import::create([
                'code' => 'IMP' . now()->format('Ymd') . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'supplier_id' => $supplier->id,
                'created_by' => $manager->id,
                'notes' => 'Nhập hàng định kỳ tháng ' . (date('m') + $i),
                'status' => ImportStatus::PENDING, // Mặc định là pending từ nhà cung cấp
                'total_amount' => 0, // Sẽ cập nhật sau
            ]);

            $totalAmount = 0;
            $selectedBooks = $books->random(rand(2, 5)); // Chọn 2-5 cuốn sách cho mỗi phiếu nhập

            foreach ($selectedBooks as $book) {
                $quantity = rand(20, 50);
                $unitPrice = $book->price * 0.7; // Giả sử giá nhập bằng 70% giá bán
                ImportDetail::create([
                    'import_id' => $import->id,
                    'book_id' => $book->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $unitPrice * $quantity,
                ]);
                $totalAmount += ($unitPrice * $quantity);
            }

            $import->update(['total_amount' => $totalAmount]);
        }
    }
}