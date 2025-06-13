<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OrderRequest;
use App\Models\OrderRequestDetail;
use App\Models\Branch;
use App\Models\User;
use App\Models\Book;
use App\Enums\OrderStatus;

class OrderRequestSeeder extends Seeder
{
    public function run(): void
    {
        // Xoá dữ liệu cũ
        OrderRequestDetail::truncate();
        OrderRequest::truncate();

        $branches = Branch::all();
        $branchUsers = User::where('role', 'branch')->get();
        $books = Book::all();

        // Seed lại nếu thiếu dữ liệu cần thiết
        if ($branches->isEmpty()) {
            $this->call(BranchSeeder::class);
            $branches = Branch::all();
        }

        if ($branchUsers->isEmpty()) {
            $this->call(UserSeeder::class);
            $branchUsers = User::where('role', 'branch')->get();
        }

        if ($books->isEmpty()) {
            $this->call(BookSeeder::class);
            $books = Book::all();
        }

        // Nếu sau khi seed vẫn rỗng, dừng lại
        if ($branches->isEmpty() || $branchUsers->isEmpty() || $books->isEmpty()) {
            $this->command->warn('Không thể tạo OrderRequest vì thiếu dữ liệu Branch, User hoặc Book.');
            return;
        }

        // Lọc chi nhánh có ít nhất 1 user
        $validBranches = $branches->filter(function ($branch) use ($branchUsers) {
            return $branchUsers->where('branch_id', $branch->id)->isNotEmpty();
        });

        if ($validBranches->isEmpty()) {
            $this->command->warn("Không có chi nhánh nào có user hợp lệ để tạo OrderRequest.");
            return;
        }

        // Tạo 5 yêu cầu đặt hàng
        for ($i = 0; $i < 5; $i++) {
            $branch = $validBranches->random();
            $usersInBranch = $branchUsers->where('branch_id', $branch->id);

            if ($usersInBranch->isEmpty()) {
                $this->command->warn("Chi nhánh {$branch->name} không có user. Bỏ qua.");
                continue;
            }

            $creator = $usersInBranch->random();

            $order = OrderRequest::create([
                'code' => 'ORD' . now()->format('Ymd') . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'branch_id' => $branch->id,
                'created_by' => $creator->id,
                'notes' => 'Yêu cầu sách bổ sung cho quý ' . ($i + 1),
                'status' => OrderStatus::PENDING,
            ]);

            $totalItemsCalculated = 0;
            $selectedBooks = $books->random(rand(1, 3));

            foreach ($selectedBooks as $book) {
                $quantity = rand(5, 15);

                OrderRequestDetail::create([
                    'order_request_id' => $order->id,
                    'book_id' => $book->id,
                    'quantity' => $quantity,
                    'unit_price' => $book->price,
                    'total_price' => $book->price * $quantity,
                ]);

                $totalItemsCalculated += $quantity;
            }

            $this->command->info("Đã tạo Order: {$order->code}, tổng số lượng: {$totalItemsCalculated}, chi nhánh: {$branch->name}, user: {$creator->name}");
        }
    }
}
