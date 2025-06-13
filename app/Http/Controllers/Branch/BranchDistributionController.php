<?php

namespace App\Http\Controllers\Branch;

use App\Http\Controllers\Controller;
use App\Models\Distribution;
use App\Enums\DistributionStatus; // Đảm bảo Enum DistributionStatus đã được định nghĩa
use App\Services\InventoryService; // Cập nhật tồn kho (nếu cần)
use App\Services\NotificationService; // Gửi thông báo
use Illuminate\Http\Request;

class BranchDistributionController extends Controller
{
    protected $inventoryService;
    protected $notificationService;

    public function __construct(InventoryService $inventoryService, NotificationService $notificationService)
    {
        $this->inventoryService = $inventoryService;
        $this->notificationService = $notificationService;
    }

    public function index(Request $request)
    {
        $branchId = auth()->user()->branch_id;

        if (!$branchId) {
            return redirect()->route('home')->with('error', 'Bạn không thuộc chi nhánh nào.');
        }

        $query = Distribution::with(['creator', 'orderRequest'])
                             ->where('branch_id', $branchId); // Chỉ lấy phiếu phân phối của chi nhánh này

        // Bộ lọc trạng thái
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            // Mặc định chỉ hiển thị phiếu đang chờ nhận hoặc đã nhận
            $query->whereIn('status', [DistributionStatus::SHIPPED, DistributionStatus::DELIVERED]);
        }

        $distributions = $query->latest()->paginate(15);

        return view('branch.distributions.index', compact('distributions'));
    }

    public function show(Distribution $distribution)
    {
        // Đảm bảo chỉ chi nhánh của người dùng mới có thể xem phiếu phân phối này
        if ($distribution->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Bạn không có quyền xem phiếu phân phối này.');
        }

        $distribution->load([
            'creator',
            'orderRequest.creator', // Lấy thông tin người tạo yêu cầu gốc
            'details.book'
        ]);

        return view('branch.distributions.show', compact('distribution'));
    }

    // Phương thức để chi nhánh xác nhận đã nhận hàng
    public function confirmReceived(Distribution $distribution)
    {
        // Đảm bảo chỉ chi nhánh của người dùng mới có thể xác nhận
        if ($distribution->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Bạn không có quyền thực hiện hành động này.');
        }

        // Chỉ cho phép xác nhận khi phiếu đang ở trạng thái 'shipped' (đã xuất kho)
        if ($distribution->status !== DistributionStatus::SHIPPED) {
            return redirect()->back()->with('error', 'Phiếu phân phối này không thể xác nhận nhận hàng.');
        }

        // Gọi service để cập nhật tồn kho chi nhánh
        // Lý tưởng là InventoryService sẽ xử lý việc tăng tồn kho của chi nhánh
        // và cập nhật trạng thái phiếu phân phối thành 'delivered'
        $result = $this->inventoryService->confirmBranchReceivedDistribution($distribution);

        if ($result['success']) {
            // Gửi thông báo đến kho trung tâm hoặc quản lý rằng chi nhánh đã nhận được hàng
            $this->notificationService->notifyBranchReceivedDistribution($distribution);
            return redirect()->route('branch.distributions.show', $distribution)
                ->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }

    // Chi nhánh có thể báo cáo vấn đề với phiếu phân phối
    public function reportIssue(Request $request, Distribution $distribution)
    {
        // Đảm bảo chỉ chi nhánh của người dùng mới có thể báo cáo
        if ($distribution->branch_id !== auth()->user()->branch_id) {
            abort(403, 'Bạn không có quyền thực hiện hành động này.');
        }

        // Chỉ cho phép báo cáo vấn đề khi phiếu đang ở trạng thái 'shipped' hoặc 'delivered' (nếu có vấn đề phát sinh sau khi nhận)
        if (!in_array($distribution->status, [DistributionStatus::SHIPPED, DistributionStatus::DELIVERED])) {
            return redirect()->back()->with('error', 'Phiếu phân phối này không thể báo cáo vấn đề.');
        }

        $request->validate([
            'issue_description' => 'required|string|max:1000',
        ]);

        // Logic để lưu vấn đề, ví dụ: tạo một bản ghi `DistributionIssue`
        // hoặc thêm trường `issue_description` và `has_issue` vào bảng `distributions`
        $distribution->update([
            'has_issue' => true,
            'issue_description' => $request->issue_description,
            // Có thể thêm trạng thái mới như 'issue_reported'
        ]);

        // Gửi thông báo đến quản lý trung tâm hoặc kho về vấn đề
        $this->notificationService->notifyDistributionIssueReported($distribution, $request->issue_description);

        return redirect()->back()->with('success', 'Vấn đề đã được báo cáo thành công.');
    }
}