<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Book;
use App\Models\User;
use App\Models\OrderRequest;
use App\Models\Distribution;
use App\Models\Import;
use App\Models\Inventory;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        $summary = [
            'total_orders_this_month' => OrderRequest::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'total_revenue_this_month' => OrderRequest::with('details')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->where('status', 'completed')
                ->get()
                ->sum(function($order) {
                    return $order->details->sum('total_price');
                }),
            'total_books_distributed' => Distribution::where('status', 'delivered')
                ->with('details')
                ->get()
                ->sum(function($dist) {
                    return $dist->details->sum('quantity');
                }),
            'total_inventory_value' => Inventory::with('book')->get()->sum(function($inv) {
                return $inv->quantity * $inv->book->price;
            }),
        ];

        return view('admin.reports.index', compact('summary'));
    }

    public function orderReport(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();
        $branchId = $request->branch_id;
        $status = $request->status;

        $query = OrderRequest::with(['branch', 'creator', 'details.book'])
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $orders = $query->get();

        $summary = [
            'total_orders' => $orders->count(),
            'pending_orders' => $orders->where('status', 'pending')->count(),
            'approved_orders' => $orders->where('status', 'approved')->count(),
            'rejected_orders' => $orders->where('status', 'rejected')->count(),
            'completed_orders' => $orders->where('status', 'completed')->count(),
            'total_value' => $orders->sum('total_amount'),
            'average_order_value' => $orders->count() > 0 ? $orders->sum('total_amount') / $orders->count() : 0,
        ];

        $ordersByBranch = $orders->groupBy('branch.name')->map(function($branchOrders) {
            return [
                'count' => $branchOrders->count(),
                'value' => $branchOrders->sum('total_amount')
            ];
        });

        $ordersByStatus = $orders->groupBy('status')->map->count();

        $ordersByMonth = $orders->groupBy(function($order) {
            return $order->created_at->format('Y-m');
        })->map->count();

        $branches = Branch::where('is_active', true)->get();

        return view('admin.reports.orders', compact(
            'orders', 'summary', 'ordersByBranch', 'ordersByStatus', 'ordersByMonth',
            'branches', 'startDate', 'endDate', 'branchId', 'status'
        ));
    }

    public function inventoryReport()
    {
        $inventories = Inventory::with(['book.category'])
            ->get()
            ->map(function($inventory) {
                return [
                    'book_id' => $inventory->book_id,
                    'book_title' => $inventory->book->title,
                    'category' => $inventory->book->category->name,
                    'isbn' => $inventory->book->isbn,
                    'quantity' => $inventory->quantity,
                    'reserved_quantity' => $inventory->reserved_quantity,
                    'available_quantity' => $inventory->available_quantity,
                    'unit_price' => $inventory->book->price,
                    'total_value' => $inventory->quantity * $inventory->book->price,
                    'status' => $inventory->available_quantity <= 0 ? 'Hết hàng' : 
                               ($inventory->available_quantity <= 10 ? 'Sắp hết' : 'Đủ hàng')
                ];
            });

        $summary = [
            'total_books' => $inventories->count(),
            'total_quantity' => $inventories->sum('quantity'),
            'total_value' => $inventories->sum('total_value'),
            'low_stock_count' => $inventories->where('available_quantity', '<=', 10)->count(),
            'out_of_stock_count' => $inventories->where('quantity', '<=', 0)->count(),
            'average_stock_value' => $inventories->count() > 0 ? $inventories->sum('total_value') / $inventories->count() : 0,
        ];

        $stockByCategory = $inventories->groupBy('category')->map(function($items) {
            return [
                'count' => $items->count(),
                'quantity' => $items->sum('quantity'),
                'value' => $items->sum('total_value'),
            ];
        });

        return view('admin.reports.inventory', compact('inventories', 'summary', 'stockByCategory'));
    }

    public function branchPerformanceReport(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        $branchStats = Branch::withCount([
            'orderRequests' => function($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            },
            'orderRequests as approved_orders_count' => function($query) use ($startDate, $endDate) {
                $query->where('status', 'approved')
                      ->whereBetween('created_at', [$startDate, $endDate]);
            },
            'orderRequests as completed_orders_count' => function($query) use ($startDate, $endDate) {
                $query->where('status', 'completed')
                      ->whereBetween('created_at', [$startDate, $endDate]);
            }
        ])->get()->map(function($branch) use ($startDate, $endDate) {
            $orders = $branch->orderRequests()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->with('details')
                ->get();

            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'code' => $branch->code,
                'total_orders' => $orders->count(),
                'approved_orders' => $orders->where('status', 'approved')->count(),
                'completed_orders' => $orders->where('status', 'completed')->count(),
                'rejected_orders' => $orders->where('status', 'rejected')->count(),
                'total_value' => $orders->sum(function($order) {
                    return $order->details->sum('total_price');
                }),
                'avg_order_value' => $orders->count() > 0 ? 
                    $orders->sum(function($order) {
                        return $order->details->sum('total_price');
                    }) / $orders->count() : 0,
                'approval_rate' => $orders->count() > 0 ? 
                    ($orders->where('status', 'approved')->count() / $orders->count()) * 100 : 0,
                'completion_rate' => $orders->where('status', 'approved')->count() > 0 ?
                    ($orders->where('status', 'completed')->count() / $orders->where('status', 'approved')->count()) * 100 : 0,
            ];
        });

        return view('admin.reports.branch-performance', compact('branchStats', 'startDate', 'endDate'));
    }

    public function supplierReport(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        $supplierStats = Supplier::with(['imports' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate])
                  ->where('status', 'received');
        }])->get()->map(function($supplier) {
            return [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'code' => $supplier->code,
                'total_imports' => $supplier->imports->count(),
                'total_value' => $supplier->imports->sum('total_amount'),
                'total_quantity' => $supplier->imports->sum(function($import) {
                    return $import->details->sum('quantity');
                }),
                'avg_delivery_time' => $supplier->imports->filter(function($import) {
                    return $import->received_at && $import->confirmed_at;
                })->avg(function($import) {
                    return $import->confirmed_at->diffInDays($import->received_at);
                }) ?? 0,
                'on_time_delivery_rate' => $supplier->imports->count() > 0 ?
                    ($supplier->imports->where('status', 'received')->count() / $supplier->imports->count()) * 100 : 0,
            ];
        })->where('total_imports', '>', 0);

        $summary = [
            'total_suppliers' => $supplierStats->count(),
            'total_imports' => $supplierStats->sum('total_imports'),
            'total_value' => $supplierStats->sum('total_value'),
            'avg_import_value' => $supplierStats->count() > 0 ? $supplierStats->sum('total_value') / $supplierStats->sum('total_imports') : 0,
            'avg_delivery_time' => $supplierStats->avg('avg_delivery_time'),
        ];

        return view('admin.reports.suppliers', compact('supplierStats', 'summary', 'startDate', 'endDate'));
    }

    public function exportOrderReport(Request $request)
    {
        // Implement export functionality (Excel, PDF, etc.)
        return back()->with('success', 'Báo cáo đã được xuất thành công.');
    }

    public function exportInventoryReport()
    {
        // Implement export functionality
        return back()->with('success', 'Báo cáo tồn kho đã được xuất thành công.');
    }
}