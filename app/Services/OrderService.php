<?php

namespace App\Services;

use App\Models\OrderRequest;
use App\Models\Distribution;
use App\Models\DistributionDetail;
use App\Models\Inventory;
use App\Enums\OrderStatus;
use App\Enums\DistributionStatus;
use DB;

class OrderService
{
    public function approveOrder(OrderRequest $orderRequest, $userId)
    {
        DB::beginTransaction();
        
        try {
            // Check inventory availability
            foreach ($orderRequest->details as $detail) {
                $inventory = Inventory::where('book_id', $detail->book_id)->first();
                if (!$inventory || !$inventory->hasStock($detail->quantity)) {
                    DB::rollback();
                    return [
                        'success' => false,
                        'message' => "Không đủ tồn kho cho sách: {$detail->book->title}"
                    ];
                }
            }

            // Reserve stock
            foreach ($orderRequest->details as $detail) {
                $inventory = Inventory::where('book_id', $detail->book_id)->first();
                $inventory->reserveStock($detail->quantity);
            }

            // Update order status
            $orderRequest->update([
                'status' => OrderStatus::APPROVED,
                'approved_at' => now(),
                'approved_by' => $userId,
            ]);

            // Create distribution
            $distribution = Distribution::create([
                'code' => $this->generateDistributionCode(),
                'order_request_id' => $orderRequest->id,
                'branch_id' => $orderRequest->branch_id,
                'created_by' => $userId,
                'status' => DistributionStatus::PENDING,
            ]);

            // Create distribution details
            foreach ($orderRequest->details as $detail) {
                DistributionDetail::create([
                    'distribution_id' => $distribution->id,
                    'book_id' => $detail->book_id,
                    'quantity' => $detail->quantity,
                    'unit_price' => $detail->unit_price,
                    'total_price' => $detail->total_price,
                ]);
            }

            DB::commit();
            
            return [
                'success' => true,
                'message' => 'Đơn hàng đã được duyệt và tạo phiếu phân phối thành công.',
                'distribution' => $distribution
            ];
            
        } catch (\Exception $e) {
            DB::rollback();
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ];
        }
    }

    public function rejectOrder(OrderRequest $orderRequest, $userId, $reason)
    {
        $orderRequest->update([
            'status' => OrderStatus::REJECTED,
            'rejection_reason' => $reason,
            'approved_by' => $userId,
        ]);

        return [
            'success' => true,
            'message' => 'Đơn hàng đã được từ chối.'
        ];
    }

    public function generateOrderCode()
    {
        $prefix = 'ORD';
        $date = now()->format('Ymd');
        $count = OrderRequest::whereDate('created_at', now())->count() + 1;
        return $prefix . $date . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function generateDistributionCode()
    {
        $prefix = 'DIST';
        $date = now()->format('Ymd');
        $count = Distribution::whereDate('created_at', now())->count() + 1;
        return $prefix . $date . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}