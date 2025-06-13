<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\OrderRequest;
use App\Models\Distribution;
use App\Models\Import;
use App\Models\Inventory;
use App\Models\Branch;
use App\Models\Supplier;
use Carbon\Carbon;

class ManagerController extends Controller
{
    public function dashboard()
    {
        // Thống kê tổng quan
        $stats = [
            'pending_orders' => OrderRequest::where('status', 'pending')->count(),
            'approved_orders' => OrderRequest::where('status', 'approved')->count(),
            'rejected_orders' => OrderRequest::where('status', 'rejected')->count(),
            'processing_orders' => OrderRequest::where('status', 'processing')->count(),
            'completed_orders' => OrderRequest::where('status', 'completed')->count(),
            'pending_distributions' => Distribution::where('status', 'pending')->count(),
            'confirmed_distributions' => Distribution::where('status', 'confirmed')->count(),
            'pending_imports' => Import::where('status', 'pending')->count(),
            'confirmed_imports' => Import::where('status', 'confirmed')->count(),
            'low_stock_books' => Inventory::where('available_quantity', '<=', 10)->count(),
            'out_of_stock_books' => Inventory::where('available_quantity', '<=', 0)->count(),
        ];

        // Đơn hàng chờ duyệt (ưu tiên cao)
        $recent_orders = OrderRequest::with(['branch', 'creator'])
            ->where('status', 'pending')
            ->latest()
            ->take(10)
            ->get();

        // Sách sắp hết hàng cần nhập thêm
        $low_stock_books = Inventory::with('book.category')
            ->where('available_quantity', '<=', 10)
            ->orderBy('available_quantity', 'asc')
            ->take(10)
            ->get();

        // Phân phối cần xử lý
        $pending_distributions = Distribution::with(['branch', 'orderRequest'])
            ->where('status', 'pending')
            ->latest()
            ->take(5)
            ->get();

        // Nhập hàng đang chờ
        $pending_imports = Import::with('supplier')
            ->where('status', 'pending')
            ->latest()
            ->take(5)
            ->get();

        // Thống kê theo thời gian (7 ngày gần nhất)
        $daily_stats = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $daily_stats[] = [
                'date' => $date->format('d/m'),
                'orders' => OrderRequest::whereDate('created_at', $date)->count(),
                'approved' => OrderRequest::whereDate('approved_at', $date)->count(),
            ];
        }

        return view('manager.dashboard', compact(
            'stats', 'recent_orders', 'low_stock_books', 
            'pending_distributions', 'pending_imports', 'daily_stats'
        ));
    }

    public function quickStats()
    {
        // API endpoint cho real-time stats
        $stats = [
            'pending_orders' => OrderRequest::where('status', 'pending')->count(),
            'low_stock_alert' => Inventory::where('available_quantity', '<=', 5)->count(),
            'urgent_distributions' => Distribution::where('status', 'pending')
                ->where('created_at', '<', Carbon::now()->subHours(24))
                ->count(),
        ];

        return response()->json($stats);
    }
}