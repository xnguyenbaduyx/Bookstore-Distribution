<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Distribution;
use App\Models\DistributionDetail;
use App\Models\OrderRequest;
use App\Models\User;
use App\Models\Book;
use App\Enums\DistributionStatus;
use App\Enums\OrderStatus;

class DistributionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Distribution::truncate();
        DistributionDetail::truncate();

        $orderRequests = OrderRequest::where('status', OrderStatus::PENDING)->get();
        $warehouseUsers = User::where('role', 'warehouse')->get();
        $managerUsers = User::where('role', 'manager')->get();
        $books = Book::all();

        if ($orderRequests->isEmpty() || $warehouseUsers->isEmpty() || $managerUsers->isEmpty() || $books->isEmpty()) {
            $this->command->warn('Không thể tạo Distribution vì thiếu OrderRequest, Users (Warehouse/Manager) hoặc Books.');
            // Gọi các seeder phụ thuộc nếu chưa có
            $this->call(OrderRequestSeeder::class);
            $orderRequests = OrderRequest::where('status', OrderStatus::PENDING)->get();
            if ($orderRequests->isEmpty()) return;
        }

        // Tạo một số phiếu phân phối từ các yêu cầu đặt hàng đã được tạo
        foreach ($orderRequests->take(2) as $orderRequest) { // Lấy 2 yêu cầu để tạo phân phối
            $manager = $managerUsers->random();
            $warehouse = $warehouseUsers->random();

            // Cập nhật trạng thái OrderRequest thành Approved để tạo Distribution
            $orderRequest->update([
                'status' => OrderStatus::APPROVED,
                'approved_at' => now(),
                'approved_by' => $manager->id,
            ]);

            $distribution = Distribution::create([
                'code' => 'DIST' . now()->format('Ymd') . str_pad(rand(1, 100), 4, '0', STR_PAD_LEFT),
                'order_request_id' => $orderRequest->id,
                'branch_id' => $orderRequest->branch_id,
                'created_by' => $manager->id,
                'status' => DistributionStatus::CONFIRMED,
                'shipped_at' => null,
                'delivered_at' => null,
            ]);

            foreach ($orderRequest->details as $orderDetail) {
                DistributionDetail::create([
                    'distribution_id' => $distribution->id,
                    'book_id' => $orderDetail->book_id,
                    'quantity' => $orderDetail->quantity,
                    'unit_price' => $orderDetail->unit_price ?? $orderDetail->book->price, // Sử dụng giá sách nếu orderDetail không có
                    'total_price' => ($orderDetail->unit_price ?? $orderDetail->book->price) * $orderDetail->quantity,
                ]);
            }
        }
    }
}