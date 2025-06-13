<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Book;
use App\Models\Category;
use App\Services\InventoryService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function index(Request $request)
    {
        $query = Inventory::with(['book.category']);

        // Lọc theo danh mục
        if ($request->has('category_id') && !empty($request->category_id)) {
            $query->whereHas('book', function($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        // Lọc theo trạng thái tồn kho
        if ($request->has('stock_status') && !empty($request->stock_status)) {
            switch ($request->stock_status) {
                case 'out_of_stock':
                    $query->where('available_quantity', '<=', 0);
                    break;
                case 'low_stock':
                    $query->where('available_quantity', '>', 0)
                          ->where('available_quantity', '<=', 10);
                    break;
                case 'in_stock':
                    $query->where('available_quantity', '>', 10);
                    break;
            }
        }

        // Tìm kiếm theo tên sách
        if ($request->has('search') && !empty($request->search)) {
            $query->whereHas('book', function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('isbn', 'like', '%' . $request->search . '%');
            });
        }

        // Sắp xếp
        $sortBy = $request->get('sort', 'available_quantity');
        $sortOrder = $request->get('order', 'asc');
        
        if ($sortBy === 'book_title') {
            $query->join('books', 'inventories.book_id', '=', 'books.id')
                  ->orderBy('books.title', $sortOrder)
                  ->select('inventories.*');
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $inventories = $query->paginate(20)->appends($request->all());

        // Dữ liệu cho form lọc
        $categories = Category::where('is_active', true)->get();
        
        // Thống kê nhanh
        $quick_stats = [
            'total_books' => Inventory::count(),
            'out_of_stock' => Inventory::where('available_quantity', '<=', 0)->count(),
            'low_stock' => Inventory::where('available_quantity', '>', 0)
                                  ->where('available_quantity', '<=', 10)->count(),
            'total_value' => Inventory::with('book')->get()->sum(function($inv) {
                return $inv->quantity * $inv->book->price;
            }),
        ];

        return view('manager.inventory.index', compact(
            'inventories', 'categories', 'quick_stats'
        ));
    }

    public function lowStock()
    {
        $lowStockBooks = $this->inventoryService->getLowStockBooks(10);
        
        return view('manager.inventory.low-stock', compact('lowStockBooks'));
    }

    public function report()
    {
        $report = $this->inventoryService->getInventoryReport();
        
        // Phân tích theo danh mục
        $categoryAnalysis = $report->groupBy('category')->map(function($items) {
            return [
                'total_books' => $items->count(),
                'total_quantity' => $items->sum('quantity'),
                'total_value' => $items->sum(function($item) {
                    return $item['quantity'] * $item['unit_price'];
                }),
                'low_stock_count' => $items->where('available_quantity', '<=', 10)->count(),
            ];
        });

        // Top sách có giá trị tồn kho cao nhất
        $topValueBooks = $report->sortByDesc(function($item) {
            return $item['quantity'] * $item['unit_price'];
        })->take(10);

        // Sách cần nhập gấp
        $urgentBooks = $report->where('available_quantity', '<=', 5)
                             ->sortBy('available_quantity')
                             ->take(20);

        return view('manager.inventory.report', compact(
            'report', 'categoryAnalysis', 'topValueBooks', 'urgentBooks'
        ));
    }

    public function forecast(Request $request)
    {
        // Dự báo nhu cầu tồn kho dựa trên lịch sử đặt hàng
        $months = $request->get('months', 3); // Số tháng để dự báo
        
        $forecast = Book::with(['orderRequestDetails', 'inventory'])
            ->whereHas('orderRequestDetails')
            ->get()
            ->map(function($book) use ($months) {
                $recentOrders = $book->orderRequestDetails()
                    ->whereHas('orderRequest', function($q) {
                        $q->where('status', 'completed')
                          ->where('created_at', '>=', now()->subMonths(6));
                    })
                    ->get();

                $avgMonthlyDemand = $recentOrders->count() > 0 ? $recentOrders->sum('quantity') / 6 : 0;
                $forecastDemand = $avgMonthlyDemand * $months;
                $currentStock = $book->inventory ? $book->inventory->available_quantity : 0;
                
                return [
                    'book' => $book,
                    'current_stock' => $currentStock,
                    'avg_monthly_demand' => round($avgMonthlyDemand, 2),
                    'forecast_demand' => round($forecastDemand, 2),
                    'recommended_order' => max(0, ceil($forecastDemand - $currentStock)),
                    'stock_out_risk' => $currentStock < $forecastDemand ? 'High' : 'Low',
                ];
            })
            ->where('avg_monthly_demand', '>', 0)
            ->sortByDesc('recommended_order');

        return view('manager.inventory.forecast', compact('forecast', 'months'));
    }

    public function movements(Request $request)
    {
        // Lịch sử biến động tồn kho
        $bookId = $request->book_id;
        $movements = collect();

        if ($bookId) {
            $book = Book::with('inventory')->find($bookId);
            
            if ($book) {
                // Lấy lịch sử từ các bảng liên quan
                $imports = $book->importDetails()
                    ->whereHas('import.supplier', function($q) {
                        $q->where('is_active', true);
                    })
                    ->whereHas('import', function($q) {
                        $q->where('status', 'received');
                    })
                    ->with(['import.supplier'])
                    ->get()
                    ->map(function($detail) {
                        return [
                            'type' => 'import',
                            'date' => $detail->import->received_at,
                            'quantity' => $detail->quantity,
                            'reference' => $detail->import->code,
                            'note' => "Nhập từ {$detail->import->supplier->name}",
                            'balance_after' => null, // Sẽ tính sau
                        ];
                    });

                $distributions = $book->distributionDetails()
                    ->whereHas('distribution', function($q) {
                        $q->where('status', 'delivered');
                    })
                    ->with(['distribution.branch'])
                    ->get()
                    ->map(function($detail) {
                        return [
                            'type' => 'distribution',
                            'date' => $detail->distribution->delivered_at,
                            'quantity' => -$detail->quantity,
                            'reference' => $detail->distribution->code,
                            'note' => "Xuất cho {$detail->distribution->branch->name}",
                            'balance_after' => null, // Sẽ tính sau
                        ];
                    });

                $movements = $imports->concat($distributions)
                                   ->sortByDesc('date')
                                   ->take(50)
                                   ->values();
            }
        }

        $books = Book::where('is_active', true)->orderBy('title')->get();

        return view('manager.inventory.movements', compact('movements', 'books', 'bookId'));
    }

    public function alerts()
    {
        $alerts = [
            'out_of_stock' => Inventory::with('book')
                ->where('available_quantity', '<=', 0)
                ->get(),
            'low_stock' => Inventory::with('book')
                ->where('available_quantity', '>', 0)
                ->where('available_quantity', '<=', 10)
                ->get(),
            'overstock' => Inventory::with('book')
                ->where('available_quantity', '>', 100)
                ->get(),
            'negative_stock' => Inventory::with('book')
                ->where('available_quantity', '<', 0)
                ->get(),
        ];

        // Sách có reserved quantity cao
        $alerts['high_reserved'] = Inventory::with('book')
            ->where('reserved_quantity', '>', 0)
            ->where('reserved_quantity', '>=', 'available_quantity')
            ->get();

        return view('manager.inventory.alerts', compact('alerts'));
    }

    public function export(Request $request)
    {
        // Export inventory to Excel
        // Implement export functionality here
        return back()->with('success', 'Đã xuất báo cáo tồn kho thành công.');
    }

    public function adjustStock(Request $request, Inventory $inventory)
    {
        $request->validate([
            'adjustment_type' => 'required|in:increase,decrease',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:255',
        ], [
            'adjustment_type.required' => 'Loại điều chỉnh là bắt buộc.',
            'quantity.required' => 'Số lượng là bắt buộc.',
            'quantity.min' => 'Số lượng phải lớn hơn 0.',
            'reason.required' => 'Lý do điều chỉnh là bắt buộc.',
        ]);

        $oldQuantity = $inventory->quantity;
        
        if ($request->adjustment_type === 'increase') {
            $inventory->addStock($request->quantity);
            $newQuantity = $inventory->quantity;
        } else {
            $inventory->removeStock($request->quantity);
            $newQuantity = $inventory->quantity;
        }

        // Log the adjustment
        \Log::info("Stock adjusted by manager", [
            'user_id' => auth()->id(),
            'book_id' => $inventory->book_id,
            'book_title' => $inventory->book->title,
            'old_quantity' => $oldQuantity,
            'new_quantity' => $newQuantity,
            'adjustment_type' => $request->adjustment_type,
            'adjustment_quantity' => $request->quantity,
            'reason' => $request->reason,
        ]);

        return back()->with('success', 'Đã điều chỉnh tồn kho thành công.');
    }

    public function stockHistory(Request $request)
    {
        $query = Inventory::with(['book.category']);

        // Lọc theo thời gian
        if ($request->has('date_from') && !empty($request->date_from)) {
            $query->where('updated_at', '>=', $request->date_from);
        }
        if ($request->has('date_to') && !empty($request->date_to)) {
            $query->where('updated_at', '<=', $request->date_to . ' 23:59:59');
        }

        // Lọc theo danh mục
        if ($request->has('category_id') && !empty($request->category_id)) {
            $query->whereHas('book', function($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        $inventories = $query->orderBy('updated_at', 'desc')
                           ->paginate(20)
                           ->appends($request->all());

        $categories = Category::where('is_active', true)->get();

        return view('manager.inventory.history', compact('inventories', 'categories'));
    }

    public function stockValuation()
    {
        $valuations = Inventory::with(['book.category'])
            ->get()
            ->map(function($inventory) {
                return [
                    'book_id' => $inventory->book_id,
                    'book_title' => $inventory->book->title,
                    'category' => $inventory->book->category->name,
                    'quantity' => $inventory->quantity,
                    'unit_price' => $inventory->book->price,
                    'total_value' => $inventory->quantity * $inventory->book->price,
                    'available_quantity' => $inventory->available_quantity,
                    'reserved_quantity' => $inventory->reserved_quantity,
                ];
            })
            ->sortByDesc('total_value');

        $summary = [
            'total_books' => $valuations->count(),
            'total_quantity' => $valuations->sum('quantity'),
            'total_value' => $valuations->sum('total_value'),
            'avg_unit_price' => $valuations->avg('unit_price'),
        ];

        $categoryValuation = $valuations->groupBy('category')->map(function($items) {
            return [
                'total_books' => $items->count(),
                'total_quantity' => $items->sum('quantity'),
                'total_value' => $items->sum('total_value'),
                'percentage' => 0, // Will calculate in view
            ];
        });

        return view('manager.inventory.valuation', compact(
            'valuations', 'summary', 'categoryValuation'
        ));
    }

    public function reorderReport()
    {
        // Báo cáo đề xuất đặt hàng
        $reorderBooks = Inventory::with(['book.category'])
            ->where('available_quantity', '<=', 10)
            ->get()
            ->map(function($inventory) {
                // Tính lượng đặt hàng đề xuất dựa trên lịch sử
                $avgMonthlyDemand = $inventory->book->orderRequestDetails()
                    ->whereHas('orderRequest', function($q) {
                        $q->where('status', 'completed')
                          ->where('created_at', '>=', now()->subMonths(3));
                    })
                    ->sum('quantity') / 3;

                $recommendedQuantity = max(50, ceil($avgMonthlyDemand * 2)); // 2 months supply

                return [
                    'book' => $inventory->book,
                    'current_stock' => $inventory->available_quantity,
                    'reserved_stock' => $inventory->reserved_quantity,
                    'avg_monthly_demand' => round($avgMonthlyDemand, 2),
                    'recommended_quantity' => $recommendedQuantity,
                    'priority' => $inventory->available_quantity <= 0 ? 'Urgent' : 
                                 ($inventory->available_quantity <= 5 ? 'High' : 'Medium'),
                ];
            })
            ->sortBy('current_stock');

        return view('manager.inventory.reorder', compact('reorderBooks'));
    }
}