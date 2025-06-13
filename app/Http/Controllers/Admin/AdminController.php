<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Book;
use App\Models\User;
use App\Models\OrderRequest;
use App\Models\Supplier;
use App\Models\Category;
use App\Models\Distribution;
use App\Models\Import;
use App\Models\Inventory;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function dashboard()
    {
        // Thống kê tổng quan
        $stats = [
            'total_branches' => Branch::where('is_active', true)->count(),
            'total_books' => Book::where('is_active', true)->count(),
            'total_users' => User::where('is_active', true)->count(),
            'total_suppliers' => Supplier::where('is_active', true)->count(),
            'total_categories' => Category::where('is_active', true)->count(),
            'pending_orders' => OrderRequest::where('status', 'pending')->count(),
            'approved_orders' => OrderRequest::where('status', 'approved')->count(),
            'total_distributions' => Distribution::count(),
            'total_imports' => Import::count(),
            'low_stock_books' => Inventory::where('available_quantity', '<=', 10)->count(),
        ];

        // Đơn hàng gần đây
        $recent_orders = OrderRequest::with(['branch', 'creator'])
            ->latest()
            ->take(10)
            ->get();

        // Người dùng mới
        $recent_users = User::with('branch')
            ->latest()
            ->take(5)
            ->get();

        // Sách sắp hết hàng
        $low_stock_books = Inventory::with('book')
            ->where('available_quantity', '<=', 10)
            ->take(5)
            ->get();

        // Thống kê theo tháng (12 tháng gần nhất)
        $monthly_orders = OrderRequest::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->whereYear('created_at', date('Y'))
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();

        $monthly_data = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthly_data[] = $monthly_orders[$i] ?? 0;
        }

        // Thống kê theo vai trò
        $user_by_roles = User::selectRaw('role, COUNT(*) as count')
            ->where('is_active', true)
            ->groupBy('role')
            ->pluck('count', 'role')
            ->toArray();

        return view('admin.dashboard', compact(
            'stats', 'recent_orders', 'recent_users', 'low_stock_books', 
            'monthly_data', 'user_by_roles'
        ));
    }

    public function reports()
    {
        $data = [
            'orders_by_status' => OrderRequest::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            
            'orders_by_branch' => OrderRequest::with('branch')
                ->selectRaw('branch_id, COUNT(*) as count')
                ->groupBy('branch_id')
                ->get()
                ->map(function($item) {
                    return [
                        'branch_name' => $item->branch->name,
                        'count' => $item->count
                    ];
                }),
            
            'books_by_category' => Book::with('category')
                ->selectRaw('category_id, COUNT(*) as count')
                ->groupBy('category_id')
                ->get()
                ->map(function($item) {
                    return [
                        'category_name' => $item->category->name,
                        'count' => $item->count
                    ];
                }),
        ];

        return view('admin.reports', compact('data'));
    }
}