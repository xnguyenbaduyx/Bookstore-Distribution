<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Models\OrderRequest;
use App\Models\BranchInventory;
use App\Models\Distribution;
use Carbon\Carbon;

class BranchController extends Controller
{
    public function dashboard()
    {
        $branchId = auth()->user()->branch_id;
        
        // Thống kê tổng quan
        $stats = [
            'pending_orders' => OrderRequest::where('branch_id', $branchId)
                ->where('status', 'pending')->count(),
            'approved_orders' => OrderRequest::where('branch_id', $branchId)
                ->where('status', 'approved')->count(),
            'rejected_orders' => OrderRequest::where('branch_id', $branchId)
                ->where('status', 'rejected')->count(),
            'completed_orders' => OrderRequest::where('branch_id', $branchId)
                ->where('status', 'completed')->count(),
            'processing_orders' => OrderRequest::where('branch_id', $branchId)
                ->where('status', 'processing')->count(),
            'total_inventory_items' => BranchInventory::where('branch_id', $branchId)
                ->sum('quantity'),
            'total_inventory_value' => BranchInventory::where('branch_id', $branchId)
                ->with('book')
                ->get()
                ->sum(function($item) {
                    return $item->quantity * $item->book->price;
                }),
            'low_stock_items' => BranchInventory::where('branch_id', $branchId)
                ->where('quantity', '<=', 5)
                ->where('quantity', '>', 0)->count(),
            'out_of_stock_items' => BranchInventory::where('branch_id', $branchId)
                ->where('quantity', '<=', 0)->count(),
        ];

        // Đơn hàng gần đây
        $recent_orders = OrderRequest::with(['creator', 'approver'])
            ->where('branch_id', $branchId)
            ->latest()
            ->take(10)
            ->get();

        // Tồn kho chi nhánh
        $inventory_items = BranchInventory::with(['book.category'])
            ->where('branch_id', $branchId)
            ->where('quantity', '>', 0)
            ->orderBy('quantity', 'desc')
            ->take(10)
            ->get();

        // Phân phối đang đến
        $incoming_distributions = Distribution::with(['orderRequest', 'details.book'])
            ->where('branch_id', $branchId)
            ->whereIn('status', ['confirmed', 'shipped'])
            ->latest()
            ->take(5)
            ->get();

        // Thống kê theo tuần (7 ngày gần nhất)
        $weekly_stats = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $weekly_stats[] = [
                'date' => $date->format('d/m'),
                'orders_created' => OrderRequest::where('branch_id', $branchId)
                    ->whereDate('created_at', $date)->count(),
                'orders_approved' => OrderRequest::where('branch_id', $branchId)
                    ->whereDate('approved_at', $date)->count(),
            ];
        }

        // Tháng này so với tháng trước
        $thisMonth = OrderRequest::where('branch_id', $branchId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        
        $lastMonth = OrderRequest::where('branch_id', $branchId)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();

        $monthly_growth = $lastMonth > 0 ? 
            round((($thisMonth - $lastMonth) / $lastMonth) * 100, 1) : 0;

        // Top sách bán chạy
        $top_books = OrderRequest::where('branch_id', $branchId)
            ->where('status', 'completed')
            ->with('details.book')
            ->whereMonth('created_at', now()->month)
            ->get()
            ->flatMap(function($order) {
                return $order->details;
            })
            ->groupBy('book_id')
            ->map(function($details) {
                return [
                    'book' => $details->first()->book,
                    'total_quantity' => $details->sum('quantity'),
                    'total_value' => $details->sum('total_price'),
                ];
            })
            ->sortByDesc('total_quantity')
            ->take(5);

        return view('branch.dashboard', compact(
            'stats', 'recent_orders', 'inventory_items', 'incoming_distributions',
            'weekly_stats', 'monthly_growth', 'top_books'
        ));
    }

    public function notifications()
    {
        $branchId = auth()->user()->branch_id;
        
        $notifications = [
            'order_updates' => OrderRequest::where('branch_id', $branchId)
                ->whereIn('status', ['approved', 'rejected'])
                ->whereDate('updated_at', '>=', now()->subDays(7))
                ->with(['approver'])
                ->latest()
                ->get(),
            
            'incoming_shipments' => Distribution::where('branch_id', $branchId)
                ->where('status', 'shipped')
                ->with(['details.book'])
                ->latest()
                ->get(),
                
            'low_stock_alerts' => BranchInventory::where('branch_id', $branchId)
                ->where('quantity', '<=', 5)
                ->where('quantity', '>', 0)
                ->with('book')
                ->get(),
        ];

        return view('branch.notifications', compact('notifications'));
    }

    public function profile()
    {
        $user = auth()->user();
        $user->load('branch');
        
        $branch_stats = [
            'total_users' => $user->branch->users()->where('is_active', true)->count(),
            'total_orders_this_year' => OrderRequest::where('branch_id', $user->branch_id)
                ->whereYear('created_at', now()->year)->count(),
            'success_rate' => $this->calculateSuccessRate($user->branch_id),
            'avg_order_value' => $this->calculateAverageOrderValue($user->branch_id),
        ];

        return view('branch.profile', compact('user', 'branch_stats'));
    }

    private function calculateSuccessRate($branchId)
    {
        $total = OrderRequest::where('branch_id', $branchId)->count();
        $approved = OrderRequest::where('branch_id', $branchId)
            ->whereIn('status', ['approved', 'completed'])->count();
        
        return $total > 0 ? round(($approved / $total) * 100, 1) : 0;
    }

    private function calculateAverageOrderValue($branchId)
    {
        $orders = OrderRequest::where('branch_id', $branchId)
            ->where('status', 'completed')
            ->with('details')
            ->get();

        if ($orders->count() === 0) {
            return 0;
        }

        $totalValue = $orders->sum(function($order) {
            return $order->details->sum('total_price');
        });

        return round($totalValue / $orders->count(), 0);
    }
}