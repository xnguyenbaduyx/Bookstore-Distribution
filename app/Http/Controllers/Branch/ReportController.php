<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Models\OrderRequest;
use App\Models\BranchInventory;
use App\Models\Distribution;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        $branchId = auth()->user()->branch_id;
        
        $summary = [
            'orders_this_month' => OrderRequest::where('branch_id', $branchId)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'completed_orders_this_month' => OrderRequest::where('branch_id', $branchId)
                ->where('status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'total_inventory_value' => BranchInventory::where('branch_id', $branchId)
                ->with('book')
                ->get()
                ->sum(function($item) {
                    return $item->quantity * $item->book->price;
                }),
            'books_received_this_month' => Distribution::where('branch_id', $branchId)
                ->where('status', 'delivered')
                ->whereMonth('delivered_at', now()->month)
                ->whereYear('delivered_at', now()->year)
                ->with('details')
                ->get()
                ->sum(function($distribution) {
                    return $distribution->details->sum('quantity');
                }),
        ];

        return view('branch.reports.index', compact('summary'));
    }

    public function orderSummary(Request $request)
    {
        $branchId = auth()->user()->branch_id;
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        $orders = OrderRequest::where('branch_id', $branchId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['details.book', 'creator', 'approver'])
            ->get();

        $summary = [
            'total_orders' => $orders->count(),
            'pending_orders' => $orders->where('status', 'pending')->count(),
            'approved_orders' => $orders->where('status', 'approved')->count(),
            'rejected_orders' => $orders->where('status', 'rejected')->count(),
            'completed_orders' => $orders->where('status', 'completed')->count(),
            'total_value' => $orders->where('status', 'completed')->sum('total_amount'),
            'avg_order_value' => $orders->where('status', 'completed')->count() > 0 ?
                $orders->where('status', 'completed')->sum('total_amount') / $orders->where('status', 'completed')->count() : 0,
            'success_rate' => $orders->count() > 0 ?
                (($orders->whereIn('status', ['approved', 'completed'])->count() / $orders->count()) * 100) : 0,
        ];

        // Top sách được đặt nhiều nhất
        $topBooks = $orders->flatMap(function($order) {
            return $order->details;
        })->groupBy('book_id')->map(function($details) {
            return [
                'book' => $details->first()->book,
                'total_quantity' => $details->sum('quantity'),
                'total_value' => $details->sum('total_price'),
                'order_count' => $details->count(),
            ];
        })->sortByDesc('total_quantity')->take(10);

        return view('branch.reports.order-summary', compact(
            'orders', 'summary', 'topBooks', 'startDate', 'endDate'
        ));
    }

    public function inventoryStatus()
    {
        $branchId = auth()->user()->branch_id;
        
        $inventory = BranchInventory::where('branch_id', $branchId)
            ->with(['book.category'])
            ->get();

        $summary = [
            'total_items' => $inventory->count(),
            'total_quantity' => $inventory->sum('quantity'),
            'total_value' => $inventory->sum(function($item) {
                return $item->quantity * $item->book->price;
            }),
            'out_of_stock' => $inventory->where('quantity', '<=', 0)->count(),
            'low_stock' => $inventory->where('quantity', '>', 0)->where('quantity', '<=', 5)->count(),
            'avg_stock_value' => $inventory->count() > 0 ?
                $inventory->sum(function($item) {
                    return $item->quantity * $item->book->price;
                }) / $inventory->count() : 0,
        ];

        $categoryBreakdown = $inventory->groupBy('book.category.name')->map(function($items) {
            return [
                'count' => $items->count(),
                'quantity' => $items->sum('quantity'),
                'value' => $items->sum(function($item) {
                    return $item->quantity * $item->book->price;
                }),
            ];
        });

        return view('branch.reports.inventory-status', compact(
            'inventory', 'summary', 'categoryBreakdown'
        ));
    }

    public function performance(Request $request)
    {
        $branchId = auth()->user()->branch_id;
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfYear();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfYear();

        // Thống kê theo tháng
        $monthlyStats = [];
        $current = $startDate->copy()->startOfMonth();
        
        while ($current <= $endDate) {
            $orders = OrderRequest::where('branch_id', $branchId)
                ->whereMonth('created_at', $current->month)
                ->whereYear('created_at', $current->year)
                ->get();

            $monthlyStats[] = [
                'month' => $current->format('m/Y'),
                'total_orders' => $orders->count(),
                'approved_orders' => $orders->where('status', 'approved')->count(),
                'completed_orders' => $orders->where('status', 'completed')->count(),
                'total_value' => $orders->where('status', 'completed')->sum('total_amount'),
                'success_rate' => $orders->count() > 0 ?
                    round(($orders->whereIn('status', ['approved', 'completed'])->count() / $orders->count()) * 100, 1) : 0,
            ];
            
            $current->addMonth();
        }

        return view('branch.reports.performance', compact('monthlyStats', 'startDate', 'endDate'));
    }
}