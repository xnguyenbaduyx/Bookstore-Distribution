<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\BranchInventory;
use App\Models\Book; // Đã có
use App\Models\Import; // Đã có
use App\Models\Distribution; // Đã có
use App\Models\OrderRequest; // Mới thêm để update status của OrderRequest khi hủy phân phối
use App\Enums\DistributionStatus; // Mới thêm
use App\Enums\OrderStatus; // Mới thêm
use DB;

class InventoryService
{
    public function updateInventoryFromImport(Import $import)
    {
        DB::beginTransaction();
        
        try {
            foreach ($import->details as $detail) {
                // Lấy hoặc tạo bản ghi tồn kho trung tâm cho sách
                $inventory = Inventory::firstOrCreate(
                    ['book_id' => $detail->book_id],
                    ['quantity' => 0, 'reserved_quantity' => 0, 'available_quantity' => 0]
                );
                
                // Thêm số lượng sách nhập vào tồn kho trung tâm
                $inventory->addStock($detail->quantity);
            }

            // Cập nhật trạng thái phiếu nhập thành đã nhận
            $import->update([
                'status' => 'received',
                'received_at' => now(),
            ]);

            DB::commit();
            return ['success' => true, 'message' => 'Cập nhật tồn kho thành công.'];
            
        } catch (\Exception $e) {
            DB::rollback();
            return ['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()];
        }
    }

    public function updateInventoryFromDistribution(Distribution $distribution)
    {
        DB::beginTransaction();
        
        try {
            foreach ($distribution->details as $detail) {
                // Cập nhật tồn kho trung tâm: giảm số lượng tổng và giải phóng số lượng đã đặt trước
                $inventory = Inventory::where('book_id', $detail->book_id)->first();
                if ($inventory) {
                    $inventory->confirmStock($detail->quantity); // Phương thức này sẽ giảm cả quantity và reserved_quantity
                } else {
                    DB::rollback();
                    return ['success' => false, 'message' => 'Không tìm thấy tồn kho trung tâm cho sách ID: ' . $detail->book_id];
                }
            }

            // Cập nhật trạng thái phiếu phân phối thành đã giao
            $distribution->update([
                'status' => DistributionStatus::DELIVERED,
                'delivered_at' => now(),
            ]);

            // Cập nhật trạng thái yêu cầu đặt hàng gốc thành hoàn thành
            if ($distribution->orderRequest) { // Đảm bảo có orderRequest
                $distribution->orderRequest->update([
                    'status' => OrderStatus::COMPLETED
                ]);
            }

            DB::commit();
            return ['success' => true, 'message' => 'Cập nhật tồn kho và giao hàng thành công.'];
            
        } catch (\Exception $e) {
            DB::rollback();
            return ['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()];
        }
    }

    /**
     * Phương thức mới: Xử lý việc chi nhánh xác nhận đã nhận phiếu phân phối.
     * Cập nhật tồn kho chi nhánh và trạng thái phiếu phân phối.
     */
    public function confirmBranchReceivedDistribution(Distribution $distribution)
    {
        DB::beginTransaction();

        try {
            // Kiểm tra trạng thái phiếu phân phối phải là 'shipped'
            if ($distribution->status !== DistributionStatus::SHIPPED) {
                DB::rollback();
                return ['success' => false, 'message' => 'Phiếu phân phối chưa được xuất kho hoặc đã hoàn thành.'];
            }

            foreach ($distribution->details as $detail) {
                // Cập nhật tồn kho chi nhánh: tăng số lượng sách
                $branchInventory = BranchInventory::firstOrCreate(
                    [
                        'branch_id' => $distribution->branch_id,
                        'book_id' => $detail->book_id
                    ],
                    ['quantity' => 0]
                );
                
                $branchInventory->addStock($detail->quantity);
            }

            // Cập nhật trạng thái phiếu phân phối thành 'delivered' (đã giao)
            $distribution->update([
                'status' => DistributionStatus::DELIVERED,
                'delivered_at' => now(),
                // 'received_by' => auth()->id(), // Có thể thêm trường này trong migration nếu cần người nhận cụ thể
            ]);

            // Cập nhật trạng thái yêu cầu đặt hàng gốc thành 'completed'
            if ($distribution->orderRequest) {
                $distribution->orderRequest->update([
                    'status' => OrderStatus::COMPLETED
                ]);
            }

            DB::commit();
            return ['success' => true, 'message' => 'Chi nhánh đã xác nhận nhận hàng thành công.'];

        } catch (\Exception $e) {
            DB::rollback();
            return ['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()];
        }
    }

    /**
     * Phương thức mới: Giải phóng số lượng sách đã đặt trước khi phiếu phân phối bị hủy.
     * Thường được gọi khi Manager/Warehouse hủy phiếu phân phối đã ở trạng thái 'confirmed' hoặc 'pending'.
     */
    public function releaseReservedStockForDistribution(Distribution $distribution)
    {
        DB::beginTransaction();

        try {
            foreach ($distribution->details as $detail) {
                $inventory = Inventory::where('book_id', $detail->book_id)->first();
                if ($inventory) {
                    // Giải phóng số lượng đã đặt trước
                    $inventory->releaseReservedStock($detail->quantity);
                } else {
                    // Xử lý lỗi nếu không tìm thấy tồn kho (ít xảy ra nếu luồng đúng)
                    \Log::error("Không tìm thấy tồn kho trung tâm cho sách ID: " . $detail->book_id . " khi hủy phân phối " . $distribution->id);
                }
            }
            DB::commit();
            return ['success' => true, 'message' => 'Đã giải phóng tồn kho đặt trước.'];
        } catch (\Exception $e) {
            DB::rollback();
            return ['success' => false, 'message' => 'Lỗi khi giải phóng tồn kho đặt trước: ' . $e->getMessage()];
        }
    }


    public function getInventoryReport()
    {
        return Inventory::with('book.category')
            ->get()
            ->map(function ($inventory) {
                return [
                    'book_id' => $inventory->book_id,
                    'book_title' => $inventory->book->title,
                    'category' => $inventory->book->category->name,
                    'quantity' => $inventory->quantity,
                    'reserved_quantity' => $inventory->reserved_quantity,
                    'available_quantity' => $inventory->available_quantity,
                ];
            });
    }

    public function getBranchInventoryReport($branchId)
    {
        return BranchInventory::with('book.category')
            ->where('branch_id', $branchId)
            ->get()
            ->map(function ($inventory) {
                return [
                    'book_id' => $inventory->book_id,
                    'book_title' => $inventory->book->title,
                    'category' => $inventory->book->category->name,
                    'quantity' => $inventory->quantity,
                ];
            });
    }

    public function getLowStockBooks($threshold = 10)
    {
        return Inventory::with('book')
            ->where('available_quantity', '<=', $threshold)
            ->get();
    }
}