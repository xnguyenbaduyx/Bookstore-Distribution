<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Import;
use App\Models\ImportDetail;
use App\Models\Supplier;
use App\Models\Book;
use App\Models\Category;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use DB;
use Carbon\Carbon;

class ImportController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
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

        // Sắp xếp
        $query->latest();

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
            'total_value_this_month' => Import::where('status', 'received')
                ->whereMonth('received_at', now()->month)
                ->sum('total_amount'),
        ];

        return view('manager.imports.index', compact(
            'imports', 'suppliers', 'statuses', 'quick_stats'
        ));
    }

    public function create()
    {
        $suppliers = Supplier::where('is_active', true)->get();
        $categories = Category::where('is_active', true)->get();
        $books = Book::where('is_active', true)->with('category')->get();
        
        return view('manager.imports.create', compact('suppliers', 'categories', 'books'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'notes' => 'nullable|string|max:1000',
            'books' => 'required|array|min:1',
            'books.*.book_id' => 'required|exists:books,id',
            'books.*.quantity' => 'required|integer|min:1|max:999999',
            'books.*.unit_price' => 'required|numeric|min:0|max:99999999.99',
        ], [
            'supplier_id.required' => 'Nhà cung cấp là bắt buộc.',
            'books.required' => 'Phải chọn ít nhất một cuốn sách.',
            'books.min' => 'Phải chọn ít nhất một cuốn sách.',
            'books.*.book_id.required' => 'Sách là bắt buộc.',
            'books.*.quantity.required' => 'Số lượng là bắt buộc.',
            'books.*.quantity.min' => 'Số lượng phải lớn hơn 0.',
            'books.*.quantity.max' => 'Số lượng quá lớn.',
            'books.*.unit_price.required' => 'Giá đơn vị là bắt buộc.',
            'books.*.unit_price.min' => 'Giá đơn vị không được âm.',
            'books.*.unit_price.max' => 'Giá đơn vị quá lớn.',
            'notes.max' => 'Ghi chú không được vượt quá 1000 ký tự.',
        ]);

        DB::beginTransaction();

        try {
            $import = Import::create([
                'code' => $this->generateImportCode(),
                'supplier_id' => $request->supplier_id,
                'created_by' => auth()->id(),
                'notes' => $request->notes,
                'status' => 'pending',
            ]);

            $totalAmount = 0;

            foreach ($request->books as $bookData) {
                $totalPrice = $bookData['quantity'] * $bookData['unit_price'];
                $totalAmount += $totalPrice;

                ImportDetail::create([
                    'import_id' => $import->id,
                    'book_id' => $bookData['book_id'],
                    'quantity' => $bookData['quantity'],
                    'unit_price' => $bookData['unit_price'],
                    'total_price' => $totalPrice,
                ]);
            }

            $import->update(['total_amount' => $totalAmount]);

            DB::commit();

            // Gửi thông báo
            $this->notificationService->notifyNewImport($import);

            return redirect()->route('manager.imports.index')
                ->with('success', 'Phiếu nhập hàng đã được tạo thành công.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())
                         ->withInput();
        }
    }

    public function show(Import $import)
    {
        $import->load([
            'supplier', 'creator', 'confirmer',
            'details.book.category'
        ]);

        // Timeline của phiếu nhập
        $timeline = [
            [
                'status' => 'created',
                'title' => 'Tạo phiếu nhập hàng',
                'date' => $import->created_at,
                'user' => $import->creator->name,
                'completed' => true,
            ],
        ];

        if ($import->status !== 'pending') {
            return back()->with('error', 'Chỉ có thể chỉnh sửa phiếu nhập ở trạng thái chờ xác nhận.');
        }

        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'notes' => 'nullable|string|max:1000',
            'books' => 'required|array|min:1',
            'books.*.book_id' => 'required|exists:books,id',
            'books.*.quantity' => 'required|integer|min:1|max:999999',
            'books.*.unit_price' => 'required|numeric|min:0|max:99999999.99',
        ], [
            'supplier_id.required' => 'Nhà cung cấp là bắt buộc.',
            'books.required' => 'Phải chọn ít nhất một cuốn sách.',
            'books.min' => 'Phải chọn ít nhất một cuốn sách.',
            'books.*.book_id.required' => 'Sách là bắt buộc.',
            'books.*.quantity.required' => 'Số lượng là bắt buộc.',
            'books.*.quantity.min' => 'Số lượng phải lớn hơn 0.',
            'books.*.quantity.max' => 'Số lượng quá lớn.',
            'books.*.unit_price.required' => 'Giá đơn vị là bắt buộc.',
            'books.*.unit_price.min' => 'Giá đơn vị không được âm.',
            'books.*.unit_price.max' => 'Giá đơn vị quá lớn.',
            'notes.max' => 'Ghi chú không được vượt quá 1000 ký tự.',
        ]);

        DB::beginTransaction();

        try {
            // Cập nhật thông tin phiếu nhập
            $import->update([
                'supplier_id' => $request->supplier_id,
                'notes' => $request->notes,
            ]);

            // Xóa chi tiết cũ
            $import->details()->delete();

            // Tạo chi tiết mới
            $totalAmount = 0;
            foreach ($request->books as $bookData) {
                $totalPrice = $bookData['quantity'] * $bookData['unit_price'];
                $totalAmount += $totalPrice;

                ImportDetail::create([
                    'import_id' => $import->id,
                    'book_id' => $bookData['book_id'],
                    'quantity' => $bookData['quantity'],
                    'unit_price' => $bookData['unit_price'],
                    'total_price' => $totalPrice,
                ]);
            }

            $import->update(['total_amount' => $totalAmount]);

            DB::commit();

            return redirect()->route('manager.imports.index')
                ->with('success', 'Phiếu nhập hàng đã được cập nhật thành công.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())
                         ->withInput();
        }
    }

    public function destroy(Import $import)
    {
        if ($import->status !== 'pending') {
            return back()->with('error', 'Chỉ có thể xóa phiếu nhập ở trạng thái chờ xác nhận.');
        }

        $import->update(['status' => 'cancelled']);

        return redirect()->route('manager.imports.index')
            ->with('success', 'Phiếu nhập hàng đã được hủy.');
    }

    public function duplicate(Import $import)
    {
        $suppliers = Supplier::where('is_active', true)->get();
        $categories = Category::where('is_active', true)->get();
        $books = Book::where('is_active', true)->with('category')->get();
        $import->load('details.book');

        return view('manager.imports.duplicate', compact('import', 'suppliers', 'categories', 'books'));
    }

    public function storeDuplicate(Request $request, Import $originalImport)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'notes' => 'nullable|string|max:1000',
            'books' => 'required|array|min:1',
            'books.*.book_id' => 'required|exists:books,id',
            'books.*.quantity' => 'required|integer|min:1|max:999999',
            'books.*.unit_price' => 'required|numeric|min:0|max:99999999.99',
        ]);

        DB::beginTransaction();

        try {
            $import = Import::create([
                'code' => $this->generateImportCode(),
                'supplier_id' => $request->supplier_id,
                'created_by' => auth()->id(),
                'notes' => $request->notes,
                'status' => 'pending',
            ]);

            $totalAmount = 0;
            foreach ($request->books as $bookData) {
                $totalPrice = $bookData['quantity'] * $bookData['unit_price'];
                $totalAmount += $totalPrice;

                ImportDetail::create([
                    'import_id' => $import->id,
                    'book_id' => $bookData['book_id'],
                    'quantity' => $bookData['quantity'],
                    'unit_price' => $bookData['unit_price'],
                    'total_price' => $totalPrice,
                ]);
            }

            $import->update(['total_amount' => $totalAmount]);

            DB::commit();

            $this->notificationService->notifyNewImport($import);

            return redirect()->route('manager.imports.index')
                ->with('success', 'Phiếu nhập hàng đã được tạo từ bản sao thành công.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())
                         ->withInput();
        }
    }

    private function generateImportCode()
    {
        $prefix = 'IMP';
        $date = now()->format('Ymd');
        $count = Import::whereDate('created_at', now())->count() + 1;
        return $prefix . $date . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function quickCreate(Request $request)
    {
        // API endpoint for quick import creation
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'book_id' => 'required|exists:books,id',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            $import = Import::create([
                'code' => $this->generateImportCode(),
                'supplier_id' => $request->supplier_id,
                'created_by' => auth()->id(),
                'status' => 'pending',
                'total_amount' => $request->quantity * $request->unit_price,
            ]);

            ImportDetail::create([
                'import_id' => $import->id,
                'book_id' => $request->book_id,
                'quantity' => $request->quantity,
                'unit_price' => $request->unit_price,
                'total_price' => $request->quantity * $request->unit_price,
            ]);

            DB::commit();

            $this->notificationService->notifyNewImport($import);

            return response()->json([
                'success' => true,
                'message' => 'Phiếu nhập nhanh đã được tạo thành công.',
                'import' => $import
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
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
                ->where('status', 'received')
                ->sum('total_amount'),
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

        return view('manager.imports.statistics', compact(
            'stats', 'importsBySupplier', 'importsByStatus', 'startDate', 'endDate'
        ));
    }

    public function getBooksBySupplier(Request $request)
    {
        // API endpoint to get books by supplier (based on import history)
        $supplierId = $request->supplier_id;
        
        $books = Book::whereHas('importDetails.import', function($query) use ($supplierId) {
            $query->where('supplier_id', $supplierId)
                  ->where('status', 'received');
        })->with('category')->get();

        return response()->json($books);
    }

    public function getSupplierStats(Supplier $supplier)
    {
        // API endpoint for supplier statistics
        $stats = [
            'total_imports' => $supplier->imports()->count(),
            'received_imports' => $supplier->imports()->where('status', 'received')->count(),
            'total_value' => $supplier->imports()->where('status', 'received')->sum('total_amount'),
            'avg_delivery_time' => $supplier->imports()
                ->whereNotNull('received_at')
                ->whereNotNull('confirmed_at')
                ->get()
                ->avg(function($import) {
                    return $import->confirmed_at->diffInDays($import->received_at);
                }) ?? 0,
            'on_time_rate' => $this->calculateOnTimeRate($supplier),
        ];

        return response()->json($stats);
    }

    private function calculateOnTimeRate(Supplier $supplier)
    {
        $imports = $supplier->imports()
            ->whereNotNull('received_at')
            ->whereNotNull('confirmed_at')
            ->get();

        if ($imports->count() === 0) {
            return 0;
        }

        $onTimeCount = $imports->filter(function($import) {
            // Consider on-time if received within 7 days of confirmation
            return $import->confirmed_at->diffInDays($import->received_at) <= 7;
        })->count();

        return ($onTimeCount / $imports->count()) * 100;
    }

    public function printPurchaseOrder(Import $import)
    {
        $import->load(['supplier', 'details.book']);
        
        return view('manager.imports.purchase-order', compact('import'));
    }

    public function export(Request $request)
    {
        // Export imports to Excel/PDF
        return back()->with('success', 'Đã xuất danh sách phiếu nhập hàng thành công.');
    }

    public function bulkCancel(Request $request)
    {
        $request->validate([
            'import_ids' => 'required|array',
            'import_ids.*' => 'exists:imports,id',
            'cancellation_reason' => 'required|string|max:500',
        ]);

        $cancelled_count = 0;
        $failed_imports = [];

        foreach ($request->import_ids as $importId) {
            $import = Import::find($importId);
            
            if ($import && $import->status === 'pending') {
                $import->update([
                    'status' => 'cancelled',
                    'notes' => $request->cancellation_reason
                ]);
                $cancelled_count++;
            } else {
                $failed_imports[] = $import ? $import->code : "ID: $importId";
            }
        }

        $message = "Đã hủy thành công {$cancelled_count} phiếu nhập hàng.";
        if (!empty($failed_imports)) {
            $message .= " Không thể hủy: " . implode(', ', $failed_imports);
        }

        return back()->with('success', $message);
    }
}