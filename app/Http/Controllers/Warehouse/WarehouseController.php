<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Distribution;
use App\Models\Import;
use App\Models\Inventory;
use App\Models\Book;
use App\Models\Supplier;
use Carbon\Carbon;

class WarehouseController extends Controller
{
    public function dashboard()
    {
        // Thống kê tổng quan
        $stats = [
            'pending_distributions' => Distribution::where('status', 'pending')->count(),
            'confirmed_distributions' => Distribution::where('status', 'confirmed')->count(),
            'shipped_distributions' => Distribution::where('status', 'shipped')->count(),
            'pending_imports' => Import::where('status', 'pending')->count(),
            'confirmed_imports' => Import::where('status', 'confirmed')->count(),
            'received_imports_today' => Import::where('status', 'received')
                ->whereDate('received_at', today())->count(),
            'total_inventory_value' => Inventory::with('book')->get()->sum(function($inv) {
                return $inv->quantity * $inv->book->price;
            }),
            'low_stock_count' => Inventory::where('available_quantity', '<=', 10)->count(),
            'out_of_stock_count' => Inventory::where('available_quantity', '<=', 0)->count(),
            'total_books_managed' => Inventory::where('quantity', '>', 0)->count(),
        ];

        // Phân phối cần xử lý gấp (ưu tiên cao)
        $pending_distributions = Distribution::with(['branch', 'orderRequest.creator'])
            ->where('status', 'pending')
            ->latest()
            ->take(10)
            ->get();

        // Nhập hàng chờ xử lý
        $pending_imports = Import::with(['supplier', 'creator'])
            ->where('status', 'pending')
            ->latest()
            ->take(10)
            ->get();

        // Nhập hàng đã xác nhận, cần nhận hàng
        $confirmed_imports = Import::with(['supplier', 'creator'])
            ->where('status', 'confirmed')
            ->latest()
            ->take(5)
            ->get();

        // Sách sắp hết hàng (cần báo cáo)
        $low_stock_books = Inventory::with('book.category')
            ->where('available_quantity', '<=', 10)
            ->where('available_quantity', '>', 0)
            ->orderBy('available_quantity', 'asc')
            ->take(10)
            ->get();

        // Thống kê hoạt động 7 ngày gần nhất
        $daily_activities = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $daily_activities[] = [
                'date' => $date->format('d/m'),
                'distributions_shipped' => Distribution::where('status', 'shipped')
                    ->whereDate('shipped_at', $date)->count(),
                'imports_received' => Import::where('status', 'received')
                    ->whereDate('received_at', $date)->count(),
            ];
        }

        // Phân phối quá hạn (tạo hơn 2 ngày mà chưa xử lý)
        $overdue_distributions = Distribution::where('status', 'pending')
            ->where('created_at', '<', Carbon::now()->subDays(2))
            ->count();

        return view('warehouse.dashboard', compact(
            'stats', 'pending_distributions', 'pending_imports', 'confirmed_imports',
            'low_stock_books', 'daily_activities', 'overdue_distributions'
        ));
    }

    public function quickStats()
    {
        // API endpoint cho real-time stats
        $stats = [
            'pending_distributions' => Distribution::where('status', 'pending')->count(),
            'pending_imports' => Import::where('status', 'pending')->count(),
            'low_stock_alert' => Inventory::where('available_quantity', '<=', 5)->count(),
            'overdue_tasks' => Distribution::where('status', 'pending')
                ->where('created_at', '<', Carbon::now()->subDays(1))
                ->count(),
        ];

        return response()->json($stats);
    }

    public function workload()
    {
        // Tính toán khối lượng công việc
        $workload = [
            'distributions_to_process' => Distribution::whereIn('status', ['pending', 'confirmed'])->count(),
            'imports_to_receive' => Import::where('status', 'confirmed')->count(),
            'urgent_distributions' => Distribution::where('status', 'pending')
                ->where('created_at', '<', Carbon::now()->subHours(24))
                ->count(),
            'estimated_hours' => $this->calculateEstimatedWorkHours(),
        ];

        return view('warehouse.workload', compact('workload'));
    }

    private function calculateEstimatedWorkHours()
    {
        $pendingDistributions = Distribution::where('status', 'pending')->count();
        $confirmedImports = Import::where('status', 'confirmed')->count();
        
        // Estimate: 15 minutes per distribution, 30 minutes per import
        $totalMinutes = ($pendingDistributions * 15) + ($confirmedImports * 30);
        
        return round($totalMinutes / 60, 1);
    }
}