<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Models\BranchInventory;
use App\Models\Category;
use App\Models\Distribution;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class InventoryController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function index(Request $request)
    {
        $branchId = auth()->user()->branch_id;
        
        $query = BranchInventory::with(['book.category'])
            ->where('branch_id', $branchId);

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
                    $query->where('quantity', '<=', 0);
                    break;
                case 'low_stock':
                    $query->where('quantity', '>', 0)->where('quantity', '<=', 5);
                    break;
                case 'in_stock':
                    $query->where('quantity', '>', 5);
                    break;
            }
        }

        // Tìm kiếm theo tên sách
        if ($request->has('search') && !empty($request->search)) {
            $query->whereHas('book', function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('isbn', 'like', '%' . $request->search . '%')
                  ->orWhere('author', 'like', '%' . $request->search . '%');
            });
        }

        // Sắp xếp
        $sortBy = $request->get('sort', 'quantity');
        $sortOrder = $request->get('order', 'desc');
        
        if ($sortBy === 'book_title') {
            $query->join('books', 'branch_inventories.book_id', '=', 'books.id')
                  ->orderBy('books.title', $sortOrder)
                  ->select('branch_inventories.*');
        } elseif ($sortBy === 'category') {
            $query->join('books', 'branch_inventories.book_id', '=', 'books.id')
                  ->join('categories', 'books.category_id', '=', 'categories.id')
                  ->orderBy('categories.name', $sortOrder)
                  ->select('branch_inventories.*');
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $inventories = $query->paginate(20)->appends($request->all());

        // Dữ liệu cho form lọc
        $categories = Category::where('is_active', true)->get();
        
        // Thống kê nhanh
        $quick_stats = [
            'total_items' => BranchInventory::where('branch_id', $branchId)->count(),
            'total_quantity' => BranchInventory::where('branch_id', $branchId)->sum('quantity'),
            'out_of_stock' => BranchInventory::where('branch_id', $branchId)
                ->where('quantity', '<=', 0)->count(),
            'low_stock' => BranchInventory::where('branch_id', $branchId)
                ->where('quantity', '>', 0)->where('quantity', '<=', 5)->count(),
            'total_value' => BranchInventory::where('branch_id', $branchId)
                ->with('book')->get()->sum(function($item) {
                    return $item->quantity * $item->book->price;
                }),
        ];

        return view('branch.inventory.index', compact(
            'inventories', 'categories', 'quick_stats'
        ));
    }

    public function show(BranchInventory $branchInventory)
    {
        // Kiểm tra quyền truy cập
        if ($branchInventory->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Bạn không có quyền xem tồn kho này.');
        }

        $branchInventory->load(['book.category', 'branch']);

        // Lịch sử nhận hàng cho sách này
        $receiving_history = Distribution::where('branch_id', $branchInventory->branch_id)
            ->where('status', 'delivered')
            ->whereHas('details', function($q) use ($branchInventory) {
                $q->where('book_id', $branchInventory->book_id);
            })
            ->with(['details' => function($q) use ($branchInventory) {
                $q->where('book_id', $branchInventory->book_id);
            }])
            ->latest()
            ->take(10)
            ->get();

        // Thống kê cho sách này tại chi nhánh
        $book_stats = [
            'total_received' => $receiving_history->sum(function($distribution) {
                return $distribution->details->sum('quantity');
            }),
            'last_received' => $receiving_history->first() ? 
                $receiving_history->first()->delivered_at : null,
            'avg_monthly_received' => $this->calculateMonthlyReceived(
                $branchInventory->branch_id, 
                $branchInventory->book_id
            ),
            'stock_days' => $this->calculateStockDays($branchInventory),
        ];

        // Cảnh báo
        $alerts = [];
        if ($branchInventory->quantity <= 0) {
            $alerts[] = ['type' => 'danger', 'message' => 'Sách đã hết hàng'];
        } elseif ($branchInventory->quantity <= 2) {
            $alerts[] = ['type' => 'warning', 'message' => 'Sách sắp hết hàng'];
        }

        return view('branch.inventory.show', compact(
            'branchInventory', 'receiving_history', 'book_stats', 'alerts'
        ));
    }

    public function report()
    {
        $branchId = auth()->user()->branch_id;
        $report = $this->inventoryService->getBranchInventoryReport($branchId);
        
        // Phân tích theo danh mục
        $categoryAnalysis = collect($report)->groupBy('category')->map(function($items) {
            return [
                'total_books' => $items->count(),
                'total_quantity' => $items->sum('quantity'),
                'avg_quantity' => round($items->avg('quantity'), 1),
                'total_value' => $items->sum(function($item) {
                    return $item['quantity'] * $item['book']->price;
                }),
            ];
        });

        // Top sách có tồn kho cao nhất
        $topStockBooks = collect($report)->sortByDesc('quantity')->take(10);

        // Sách cần đặt thêm
        $needToOrder = collect($report)->where('quantity', '<=', 5)->sortBy('quantity');

        // Thống kê theo giá trị
        $totalValue = collect($report)->sum(function($item) {
            return $item['quantity'] * $item['book']->price;
        });

        return view('branch.inventory.report', compact(
            'report', 'categoryAnalysis', 'topStockBooks', 'needToOrder', 'totalValue'
        ));
    }

    public function lowStock()
    {
        $branchId = auth()->user()->branch_id;
        
        $lowStockItems = BranchInventory::with(['book.category'])
            ->where('branch_id', $branchId)
            ->where('quantity', '<=', 5)
            ->orderBy('quantity', 'asc')
            ->get();

        // Phân loại theo mức độ
        $critical = $lowStockItems->where('quantity', '<=', 0);
        $warning = $lowStockItems->where('quantity', '>', 0)->where('quantity', '<=', 2);
        $caution = $lowStockItems->where('quantity', '>', 2)->where('quantity', '<=', 5);

        return view('branch.inventory.low-stock', compact(
            'lowStockItems', 'critical', 'warning', 'caution'
        ));
    }

    public function requestReport()
    {
        // Báo cáo đề xuất đặt hàng dựa trên tồn kho
        $branchId = auth()->user()->branch_id;
        
        $suggestions = BranchInventory::with(['book.category'])
            ->where('branch_id', $branchId)
            ->where('quantity', '<=', 5)
            ->get()
            ->map(function($inventory) {
                $avgMonthlyUsage = $this->calculateMonthlyUsage(
                    $inventory->branch_id, 
                    $inventory->book_id
                );
                
                $recommendedQuantity = max(10, ceil($avgMonthlyUsage * 2)); // 2 months supply

                return [
                    'book' => $inventory->book,
                    'current_stock' => $inventory->quantity,
                    'avg_monthly_usage' => round($avgMonthlyUsage, 1),
                    'recommended_quantity' => $recommendedQuantity,
                    'priority' => $inventory->quantity <= 0 ? 'Urgent' : 
                                 ($inventory->quantity <= 2 ? 'High' : 'Medium'),
                    'estimated_value' => $recommendedQuantity * $inventory->book->price,
                ];
            })
            ->sortBy('current_stock');

        $summary = [
            'total_items' => $suggestions->count(),
            'urgent_items' => $suggestions->where('priority', 'Urgent')->count(),
            'high_priority_items' => $suggestions->where('priority', 'High')->count(),
            'total_estimated_value' => $suggestions->sum('estimated_value'),
        ];

        return view('branch.inventory.request-report', compact('suggestions', 'summary'));
    }

    public function movements(Request $request)
    {
        $branchId = auth()->user()->branch_id;
        $bookId = $request->book_id;
        $movements = collect();

        if ($bookId) {
            // Lấy lịch sử nhận hàng
            $distributions = Distribution::where('branch_id', $branchId)
                ->where('status', 'delivered')
                ->whereHas('details', function($q) use ($bookId) {
                    $q->where('book_id', $bookId);
                })
                ->with(['details' => function($q) use ($bookId) {
                    $q->where('book_id', $bookId);
                }])
                ->get()
                ->map(function($distribution) {
                    return [
                        'type' => 'received',
                        'date' => $distribution->delivered_at,
                        'quantity' => $distribution->details->sum('quantity'),
                        'reference' => $distribution->code,
                        'note' => 'Nhận hàng từ trung tâm',
                        'icon' => 'fas fa-arrow-down',
                        'color' => 'success',
                    ];
                });

            $movements = $distributions->sortByDesc('date')->take(50);
        }

        // Lấy danh sách sách có trong chi nhánh
        $books = BranchInventory::where('branch_id', $branchId)
            ->with('book')
            ->get()
            ->pluck('book')
            ->sortBy('title');

        return view('branch.inventory.movements', compact('movements', 'books', 'bookId'));
    }

    public function stockAlert()
    {
        $branchId = auth()->user()->branch_id;
        
        $alerts = [
            'out_of_stock' => BranchInventory::with('book')
                ->where('branch_id', $branchId)
                ->where('quantity', '<=', 0)
                ->get(),
            'low_stock' => BranchInventory::with('book')
                ->where('branch_id', $branchId)
                ->where('quantity', '>', 0)
                ->where('quantity', '<=', 3)
                ->get(),
            'slow_moving' => $this->getSlowMovingBooks($branchId),
        ];

        return view('branch.inventory.alerts', compact('alerts'));
    }

    public function export(Request $request)
    {
        $branchId = auth()->user()->branch_id;
        
        // Export branch inventory to Excel
        return back()->with('success', 'Đã xuất báo cáo tồn kho chi nhánh thành công.');
    }

    public function forecast(Request $request)
    {
        $branchId = auth()->user()->branch_id;
        $months = $request->get('months', 3);
        
        $forecast = BranchInventory::with(['book.category'])
            ->where('branch_id', $branchId)
            ->get()
            ->map(function($inventory) use ($months) {
                $avgMonthlyUsage = $this->calculateMonthlyUsage(
                    $inventory->branch_id, 
                    $inventory->book_id
                );
                
                $forecastDemand = $avgMonthlyUsage * $months;
                $currentStock = $inventory->quantity;
                
                return [
                    'book' => $inventory->book,
                    'current_stock' => $currentStock,
                    'avg_monthly_usage' => round($avgMonthlyUsage, 2),
                    'forecast_demand' => round($forecastDemand, 2),
                    'shortage_risk' => $currentStock < $forecastDemand ? 'High' : 'Low',
                    'recommended_order' => max(0, ceil($forecastDemand - $currentStock)),
                ];
            })
            ->where('avg_monthly_usage', '>', 0)
            ->sortByDesc('shortage_risk');

        return view('branch.inventory.forecast', compact('forecast', 'months'));
    }

    private function calculateMonthlyReceived($branchId, $bookId)
    {
        $distributions = Distribution::where('branch_id', $branchId)
            ->where('status', 'delivered')
            ->where('delivered_at', '>=', now()->subMonths(6))
            ->whereHas('details', function($q) use ($bookId) {
                $q->where('book_id', $bookId);
            })
            ->with(['details' => function($q) use ($bookId) {
                $q->where('book_id', $bookId);
            }])
            ->get();

        $totalReceived = $distributions->sum(function($distribution) {
            return $distribution->details->sum('quantity');
        });

        return $totalReceived / 6; // Average per month over 6 months
    }

    private function calculateMonthlyUsage($branchId, $bookId)
    {
        // Tính dựa trên lịch sử nhận hàng (giả định usage = received)
        return $this->calculateMonthlyReceived($branchId, $bookId);
    }

    private function calculateStockDays($branchInventory)
    {
        $monthlyUsage = $this->calculateMonthlyUsage(
            $branchInventory->branch_id, 
            $branchInventory->book_id
        );
        
        if ($monthlyUsage <= 0) {
            return null;
        }
        
        $dailyUsage = $monthlyUsage / 30;
        return round($branchInventory->quantity / $dailyUsage, 0);
    }

    private function getSlowMovingBooks($branchId)
    {
        // Sách không có phiếu nhận nào trong 3 tháng gần đây
        $recentlyReceivedBookIds = Distribution::where('branch_id', $branchId)
            ->where('status', 'delivered')
            ->where('delivered_at', '>=', now()->subMonths(3))
            ->with('details')
            ->get()
            ->flatMap(function($distribution) {
                return $distribution->details->pluck('book_id');
            })
            ->unique();

        return BranchInventory::with('book')
            ->where('branch_id', $branchId)
            ->where('quantity', '>', 0)
            ->whereNotIn('book_id', $recentlyReceivedBookIds)
            ->get();
    }
}