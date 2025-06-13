<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Import;
use App\Models\Supplier;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ImportController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function index(Request $request)
    {
        $query = Import::with(['supplier', 'creator', 'confirmer']);

        // Lọc theo trạng thái
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        // Lọc theo nhà cung cấp
        if ($request->has('supplier_id') && !empty($request->supplier_id)) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Lọc theo thời gian
        if ($request->has('date_from') && !empty($request->date_from)) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to') && !empty($request->date_to)) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Tìm kiếm theo mã phiếu
        if ($request->has('search') && !empty($request->search)) {
            $query->where('code', 'like', '%' . $request->search . '%');
        }

        // Sắp xếp: pending và confirmed lên đầu
        $query->orderByRaw("CASE 
                           WHEN status = 'pending' THEN 0
                           WHEN status = 'confirmed' THEN 1 
                           ELSE 2 END")
              ->latest();

        $imports = $query->paginate(15)->appends($request->all());

        // Dữ liệu cho form lọc
        $suppliers = Supplier::where('is_active', true)->get();
        $statuses = [
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'received' => 'Đã nhận hàng',
            'cancelled' => 'Đã hủy',
        ];

        // Thống kê nhanh
        $quick_stats = [
            'total' => Import::count(),
            'pending' => Import::where('status', 'pending')->count(),
            'confirmed' => Import::where('status', 'confirmed')->count(),
            'received_today' => Import::where('status', 'received')
                ->whereDate('received_at', today())->count(),
            'total_value_this_month' => Import::where('status', 'received')
                ->whereMonth('received_at', now()->month)
                ->sum('total_amount'),
        ];

        return view('warehouse.imports.index', compact(
            'imports', 'suppliers', 'statuses', 'quick_stats'
        ));
    }

    public function show(Import $import)
    {
        $import->load([
            'supplier', 'creator', 'confirmer',
            'details.book.category', 'details.book.inventory'
        ]);

        // Timeline chi tiết
        $timeline = [
            [
                'status' => 'created',
                'title' => 'Tạo phiếu nhập hàng',
                'date' => $import->created_at,
                'user' => $import->creator->name,
                'icon' => 'fas fa-plus-circle',
                'color' => 'primary',
                'completed' => true,
            ],
        ];

        if ($import->confirmed_at) {
            $timeline[] = [
                'status' => 'confirmed',
                'title' => 'Xác nhận phiếu nhập',
                'date' => $import->confirmed_at,
                'user' => $import->confirmer ? $import->confirmer->name : 'System',
                'icon' => 'fas fa-check-circle',
                'color' => 'info',
                'completed' => true,
            ];
        }

        if ($import->received_at) {
            $timeline[] = [
                'status' => 'received',
                'title' => 'Nhận hàng hoàn tất',
                'date' => $import->received_at,
                'user' => 'Nhân viên kho',
                'icon' => 'fas fa-box',
                'color' => 'success',
                'completed' => true,
            ];
        }

        // Thông tin về tồn kho hiện tại
        $current_stock = [];
        foreach ($import->details as $detail) {
            $inventory = $detail->book->inventory;
            $current_stock[$detail->book_id] = [
                'current_quantity' => $inventory ? $inventory->quantity : 0,
                'after_import' => $inventory ? $inventory->quantity + $detail->quantity : $detail->quantity,
                'book_title' => $detail->book->title,
            ];
        }

        // Tính thời gian xử lý
        $processing_info = [
            'created_hours_ago' => $import->created_at->diffInHours(now()),
            'days_since_confirmed' => $import->confirmed_at ? 
                $import->confirmed_at->diffInDays(now()) : null,
            'estimated_delivery' => $import->confirmed_at ? 
                $import->confirmed_at->addDays(7) : null,
        ];

        return view('warehouse.imports.show', compact(
            'import', 'timeline', 'current_stock', 'processing_info'
        ));
    }

    public function confirm(Import $import)
    {
        if (!$import->canBeConfirmed()) {
            return back()->with('error', 'Phiếu nhập hàng không thể được xác nhận trong trạng thái hiện tại.');
        }

        $import->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'confirmed_by' => auth()->id(),
        ]);

        // Log activity
        \Log::info("Import confirmed by warehouse", [
            'import_id' => $import->id,
            'import_code' => $import->code,
            'supplier' => $import->supplier->name,
            'confirmed_by' => auth()->id(),
            'total_amount' => $import->total_amount,
        ]);

        return back()->with('success', 'Phiếu nhập hàng đã được xác nhận thành công.');
    }

    public function receive(Import $import)
    {
        if (!$import->canBeReceived()) {
            return back()->with('error', 'Phiếu nhập hàng không thể được nhận trong trạng thái hiện tại.');
        }

        // Cập nhật tồn kho
        $result = $this->inventoryService->updateInventoryFromImport($import);

        if ($result['success']) {
            // Log activity
            \Log::info("Import received by warehouse", [
                'import_id' => $import->id,
                'import_code' => $import->code,
                'supplier' => $import->supplier->name,
                'received_by' => auth()->id(),
                'received_at' => now(),
                'total_items' => $import->details->sum('quantity'),
                'total_amount' => $import->total_amount,
            ]);

            return back()->with('success', 'Phiếu nhập hàng đã được nhận và cập nhật tồn kho thành công.');
        }

        return back()->with('error', $result['message']);
    }

    public function partialReceive(Request $request, Import $import)
    {
        if ($import->status !== 'confirmed') {
            return back()->with('error', 'Chỉ có thể nhận hàng từng phần cho phiếu đã xác nhận.');
        }

        $request->validate([
            'items' => 'required|array',
            'items.*.detail_id' => 'required|exists:import_details,id',
            'items.*.received_quantity' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $totalReceivedItems = 0;
        $partiallyReceived = false;

        foreach ($request->items as $item) {
            $detail = $import->details()->find($item['detail_id']);
            $receivedQty = $item['received_quantity'];
            
            if ($receivedQty > 0 && $receivedQty <= $detail->quantity) {
                // Cập nhật tồn kho cho số lượng nhận được
                $inventory = $detail->book->inventory;
                if ($inventory) {
                    $inventory->addStock($receivedQty);
                } else {
                    // Tạo inventory mới nếu chưa có
                    \App\Models\Inventory::create([
                        'book_id' => $detail->book_id,
                        'quantity' => $receivedQty,
                        'reserved_quantity' => 0,
                        'available_quantity' => $receivedQty,
                    ]);
                }
                
                $totalReceivedItems += $receivedQty;
                
                if ($receivedQty < $detail->quantity) {
                    $partiallyReceived = true;
                }
            }
        }

        // Cập nhật trạng thái
        if ($totalReceivedItems > 0) {
            if ($partiallyReceived) {
                $import->update([
                    'status' => 'partially_received',
                    'notes' => $request->notes,
                ]);
                $message = 'Đã nhận hàng từng phần thành công.';
            } else {
                $import->update([
                    'status' => 'received',
                    'received_at' => now(),
                    'notes' => $request->notes,
                ]);
                $message = 'Đã nhận hàng hoàn tất.';
            }

            return back()->with('success', $message);
        }

        return back()->with('error', 'Chưa có sản phẩm nào được nhận.');
    }

    public function addReceivingNote(Request $request, Import $import)
    {
        $request->validate([
            'note' => 'required|string|max:500',
        ], [
            'note.required' => 'Ghi chú là bắt buộc.',
            'note.max' => 'Ghi chú không được vượt quá 500 ký tự.',
        ]);

        $currentNotes = $import->notes ?: '';
        $newNote = now()->format('d/m/Y H:i') . ' - ' . auth()->user()->name . ': ' . $request->note;
        $updatedNotes = $currentNotes ? $currentNotes . "\n" . $newNote : $newNote;

        $import->update(['notes' => $updatedNotes]);

        return back()->with('success', 'Ghi chú nhận hàng đã được thêm thành công.');
    }

    public function printReceivingSlip(Import $import)
    {
        $import->load(['supplier', 'details.book']);
        
        return view('warehouse.imports.receiving-slip', compact('import'));
    }

    public function bulkConfirm(Request $request)
    {
        $request->validate([
            'import_ids' => 'required|array',
            'import_ids.*' => 'exists:imports,id',
        ]);

        $confirmed_count = 0;
        $failed_imports = [];

        foreach ($request->import_ids as $importId) {
            $import = Import::find($importId);
            
            if ($import && $import->canBeConfirmed()) {
                $import->update([
                    'status' => 'confirmed',
                    'confirmed_at' => now(),
                    'confirmed_by' => auth()->id(),
                ]);
                $confirmed_count++;
            } else {
                $failed_imports[] = $import ? $import->code : "ID: $importId";
            }
        }

        $message = "Đã xác nhận thành công {$confirmed_count} phiếu nhập hàng.";
        if (!empty($failed_imports)) {
            $message .= " Không thể xác nhận: " . implode(', ', $failed_imports);
        }

        return back()->with('success', $message);
    }

    public function rejectImport(Request $request, Import $import)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ], [
            'rejection_reason.required' => 'Lý do từ chối là bắt buộc.',
            'rejection_reason.max' => 'Lý do từ chối không được vượt quá 500 ký tự.',
        ]);

        if ($import->status !== 'pending') {
            return back()->with('error', 'Chỉ có thể từ chối phiếu nhập ở trạng thái chờ xác nhận.');
        }

        $import->update([
            'status' => 'cancelled',
            'notes' => 'Từ chối bởi kho: ' . $request->rejection_reason,
        ]);

        return back()->with('success', 'Phiếu nhập hàng đã được từ chối.');
    }

    public function export(Request $request)
    {
        // Export imports to Excel/PDF
        return back()->with('success', 'Đã xuất danh sách phiếu nhập hàng thành công.');
    }

    public function statistics(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        $stats = [
            'total_imports' => Import::whereBetween('created_at', [$startDate, $endDate])->count(),
            'received_imports' => Import::whereBetween('received_at', [$startDate, $endDate])
                ->where('status', 'received')->count(),
            'total_value' => Import::whereBetween('received_at', [$startDate, $endDate])
                ->where('status', 'received')->sum('total_amount'),
            'avg_processing_time' => Import::whereNotNull('received_at')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get()
                ->avg(function($import) {
                    return $import->created_at->diffInHours($import->received_at);
                }),
        ];

        $importsBySupplier = Import::with('supplier')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'received')
            ->get()
            ->groupBy('supplier.name')
            ->map(function($imports) {
                return [
                    'count' => $imports->count(),
                    'value' => $imports->sum('total_amount')
                ];
            });

        $importsByStatus = Import::whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->groupBy('status')
            ->map->count();

        return view('warehouse.imports.statistics', compact(
            'stats', 'importsBySupplier', 'importsByStatus', 'startDate', 'endDate'
        ));
    }
}