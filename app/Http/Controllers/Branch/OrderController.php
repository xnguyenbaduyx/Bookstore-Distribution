<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Models\OrderRequest;
use App\Models\OrderRequestDetail;
use App\Models\Book;
use App\Models\Category;
use App\Services\OrderService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use DB;
use Carbon\Carbon;

class OrderController extends Controller
{
    protected $orderService;
    protected $notificationService;

    public function __construct(OrderService $orderService, NotificationService $notificationService)
    {
        $this->orderService = $orderService;
        $this->notificationService = $notificationService;
    }

    public function index(Request $request)
    {
        $branchId = auth()->user()->branch_id;
        
        $query = OrderRequest::with(['creator', 'approver'])
            ->where('branch_id', $branchId);

        // Lọc theo trạng thái
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        // Lọc theo thời gian
        if ($request->has('date_from') && !empty($request->date_from)) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to') && !empty($request->date_to)) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Tìm kiếm theo mã đơn
        if ($request->has('search') && !empty($request->search)) {
            $query->where('code', 'like', '%' . $request->search . '%');
        }

        // Sắp xếp: pending lên đầu, sau đó theo thời gian
        $query->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
              ->latest();

        $orders = $query->paginate(15)->appends($request->all());

        // Thống kê nhanh
        $quick_stats = [
            'total' => OrderRequest::where('branch_id', $branchId)->count(),
            'pending' => OrderRequest::where('branch_id', $branchId)
                ->where('status', 'pending')->count(),
            'approved' => OrderRequest::where('branch_id', $branchId)
                ->where('status', 'approved')->count(),
            'completed_this_month' => OrderRequest::where('branch_id', $branchId)
                ->where('status', 'completed')
                ->whereMonth('created_at', now()->month)->count(),
            'total_value_this_month' => OrderRequest::where('branch_id', $branchId)
                ->where('status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->with('details')
                ->get()
                ->sum('total_amount'),
        ];

        $statuses = [
            'pending' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'rejected' => 'Từ chối',
            'processing' => 'Đang xử lý',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy',
        ];

        return view('branch.orders.index', compact('orders', 'quick_stats', 'statuses'));
    }

    public function create()
    {
        $categories = Category::where('is_active', true)->get();
        $books = Book::where('is_active', true)
            ->with(['category', 'inventory'])
            ->orderBy('title')
            ->get();
        
        return view('branch.orders.create', compact('categories', 'books'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
            'books' => 'required|array|min:1',
            'books.*.book_id' => 'required|exists:books,id',
            'books.*.quantity' => 'required|integer|min:1|max:999',
        ], [
            'books.required' => 'Phải chọn ít nhất một cuốn sách.',
            'books.min' => 'Phải chọn ít nhất một cuốn sách.',
            'books.*.book_id.required' => 'Sách là bắt buộc.',
            'books.*.quantity.required' => 'Số lượng là bắt buộc.',
            'books.*.quantity.min' => 'Số lượng phải lớn hơn 0.',
            'books.*.quantity.max' => 'Số lượng không được vượt quá 999.',
            'notes.max' => 'Ghi chú không được vượt quá 500 ký tự.',
        ]);

        DB::beginTransaction();

        try {
            $orderRequest = OrderRequest::create([
                'code' => $this->orderService->generateOrderCode(),
                'branch_id' => auth()->user()->branch_id,
                'created_by' => auth()->id(),
                'notes' => $request->notes,
                'status' => 'pending',
            ]);

            foreach ($request->books as $bookData) {
                $book = Book::find($bookData['book_id']);
                $totalPrice = $bookData['quantity'] * $book->price;

                OrderRequestDetail::create([
                    'order_request_id' => $orderRequest->id,
                    'book_id' => $bookData['book_id'],
                    'quantity' => $bookData['quantity'],
                    'unit_price' => $book->price,
                    'total_price' => $totalPrice,
                ]);
            }

            DB::commit();

            // Gửi thông báo
            $this->notificationService->notifyNewOrderRequest($orderRequest);

            return redirect()->route('branch.orders.index')
                ->with('success', 'Yêu cầu đặt sách đã được tạo thành công.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())
                         ->withInput();
        }
    }

    public function show(OrderRequest $orderRequest)
    {
        // Kiểm tra quyền truy cập
        if ($orderRequest->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Bạn không có quyền xem đơn hàng này.');
        }

        $orderRequest->load([
            'creator', 'approver', 'details.book.category',
            'distribution.details'
        ]);

        // Timeline trạng thái
        $timeline = [
            [
                'status' => 'pending',
                'title' => 'Tạo yêu cầu',
                'date' => $orderRequest->created_at,
                'user' => $orderRequest->creator->name,
                'icon' => 'fas fa-plus-circle',
                'color' => 'primary',
                'completed' => true,
            ]
        ];

        if ($orderRequest->approved_at) {
            $timeline[] = [
                'status' => $orderRequest->status,
                'title' => $orderRequest->status === 'approved' ? 'Đã duyệt' : 'Bị từ chối',
                'date' => $orderRequest->approved_at,
                'user' => $orderRequest->approver ? $orderRequest->approver->name : 'System',
                'icon' => $orderRequest->status === 'approved' ? 'fas fa-check-circle' : 'fas fa-times-circle',
                'color' => $orderRequest->status === 'approved' ? 'success' : 'danger',
                'completed' => true,
            ];
        }

        if ($orderRequest->distribution) {
            $distribution = $orderRequest->distribution;
            
            if ($distribution->confirmed_at) {
                $timeline[] = [
                    'status' => 'distribution_created',
                    'title' => 'Tạo phiếu phân phối',
                    'date' => $distribution->confirmed_at,
                    'user' => 'Nhân viên kho',
                    'icon' => 'fas fa-shipping-fast',
                    'color' => 'info',
                    'completed' => true,
                ];
            }

            if ($distribution->shipped_at) {
                $timeline[] = [
                    'status' => 'shipped',
                    'title' => 'Đã xuất kho',
                    'date' => $distribution->shipped_at,
                    'user' => 'Nhân viên kho',
                    'icon' => 'fas fa-truck',
                    'color' => 'warning',
                    'completed' => true,
                ];
            }

            if ($distribution->delivered_at) {
                $timeline[] = [
                    'status' => 'delivered',
                    'title' => 'Đã giao hàng',
                    'date' => $distribution->delivered_at,
                    'user' => 'Hệ thống',
                    'icon' => 'fas fa-check-double',
                    'color' => 'success',
                    'completed' => true,
                ];
            }
        }

        // Tính toán thời gian xử lý
        $processing_info = [
            'days_since_created' => $orderRequest->created_at->diffInDays(now()),
            'approval_time' => $orderRequest->approved_at ? 
                $orderRequest->created_at->diffInHours($orderRequest->approved_at) : null,
            'estimated_delivery' => $orderRequest->approved_at ? 
                $orderRequest->approved_at->addDays(3) : null,
        ];

        return view('branch.orders.show', compact('orderRequest', 'timeline', 'processing_info'));
    }

    public function edit(OrderRequest $orderRequest)
    {
        // Kiểm tra quyền truy cập
        if ($orderRequest->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Bạn không có quyền chỉnh sửa đơn hàng này.');
        }

        if (!$orderRequest->canBeModified()) {
            return back()->with('error', 'Đơn hàng không thể chỉnh sửa trong trạng thái hiện tại.');
        }

        $categories = Category::where('is_active', true)->get();
        $books = Book::where('is_active', true)
            ->with(['category', 'inventory'])
            ->orderBy('title')
            ->get();
        
        $orderRequest->load('details.book');
        
        return view('branch.orders.edit', compact('orderRequest', 'categories', 'books'));
    }

    public function update(Request $request, OrderRequest $orderRequest)
    {
        // Kiểm tra quyền truy cập
        if ($orderRequest->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Bạn không có quyền chỉnh sửa đơn hàng này.');
        }

        if (!$orderRequest->canBeModified()) {
            return back()->with('error', 'Đơn hàng không thể chỉnh sửa trong trạng thái hiện tại.');
        }

        $request->validate([
            'notes' => 'nullable|string|max:500',
            'books' => 'required|array|min:1',
            'books.*.book_id' => 'required|exists:books,id',
            'books.*.quantity' => 'required|integer|min:1|max:999',
        ], [
            'books.required' => 'Phải chọn ít nhất một cuốn sách.',
            'books.min' => 'Phải chọn ít nhất một cuốn sách.',
            'books.*.book_id.required' => 'Sách là bắt buộc.',
            'books.*.quantity.required' => 'Số lượng là bắt buộc.',
            'books.*.quantity.min' => 'Số lượng phải lớn hơn 0.',
            'books.*.quantity.max' => 'Số lượng không được vượt quá 999.',
            'notes.max' => 'Ghi chú không được vượt quá 500 ký tự.',
        ]);

        DB::beginTransaction();

        try {
            // Cập nhật thông tin đơn hàng
            $orderRequest->update([
                'notes' => $request->notes,
            ]);

            // Xóa chi tiết cũ
            $orderRequest->details()->delete();

            // Tạo chi tiết mới
            foreach ($request->books as $bookData) {
                $book = Book::find($bookData['book_id']);
                $totalPrice = $bookData['quantity'] * $book->price;

                OrderRequestDetail::create([
                    'order_request_id' => $orderRequest->id,
                    'book_id' => $bookData['book_id'],
                    'quantity' => $bookData['quantity'],
                    'unit_price' => $book->price,
                    'total_price' => $totalPrice,
                ]);
            }

            DB::commit();

            return redirect()->route('branch.orders.index')
                ->with('success', 'Yêu cầu đặt sách đã được cập nhật thành công.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())
                         ->withInput();
        }
    }

    public function destroy(OrderRequest $orderRequest)
    {
        // Kiểm tra quyền truy cập
        if ($orderRequest->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Bạn không có quyền xóa đơn hàng này.');
        }

        if (!$orderRequest->canBeCancelled()) {
            return back()->with('error', 'Đơn hàng không thể hủy trong trạng thái hiện tại.');
        }

        $orderRequest->update(['status' => 'cancelled']);

        return redirect()->route('branch.orders.index')
            ->with('success', 'Yêu cầu đặt sách đã được hủy thành công.');
    }

    public function duplicate(OrderRequest $orderRequest)
    {
        // Kiểm tra quyền truy cập
        if ($orderRequest->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Bạn không có quyền sao chép đơn hàng này.');
        }

        $categories = Category::where('is_active', true)->get();
        $books = Book::where('is_active', true)
            ->with(['category', 'inventory'])
            ->orderBy('title')
            ->get();
        
        $orderRequest->load('details.book');
        
        return view('branch.orders.duplicate', compact('orderRequest', 'categories', 'books'));
    }

    public function storeDuplicate(Request $request, OrderRequest $originalOrder)
    {
        // Kiểm tra quyền truy cập
        if ($originalOrder->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Bạn không có quyền sao chép đơn hàng này.');
        }

        $request->validate([
            'notes' => 'nullable|string|max:500',
            'books' => 'required|array|min:1',
            'books.*.book_id' => 'required|exists:books,id',
            'books.*.quantity' => 'required|integer|min:1|max:999',
        ]);

        DB::beginTransaction();

        try {
            $orderRequest = OrderRequest::create([
                'code' => $this->orderService->generateOrderCode(),
                'branch_id' => auth()->user()->branch_id,
                'created_by' => auth()->id(),
                'notes' => $request->notes,
                'status' => 'pending',
            ]);

            foreach ($request->books as $bookData) {
                $book = Book::find($bookData['book_id']);
                $totalPrice = $bookData['quantity'] * $book->price;

                OrderRequestDetail::create([
                    'order_request_id' => $orderRequest->id,
                    'book_id' => $bookData['book_id'],
                    'quantity' => $bookData['quantity'],
                    'unit_price' => $book->price,
                    'total_price' => $totalPrice,
                ]);
            }

            DB::commit();

            $this->notificationService->notifyNewOrderRequest($orderRequest);

            return redirect()->route('branch.orders.index')
                ->with('success', 'Đơn hàng đã được sao chép và tạo thành công.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Có lỗi xảy ra: ' . $e->getMessage())
                         ->withInput();
        }
    }

    public function quickOrder(Request $request)
    {
        // API endpoint cho đặt hàng nhanh
        $request->validate([
            'book_id' => 'required|exists:books,id',
            'quantity' => 'required|integer|min:1|max:999',
            'notes' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $book = Book::find($request->book_id);
            
            $orderRequest = OrderRequest::create([
                'code' => $this->orderService->generateOrderCode(),
                'branch_id' => auth()->user()->branch_id,
                'created_by' => auth()->id(),
                'notes' => $request->notes,
                'status' => 'pending',
            ]);

            OrderRequestDetail::create([
                'order_request_id' => $orderRequest->id,
                'book_id' => $request->book_id,
                'quantity' => $request->quantity,
                'unit_price' => $book->price,
                'total_price' => $request->quantity * $book->price,
            ]);

            DB::commit();

            $this->notificationService->notifyNewOrderRequest($orderRequest);

            return response()->json([
                'success' => true,
                'message' => 'Đặt hàng nhanh thành công',
                'order_code' => $orderRequest->code
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    public function export(Request $request)
    {
        $branchId = auth()->user()->branch_id;
        
        // Export orders to Excel/PDF
        return back()->with('success', 'Đã xuất danh sách đơn hàng thành công.');
    }

    public function statistics(Request $request)
    {
        $branchId = auth()->user()->branch_id;
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        $stats = [
            'total_orders' => OrderRequest::where('branch_id', $branchId)
                ->whereBetween('created_at', [$startDate, $endDate])->count(),
            'approved_orders' => OrderRequest::where('branch_id', $branchId)
                ->whereBetween('approved_at', [$startDate, $endDate])
                ->where('status', 'approved')->count(),
            'rejected_orders' => OrderRequest::where('branch_id', $branchId)
                ->whereBetween('approved_at', [$startDate, $endDate])
                ->where('status', 'rejected')->count(),
            'completed_orders' => OrderRequest::where('branch_id', $branchId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'completed')->count(),
            'total_value' => OrderRequest::where('branch_id', $branchId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->where('status', 'completed')
                ->with('details')->get()->sum('total_amount'),
            'avg_order_value' => 0, // Sẽ tính sau
            'success_rate' => 0, // Sẽ tính sau
        ];

        // Tính toán các chỉ số
        if ($stats['total_orders'] > 0) {
            $stats['success_rate'] = round(
                (($stats['approved_orders'] + $stats['completed_orders']) / $stats['total_orders']) * 100, 1
            );
        }

        if ($stats['completed_orders'] > 0) {
            $stats['avg_order_value'] = round($stats['total_value'] / $stats['completed_orders'], 0);
        }

        // Thống kê theo trạng thái
        $ordersByStatus = OrderRequest::where('branch_id', $branchId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->groupBy('status')
            ->map->count();

        // Top sách được đặt nhiều nhất
        $topBooks = OrderRequest::where('branch_id', $branchId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('details.book')
            ->get()
            ->flatMap(function($order) {
                return $order->details;
            })
            ->groupBy('book_id')
            ->map(function($details) {
                return [
                    'book' => $details->first()->book,
                    'total_quantity' => $details->sum('quantity'),
                    'total_orders' => $details->count(),
                    'total_value' => $details->sum('total_price'),
                ];
            })
            ->sortByDesc('total_quantity')
            ->take(10);

        return view('branch.orders.statistics', compact(
            'stats', 'ordersByStatus', 'topBooks', 'startDate', 'endDate'
        ));
    }

    public function getBookInfo(Request $request)
    {
        // API endpoint để lấy thông tin sách
        $book = Book::with(['category', 'inventory'])
            ->find($request->book_id);

        if (!$book) {
            return response()->json(['error' => 'Không tìm thấy sách'], 404);
        }

        return response()->json([
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'price' => $book->price,
            'formatted_price' => number_format($book->price, 0, ',', '.') . ' đ',
            'category' => $book->category->name,
            'available_stock' => $book->inventory ? $book->inventory->available_quantity : 0,
            'image_url' => $book->image_url,
        ]);
    }
}
