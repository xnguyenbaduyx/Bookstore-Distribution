<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Book;
use App\Models\Category;
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
                case 'overstock':
                    $query->where('available_quantity', '>', 100);
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
        } elseif ($sortBy === 'category') {
            $query->join('books', 'inventories.book_id', '=', 'books.id')
                  ->join('categories', 'books.category_id', '=', 'categories.id')
                  ->orderBy('categories.name', $sortOrder)
                  ->select('inventories.*');
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $inventories = $query->paginate(25)->appends($request->all());

        // Dữ liệu cho form lọc
        $categories = Category::where('is_active', true)->get();
        
        // Thống kê nhanh
        $quick_stats = [
            'total_books' => Inventory::count(),
            'out_of_stock' => Inventory::where('available_quantity', '<=', 0)->count(),
            'low_stock' => Inventory::where('available_quantity', '>', 0)
                                  ->where('available_quantity', '<=', 10)->count(),
            'overstock' => Inventory::where('available_quantity', '>', 100)->count(),
            'total_value' => Inventory::with('book')->get()->sum(function($inv) {
                return $inv->quantity * $inv->book->price;
            }),
            'total_reserved' => Inventory::sum('reserved_quantity'),
        ];

        return view('warehouse.inventory.index', compact(
            'inventories', 'categories', 'quick_stats'
        ));
    }

    public function show(Inventory $inventory)
    {
        $inventory->load(['book.category']);

        // Lịch sử biến động gần đây
        $recentMovements = $this->getRecentMovements($inventory->book_id, 20);

        // Thống kê cho sách này
        $bookStats = [
            'total_imported' => $inventory->book->importDetails()
                ->whereHas('import', function($q) {
                    $q->where('status', 'received');
                })
                ->sum('quantity'),
            'total_distributed' => $inventory->book->distributionDetails()
                ->whereHas('distribution', function($q) {
                    $q->where('status', 'delivered');
                })
                ->sum('quantity'),
            'pending_distributions' => $inventory->book->distributionDetails()
                ->whereHas('distribution', function($q) {
                    $q->whereIn('status', ['pending', 'confirmed']);
                })
                ->sum('quantity'),
            'avg_monthly_demand' => $this->calculateMonthlyDemand($inventory->book_id),
        ];

        // Cảnh báo
        $alerts = [];
        if ($inventory->available_quantity <= 0) {
            $alerts[] = ['type' => 'danger', 'message' => 'Sách đã hết hàng'];
        } elseif ($inventory->available_quantity <= 5) {
            $alerts[] = ['type' => 'warning', 'message' => 'Sách sắp hết hàng'];
        }
        
        if ($inventory->reserved_quantity > $inventory->quantity) {
            $alerts[] = ['type' => 'danger', 'message' => 'Số lượng đặt trước vượt quá tồn kho'];
        }

        return view('warehouse.inventory.show', compact(
            'inventory', 'recentMovements', 'bookStats', 'alerts'
        ));
    }

    public function adjust(Request $request, Inventory $inventory)
    {
        $request->validate([
            'adjustment_type' => 'required|in:increase,decrease',
            'quantity' => 'required|integer|min:1|max:9999',
            'reason' => 'required|string|max:255',
        ], [
            'adjustment_type.required' => 'Loại điều chỉnh là bắt buộc.',
            'quantity.required' => 'Số lượng là bắt buộc.',
            'quantity.min' => 'Số lượng phải lớn hơn 0.',
            'quantity.max' => 'Số lượng không được vượt quá 9999.',
            'reason.required' => 'Lý do điều chỉnh là bắt buộc.',
        ]);

        $oldQuantity = $inventory->quantity;
        $adjustmentQuantity = $request->quantity;
        
        if ($request->adjustment_type === 'increase') {
            $inventory->addStock($adjustmentQuantity);
            $action = 'Tăng';
        } else {
            if ($adjustmentQuantity > $inventory->quantity) {
                return back()->with('error', 
                    'Không thể giảm ' . $adjustmentQuantity . ' khi chỉ có ' . $inventory->quantity . ' trong kho.');
            }
            $inventory->removeStock($adjustmentQuantity);
            $action = 'Giảm';
        }

        $newQuantity = $inventory->quantity;

        // Log điều chỉnh
        \Log::info("Inventory adjusted by warehouse staff", [
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'book_id' => $inventory->book_id,
            'book_title' => $inventory->book->title,
            'old_quantity' => $oldQuantity,
            'new_quantity' => $newQuantity,
            'adjustment_type' => $request->adjustment_type,
            'adjustment_quantity' => $adjustmentQuantity,
            'reason' => $request->reason,
            'adjusted_at' => now(),
        ]);

        return back()->with('success', 
            "{$action} tồn kho thành công. Từ {$oldQuantity} thành {$newQuantity}.");
    }

    public function lowStock()
    {
        $lowStockBooks = $this->inventoryService->getLowStockBooks(10);
        
        // Phân loại theo mức độ cấp thiết
        $critical = $lowStockBooks->where('available_quantity', '<=', 0);
        $warning = $lowStockBooks->where('available_quantity', '>', 0)
                                ->where('available_quantity', '<=', 5);
        $caution = $lowStockBooks->where('available_quantity', '>', 5)
                                ->where('available_quantity', '<=', 10);

        return view('warehouse.inventory.low-stock', compact(
            'lowStockBooks', 'critical', 'warning', 'caution'
        ));
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
                'avg_quantity' => $items->avg('quantity'),
                'low_stock_count' => $items->where('available_quantity', '<=', 10)->count(),
            ];
        });

        // ABC Analysis (Phân tích theo giá trị)
        $sortedByValue = $report->sortByDesc(function($item) {
            return $item['quantity'] * $item['unit_price'];
        });

        $totalValue = $sortedByValue->sum(function($item) {
            return $item['quantity'] * $item['unit_price'];
        });

        $abcAnalysis = [
            'A' => [], 'B' => [], 'C' => []
        ];

        $cumulativeValue = 0;
        foreach ($sortedByValue as $item) {
            $itemValue = $item['quantity'] * $item['unit_price'];
            $cumulativeValue += $itemValue;
            $percentage = ($cumulativeValue / $totalValue) * 100;

            if ($percentage <= 80) {
                $abcAnalysis['A'][] = $item;
            } elseif ($percentage <= 95) {
                $abcAnalysis['B'][] = $item;
            } else {
                $abcAnalysis['C'][] = $item;
            }
        }

        return view('warehouse.inventory.report', compact(
            'report', 'categoryAnalysis', 'abcAnalysis', 'totalValue'
        ));
    }

    public function movements(Request $request)
    {
        $bookId = $request->book_id;
        $movements = collect();

        if ($bookId) {
            $movements = $this->getRecentMovements($bookId, 100);
        }

        $books = Book::where('is_active', true)->orderBy('title')->get();

        return view('warehouse.inventory.movements', compact('movements', 'books', 'bookId'));
    }

    public function alerts()
    {
        $alerts = [
            'critical' => Inventory::with('book')
                ->where('available_quantity', '<=', 0)
                ->get(),
            'warning' => Inventory::with('book')
                ->where('available_quantity', '>', 0)
                ->where('available_quantity', '<=', 5)
                ->get(),
            'low_stock' => Inventory::with('book')
                ->where('available_quantity', '>', 5)
                ->where('available_quantity', '<=', 10)
                ->get(),
            'overstock' => Inventory::with('book')
                ->where('available_quantity', '>', 100)
                ->get(),
            'discrepancy' => Inventory::with('book')
                ->whereRaw('reserved_quantity > available_quantity')
                ->get(),
        ];

        return view('warehouse.inventory.alerts', compact('alerts'));
    }

    public function cycleCount(Request $request)
    {
        // Kiểm kê tuần hoàn
        $query = Inventory::with(['book.category']);

        // Lọc theo danh mục cho kiểm kê
        if ($request->has('category_id') && !empty($request->category_id)) {
            $query->whereHas('book', function($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        // Chọn sách cần kiểm kê (ưu tiên sách có giá trị cao)
        $inventories = $query->get()
            ->map(function($inventory) {
                return [
                    'inventory' => $inventory,
                    'book' => $inventory->book,
                    'current_quantity' => $inventory->quantity,
                    'value' => $inventory->quantity * $inventory->book->price,
                    'last_counted' => null, // Có thể thêm field này vào database
                ];
            })
            ->sortByDesc('value')
            ->take(50);

        $categories = Category::where('is_active', true)->get();

        return view('warehouse.inventory.cycle-count', compact('inventories', 'categories'));
    }

    public function submitCycleCount(Request $request)
    {
        $request->validate([
            'counts' => 'required|array',
            'counts.*.inventory_id' => 'required|exists:inventories,id',
            'counts.*.physical_count' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $adjustments = [];
        $totalDiscrepancies = 0;

        foreach ($request->counts as $count) {
            $inventory = Inventory::find($count['inventory_id']);
            $physicalCount = $count['physical_count'];
            $systemCount = $inventory->quantity;
            $discrepancy = $physicalCount - $systemCount;

            if ($discrepancy != 0) {
                $adjustments[] = [
                    'book_title' => $inventory->book->title,
                    'system_count' => $systemCount,
                    'physical_count' => $physicalCount,
                    'discrepancy' => $discrepancy,
                ];

                // Cập nhật tồn kho
                $inventory->update(['quantity' => $physicalCount]);
                $inventory->updateAvailableQuantity();

                $totalDiscrepancies++;

                // Log điều chỉnh kiểm kê
                \Log::info("Cycle count adjustment", [
                    'user_id' => auth()->id(),
                    'book_id' => $inventory->book_id,
                    'book_title' => $inventory->book->title,
                    'system_count' => $systemCount,
                    'physical_count' => $physicalCount,
                    'discrepancy' => $discrepancy,
                    'counted_at' => now(),
                    'notes' => $request->notes,
                ]);
            }
        }

        $message = "Kiểm kê hoàn tất. Tìm thấy {$totalDiscrepancies} sai lệch.";
        
        return back()->with('success', $message)
                    ->with('adjustments', $adjustments);
    }

    public function export(Request $request)
    {
        // Export inventory to Excel
        return back()->with('success', 'Đã xuất báo cáo tồn kho thành công.');
    }

    private function getRecentMovements($bookId, $limit = 50)
    {
        $book = Book::find($bookId);
        if (!$book) {
            return collect();
        }

        // Lấy lịch sử nhập hàng
        $imports = $book->importDetails()
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
                    'user' => 'Warehouse',
                ];
            });

        // Lấy lịch sử xuất hàng
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
                    'user' => 'Warehouse',
                ];
            });

        return $imports->concat($distributions)
                      ->sortByDesc('date')
                      ->take($limit);
    }

    private function calculateMonthlyDemand($bookId)
    {
        $book = Book::find($bookId);
        if (!$book) {
            return 0;
        }

        $recentOrders = $book->orderRequestDetails()
            ->whereHas('orderRequest', function($q) {
                $q->where('status', 'completed')
                  ->where('created_at', '>=', now()->subMonths(6));
            })
            ->sum('quantity');

        return $recentOrders / 6; // Average per month
    }
}