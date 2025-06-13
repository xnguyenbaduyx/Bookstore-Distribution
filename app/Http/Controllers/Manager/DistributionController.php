<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Distribution;
use App\Models\Branch;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DistributionController extends Controller
{
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

        // Sắp xếp: pending lên đầu
        $query->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
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
            'shipped_today' => Distribution::where('status', 'shipped')
                ->whereDate('shipped_at', today())->count(),
            'overdue' => Distribution::where('status', 'pending')
                ->where('created_at', '<', Carbon::now()->subDays(2))
                ->count(),
        ];

        return view('manager.distributions.index', compact(
            'distributions', 'branches', 'statuses', 'quick_stats'
        ));
    }

    public function show(Distribution $distribution)
    {
        $distribution->load([
            'branch', 'orderRequest.creator', 'orderRequest.approver',
            'creator', 'confirmer', 'details.book.category'
        ]);

        // Timeline của phiếu phân phối
        $timeline = [
            [
                'status' => 'created',
                'title' => 'Tạo phiếu phân phối',
                'date' => $distribution->created_at,
                'user' => $distribution->creator->name,
                'completed' => true,
            ],
        ];

        if ($distribution->confirmed_at) {
            $timeline[] = [
                'status' => 'confirmed',
                'title' => 'Xác nhận phiếu',
                'date' => $distribution->confirmed_at,
                'user' => $distribution->confirmer ? $distribution->confirmer->name : 'System',
                'completed' => true,
            ];
        }

        if ($distribution->shipped_at) {
            $timeline[] = [
                'status' => 'shipped',
                'title' => 'Xuất kho',
                'date' => $distribution->shipped_at,
                'user' => 'Nhân viên kho',
                'completed' => true,
            ];
        }

        if ($distribution->delivered_at) {
            $timeline[] = [
                'status' => 'delivered',
                'title' => 'Giao hàng thành công',
                'date' => $distribution->delivered_at,
                'user' => 'Hệ thống',
                'completed' => true,
            ];
        }

        // Tính thời gian xử lý
        $processing_time = null;
        if ($distribution->delivered_at) {
            $processing_time = $distribution->created_at->diffInHours($distribution->delivered_at);
        }

        return view('manager.distributions.show', compact(
            'distribution', 'timeline', 'processing_time'
        ));
    }

    public function cancel(Request $request, Distribution $distribution)
    {
        $request->validate([
            'cancellation_reason' => 'required|string|max:500',
        ], [
            'cancellation_reason.required' => 'Lý do hủy là bắt buộc.',
            'cancellation_reason.max' => 'Lý do hủy không được vượt quá 500 ký tự.',
        ]);

        if (!$distribution->canBeCancelled()) {
            return back()->with('error', 'Phiếu phân phối không thể hủy trong trạng thái hiện tại.');
        }

        $distribution->update([
            'status' => 'cancelled',
            'notes' => $request->cancellation_reason,
        ]);

        // Giải phóng stock đã reserve
        foreach ($distribution->details as $detail) {
            $inventory = $detail->book->inventory;
            if ($inventory) {
                $inventory->releaseStock($detail->quantity);
            }
        }

        // Cập nhật lại trạng thái order request
        $distribution->orderRequest->update(['status' => 'approved']);

        return back()->with('success', 'Phiếu phân phối đã được hủy thành công.');
    }

    public function tracking(Distribution $distribution)
    {
        // Tracking information for distribution
        $tracking_info = [
            'current_status' => $distribution->status,
            'estimated_delivery' => $distribution->created_at->addDays(3),
            'last_update' => $distribution->updated_at,
            'progress_percentage' => $this->calculateProgressPercentage($distribution->status),
        ];

        return view('manager.distributions.tracking', compact('distribution', 'tracking_info'));
    }

    private function calculateProgressPercentage($status)
    {
        $progress_map = [
            'pending' => 25,
            'confirmed' => 50,
            'shipped' => 75,
            'delivered' => 100,
            'cancelled' => 0,
        ];

        return $progress_map[$status] ?? 0;
    }

    public function printLabel(Distribution $distribution)
    {
        // Generate shipping label
        return view('manager.distributions.label', compact('distribution'));
    }
}