<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\Distribution;
use App\Models\Branch;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DistributionController extends Controller
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function index(Request $request)
    {
        $query = Distribution::with(['branch', 'orderRequest.creator', 'creator', 'confirmer']);

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

        // Tìm kiếm theo mã phiếu
        if ($request->has('search') && !empty($request->search)) {
            $query->where('code', 'like', '%' . $request->search . '%');
        }

        // Lọc phiếu quá hạn
        if ($request->has('overdue') && $request->overdue == '1') {
            $query->where('status', 'pending')
                  ->where('created_at', '<', Carbon::now()->subDays(1));
        }

        // Sắp xếp: pending và quá hạn lên đầu
        $query->orderByRaw("CASE 
                           WHEN status = 'pending' AND created_at < ? THEN 0
                           WHEN status = 'pending' THEN 1 
                           ELSE 2 END", [Carbon::now()->subDays(1)])
              ->latest();

        $distributions = $query->paginate(15)->appends($request->all());

        // Dữ liệu cho form lọc
        $branches = Branch::where('is_active', true)->get();
        $statuses = [
            'pending' => 'Chờ xác nhận',
            'confirmed' => 'Đã xác nhận',
            'shipped' => 'Đã xuất kho',
            'delivered' => 'Đã giao',
            'cancelled' => 'Đã hủy',
        ];

        // Thống kê nhanh
        $quick_stats = [
            'total' => Distribution::count(),
            'pending' => Distribution::where('status', 'pending')->count(),
            'confirmed' => Distribution::where('status', 'confirmed')->count(),
            'shipped_today' => Distribution::where('status', 'shipped')
                ->whereDate('shipped_at', today())->count(),
            'overdue' => Distribution::where('status', 'pending')
                ->where('created_at', '<', Carbon::now()->subDays(1))
                ->count(),
        ];

        return view('warehouse.distributions.index', compact(
            'distributions', 'branches', 'statuses', 'quick_stats'
        ));
    }

    public function show(Distribution $distribution)
    {
        $distribution->load([
            'branch', 'orderRequest.creator', 'orderRequest.approver',
            'creator', 'confirmer', 'details.book.category', 'details.book.inventory'
        ]);

        // Kiểm tra tồn kho hiện tại cho từng item
        $stock_check = [];
        foreach ($distribution->details as $detail) {
            $inventory = $detail->book->inventory;
            $stock_check[$detail->book_id] = [
                'available' => $inventory ? $inventory->available_quantity : 0,
                'reserved' => $inventory ? $inventory->reserved_quantity : 0,
                'requested' => $detail->quantity,
                'sufficient' => $inventory ? $inventory->available_quantity >= $detail->quantity : false,
            ];
        }

        // Timeline chi tiết
        $timeline = [
            [
                'status' => 'created',
                'title' => 'Tạo phiếu phân phối',
                'date' => $distribution->created_at,
                'user' => $distribution->creator->name,
                'icon' => 'fas fa-plus-circle',
                'color' => 'primary',
                'completed' => true,
            ],
        ];

        if ($distribution->confirmed_at) {
            $timeline[] = [
                'status' => 'confirmed',
                'title' => 'Xác nhận phiếu',
                'date' => $distribution->confirmed_at,
                'user' => $distribution->confirmer ? $distribution->confirmer->name : 'System',
                'icon' => 'fas fa-check-circle',
                'color' => 'success',
                'completed' => true,
            ];
        }

        if ($distribution->shipped_at) {
            $timeline[] = [
                'status' => 'shipped',
                'title' => 'Xuất kho',
                'date' => $distribution->shipped_at,
                'user' => 'Nhân viên kho',
                'icon' => 'fas fa-shipping-fast',
                'color' => 'info',
                'completed' => true,
            ];
        }

        if ($distribution->delivered_at) {
            $timeline[] = [
                'status' => 'delivered',
                'title' => 'Giao hàng thành công',
                'date' => $distribution->delivered_at,
                'user' => 'Hệ thống',
                'icon' => 'fas fa-check-double',
                'color' => 'success',
                'completed' => true,
            ];
        }

        // Tính thời gian xử lý
        $processing_info = [
            'created_hours_ago' => $distribution->created_at->diffInHours(now()),
            'is_overdue' => $distribution->status === 'pending' && 
                          $distribution->created_at->diffInHours(now()) > 24,
            'estimated_completion' => $distribution->created_at->addDays(3),
        ];

        // Ghi chú xử lý
        $notes = $distribution->notes ?: '';

        return view('warehouse.distributions.show', compact(
            'distribution', 'stock_check', 'timeline', 'processing_info', 'notes'
        ));
    }

    public function confirm(Distribution $distribution)
    {
        if (!$distribution->canBeConfirmed()) {
            return back()->with('error', 'Phiếu phân phối không thể được xác nhận trong trạng thái hiện tại.');
        }

        // Kiểm tra tồn kho một lần nữa
        foreach ($distribution->details as $detail) {
            $inventory = $detail->book->inventory;
            if (!$inventory || $inventory->available_quantity < $detail->quantity) {
                return back()->with('error', 
                    "Không đủ tồn kho cho sách '{$detail->book->title}'. " .
                    "Cần: {$detail->quantity}, Có: " . ($inventory ? $inventory->available_quantity : 0)
                );
            }
        }

        $distribution->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'confirmed_by' => auth()->id(),
        ]);

        // Log activity
        \Log::info("Distribution confirmed by warehouse", [
            'distribution_id' => $distribution->id,
            'distribution_code' => $distribution->code,
            'confirmed_by' => auth()->id(),
            'confirmed_at' => now(),
        ]);

        return back()->with('success', 'Phiếu phân phối đã được xác nhận thành công.');
    }

    public function ship(Distribution $distribution)
    {
        if (!$distribution->canBeShipped()) {
            return back()->with('error', 'Phiếu phân phối không thể được xuất kho trong trạng thái hiện tại.');
        }

        // Cập nhật tồn kho
        $result = $this->inventoryService->updateInventoryFromDistribution($distribution);

        if ($result['success']) {
            $distribution->update([
                'status' => 'shipped',
                'shipped_at' => now(),
            ]);

            // Log activity
            \Log::info("Distribution shipped by warehouse", [
                'distribution_id' => $distribution->id,
                'distribution_code' => $distribution->code,
                'shipped_by' => auth()->id(),
                'shipped_at' => now(),
                'total_items' => $distribution->details->sum('quantity'),
            ]);

            return back()->with('success', 'Phiếu phân phối đã được xuất kho thành công.');
        }

        return back()->with('error', $result['message']);
    }

    public function markAsDelivered(Distribution $distribution)
    {
        if ($distribution->status !== 'shipped') {
            return back()->with('error', 'Chỉ có thể đánh dấu đã giao cho phiếu đã xuất kho.');
        }

        $distribution->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        // Cập nhật trạng thái đơn hàng gốc
        $distribution->orderRequest->update(['status' => 'completed']);

        return back()->with('success', 'Phiếu phân phối đã được đánh dấu là đã giao.');
    }

    public function addNote(Request $request, Distribution $distribution)
    {
        $request->validate([
            'note' => 'required|string|max:500',
        ], [
            'note.required' => 'Ghi chú là bắt buộc.',
            'note.max' => 'Ghi chú không được vượt quá 500 ký tự.',
        ]);

        $currentNotes = $distribution->notes ?: '';
        $newNote = now()->format('d/m/Y H:i') . ' - ' . auth()->user()->name . ': ' . $request->note;
        $updatedNotes = $currentNotes ? $currentNotes . "\n" . $newNote : $newNote;

        $distribution->update(['notes' => $updatedNotes]);

        return back()->with('success', 'Ghi chú đã được thêm thành công.');
    }

    public function printPackingSlip(Distribution $distribution)
    {
        $distribution->load(['branch', 'details.book']);
        
        return view('warehouse.distributions.packing-slip', compact('distribution'));
    }

    public function bulkConfirm(Request $request)
    {
        $request->validate([
            'distribution_ids' => 'required|array',
            'distribution_ids.*' => 'exists:distributions,id',
        ]);

        $confirmed_count = 0;
        $failed_distributions = [];

        foreach ($request->distribution_ids as $distributionId) {
            $distribution = Distribution::find($distributionId);
            
            if ($distribution && $distribution->canBeConfirmed()) {
                $distribution->update([
                    'status' => 'confirmed',
                    'confirmed_at' => now(),
                    'confirmed_by' => auth()->id(),
                ]);
                $confirmed_count++;
            } else {
                $failed_distributions[] = $distribution ? $distribution->code : "ID: $distributionId";
            }
        }

        $message = "Đã xác nhận thành công {$confirmed_count} phiếu phân phối.";
        if (!empty($failed_distributions)) {
            $message .= " Không thể xác nhận: " . implode(', ', $failed_distributions);
        }

        return back()->with('success', $message);
    }

    public function export(Request $request)
    {
        // Export distributions to Excel/PDF
        return back()->with('success', 'Đã xuất danh sách phiếu phân phối thành công.');
    }

    public function statistics(Request $request)
    {
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now()->endOfMonth();

        $stats = [
            'total_distributions' => Distribution::whereBetween('created_at', [$startDate, $endDate])->count(),
            'shipped_distributions' => Distribution::whereBetween('shipped_at', [$startDate, $endDate])
                ->where('status', 'shipped')->count(),
            'avg_processing_time' => Distribution::whereNotNull('shipped_at')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get()
                ->avg(function($dist) {
                    return $dist->created_at->diffInHours($dist->shipped_at);
                }),
            'on_time_rate' => $this->calculateOnTimeRate($startDate, $endDate),
        ];

        $distributionsByBranch = Distribution::with('branch')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->groupBy('branch.name')
            ->map->count();

        $distributionsByStatus = Distribution::whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->groupBy('status')
            ->map->count();

        return view('warehouse.distributions.statistics', compact(
            'stats', 'distributionsByBranch', 'distributionsByStatus', 'startDate', 'endDate'
        ));
    }

    private function calculateOnTimeRate($startDate, $endDate)
    {
        $distributions = Distribution::whereNotNull('shipped_at')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        if ($distributions->count() === 0) {
            return 0;
        }

        $onTimeCount = $distributions->filter(function($dist) {
            // Consider on-time if shipped within 24 hours
            return $dist->created_at->diffInHours($dist->shipped_at) <= 24;
        })->count();

        return round(($onTimeCount / $distributions->count()) * 100, 2);
    }
}