<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\OrderRequest;
use App\Models\Branch;
use App\Models\Inventory;
use App\Services\OrderService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class OrderRequestController extends Controller
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
        $query = OrderRequest::with(['branch', 'creator', 'approver']);

        // Lọc theo trạng thái
        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        // Lọc theo chi nhánh
        if ($request->has('branch_id') && !empty($request->branch_id)) {
            $query->where('branch_id', $request->branch_id);
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

        // Sắp xếp: pending lên đầu, sau đó theo thời gian tạo
        $query->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
              ->latest();

        $orders = $query->paginate(15)->appends($request->all());

        // Dữ liệu cho form lọc
        $branches = Branch::where('is_active', true)->get();
        $statuses = [
            'pending' => 'Chờ duyệt',
            'approved' => 'Đã duyệt',
            'rejected' => 'Từ chối',
            'processing' => 'Đang xử lý',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy',
        ];

        // Thống kê nhanh
        $quick_stats = [
            'total' => OrderRequest::count(),
            'pending' => OrderRequest::where('status', 'pending')->count(),
            'approved_today' => OrderRequest::where('status', 'approved')
                ->whereDate('approved_at', today())->count(),
            'total_value_pending' => OrderRequest::where('status', 'pending')
                ->with('details')
                ->get()
                ->sum('total_amount'),
        ];

        return view('manager.orders.index', compact(
            'orders', 'branches', 'statuses', 'quick_stats'
        ));
    }

    public function show(OrderRequest $orderRequest)
    {
        $orderRequest->load([
            'branch', 'creator', 'approver', 
            'details.book.category', 'details.book.inventory',
            'distribution.details'
        ]);

        // Kiểm tra tồn kho cho từng item
        $stock_check = [];
        foreach ($orderRequest->details as $detail) {
            $inventory = $detail->book->inventory;
            $stock_check[$detail->book_id] = [
                'available' => $inventory ? $inventory->available_quantity : 0,
                'requested' => $detail->quantity,
                'sufficient' => $inventory ? $inventory->available_quantity >= $detail->quantity : false,
            ];
        }

        // Lịch sử thay đổi trạng thái (có thể thêm bảng order_status_history)
        $status_history = [
            [
                'status' => 'pending',
                'date' => $orderRequest->created_at,
                'user' => $orderRequest->creator->name,
                'note' => 'Đơn hàng được tạo'
            ]
        ];

        if ($orderRequest->approved_at) {
            $status_history[] = [
                'status' => $orderRequest->status,
                'date' => $orderRequest->approved_at,
                'user' => $orderRequest->approver ? $orderRequest->approver->name : 'System',
                'note' => $orderRequest->status === 'approved' ? 'Đơn hàng được duyệt' : 'Đơn hàng bị từ chối'
            ];
        }

        return view('manager.orders.show', compact(
            'orderRequest', 'stock_check', 'status_history'
        ));
    }

    public function approve(OrderRequest $orderRequest)
    {
        if (!$orderRequest->canBeApproved()) {
            return back()->with('error', 'Đơn hàng không thể được duyệt trong trạng thái hiện tại.');
        }

        // Kiểm tra tồn kho trước khi duyệt
        $insufficient_stock = [];
        foreach ($orderRequest->details as $detail) {
            $inventory = Inventory::where('book_id', $detail->book_id)->first();
            if (!$inventory || !$inventory->hasStock($detail->quantity)) {
                $insufficient_stock[] = [
                    'book' => $detail->book->title,
                    'requested' => $detail->quantity,
                    'available' => $inventory ? $inventory->available_quantity : 0,
                ];
            }
        }

        if (!empty($insufficient_stock)) {
            return back()->with('error', 'Không đủ tồn kho cho một số sách.')
                         ->with('insufficient_stock', $insufficient_stock);
        }

        $result = $this->orderService->approveOrder($orderRequest, auth()->id());

        if ($result['success']) {
            // Gửi thông báo
            $this->notificationService->notifyOrderApproved($orderRequest);
            $this->notificationService->notifyNewDistribution($result['distribution']);
            
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    public function reject(Request $request, OrderRequest $orderRequest)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ], [
            'rejection_reason.required' => 'Lý do từ chối là bắt buộc.',
            'rejection_reason.max' => 'Lý do từ chối không được vượt quá 500 ký tự.',
        ]);

        if (!$orderRequest->canBeRejected()) {
            return back()->with('error', 'Đơn hàng không thể được từ chối trong trạng thái hiện tại.');
        }

        $result = $this->orderService->rejectOrder(
            $orderRequest,
            auth()->id(),
            $request->rejection_reason
        );

        if ($result['success']) {
            $this->notificationService->notifyOrderRejected($orderRequest);
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    public function bulkApprove(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:order_requests,id',
        ]);

        $approved_count = 0;
        $failed_orders = [];

        foreach ($request->order_ids as $orderId) {
            $order = OrderRequest::find($orderId);
            
            if ($order && $order->canBeApproved()) {
                $result = $this->orderService->approveOrder($order, auth()->id());
                
                if ($result['success']) {
                    $approved_count++;
                    $this->notificationService->notifyOrderApproved($order);
                    $this->notificationService->notifyNewDistribution($result['distribution']);
                } else {
                    $failed_orders[] = $order->code;
                }
            } else {
                $failed_orders[] = $order ? $order->code : "ID: $orderId";
            }
        }

        $message = "Đã duyệt thành công {$approved_count} đơn hàng.";
        if (!empty($failed_orders)) {
            $message .= " Không thể duyệt: " . implode(', ', $failed_orders);
        }

        return back()->with('success', $message);
    }

    public function export(Request $request)
    {
        // Export orders to Excel/PDF
        // Implementation depends on your export library (Laravel Excel, etc.)
        
        return back()->with('success', 'Đã xuất danh sách đơn hàng thành công.');
    }

    public function statistics(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        $stats = [
            'total_orders' => OrderRequest::whereBetween('created_at', [$startDate, $endDate])->count(),
            'approved_orders' => OrderRequest::whereBetween('approved_at', [$startDate, $endDate])
                ->where('status', 'approved')->count(),
            'rejected_orders' => OrderRequest::whereBetween('approved_at', [$startDate, $endDate])
                ->where('status', 'rejected')->count(),
            'avg_approval_time' => OrderRequest::whereNotNull('approved_at')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get()
                ->avg(function($order) {
                    return $order->created_at->diffInHours($order->approved_at);
                }),
        ];

        $ordersByBranch = OrderRequest::with('branch')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->groupBy('branch.name')
            ->map->count();

        $ordersByStatus = OrderRequest::whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->groupBy('status')
            ->map->count();

        return view('manager.orders.statistics', compact(
            'stats', 'ordersByBranch', 'ordersByStatus', 'startDate', 'endDate'
        ));
    }
}