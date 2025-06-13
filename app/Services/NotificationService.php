<?php

namespace App\Services;

use App\Models\User;
use App\Models\OrderRequest;
use App\Models\Distribution;
use App\Models\Import;
use Illuminate\Support\Facades\Log; // Sử dụng facade Log
// use Illuminate\Support\Facades\Mail; // Bỏ comment nếu bạn muốn sử dụng Laravel Mail
// use App\Mail\YourNotificationMail; // Tạo class Mail riêng nếu sử dụng

class NotificationService
{
    public function notifyNewOrderRequest(OrderRequest $orderRequest)
    {
        $managers = User::where('role', 'manager')->get();
        
        foreach ($managers as $manager) {
            $this->sendEmailNotification($manager->email, [
                'subject' => 'Yêu cầu đặt sách mới',
                'message' => "Chi nhánh {$orderRequest->branch->name} đã tạo yêu cầu đặt sách #{$orderRequest->code}",
                'action_url' => route('manager.orders.show', $orderRequest->id)
            ]);
        }
    }

    public function notifyOrderApproved(OrderRequest $orderRequest)
    {
        $branchUsers = User::where('branch_id', $orderRequest->branch_id)->get();
        
        foreach ($branchUsers as $user) {
            $this->sendEmailNotification($user->email, [
                'subject' => 'Yêu cầu đặt sách đã được duyệt',
                'message' => "Yêu cầu đặt sách #{$orderRequest->code} đã được duyệt",
                'action_url' => route('branch.orders.show', $orderRequest->id)
            ]);
        }
    }

    public function notifyOrderRejected(OrderRequest $orderRequest)
    {
        $branchUsers = User::where('branch_id', $orderRequest->branch_id)->get();
        
        foreach ($branchUsers as $user) {
            $this->sendEmailNotification($user->email, [
                'subject' => 'Yêu cầu đặt sách bị từ chối',
                'message' => "Yêu cầu đặt sách #{$orderRequest->code} bị từ chối. Lý do: {$orderRequest->rejection_reason}",
                'action_url' => route('branch.orders.show', $orderRequest->id)
            ]);
        }
    }

    public function notifyNewDistribution(Distribution $distribution)
    {
        $warehouseUsers = User::where('role', 'warehouse')->get();
        
        foreach ($warehouseUsers as $user) {
            $this->sendEmailNotification($user->email, [
                'subject' => 'Phiếu phân phối mới',
                'message' => "Phiếu phân phối #{$distribution->code} cần được xử lý",
                'action_url' => route('warehouse.exports.show', $distribution->id) // Chú ý: đã đổi route name thành exports.show
            ]);
        }
    }

    public function notifyNewImport(Import $import)
    {
        $warehouseUsers = User::where('role', 'warehouse')->get();
        
        foreach ($warehouseUsers as $user) {
            $this->sendEmailNotification($user->email, [
                'subject' => 'Phiếu nhập hàng mới',
                'message' => "Phiếu nhập hàng #{$import->code} từ nhà cung cấp {$import->supplier->name} cần được xử lý",
                'action_url' => route('warehouse.imports.show', $import->id)
            ]);
        }
    }

    /**
     * MỚI: Thông báo khi phiếu phân phối đã được xuất kho (shipped).
     * Gửi đến chi nhánh nhận hàng.
     */
    public function notifyDistributionShipped(Distribution $distribution)
    {
        $branchUsers = User::where('branch_id', $distribution->branch_id)->get();

        foreach ($branchUsers as $user) {
            $this->sendEmailNotification($user->email, [
                'subject' => 'Phiếu phân phối đã xuất kho',
                'message' => "Phiếu phân phối #{$distribution->code} đã được xuất kho và đang trên đường đến chi nhánh của bạn.",
                'action_url' => route('branch.distributions.show', $distribution->id)
            ]);
        }
    }

    /**
     * MỚI: Thông báo khi chi nhánh xác nhận đã nhận phiếu phân phối.
     * Gửi đến người tạo phiếu phân phối (Quản lý) và Nhân viên kho liên quan.
     */
    public function notifyBranchReceivedDistribution(Distribution $distribution)
    {
        // Thông báo cho người tạo phiếu (Manager)
        if ($distribution->creator) {
            $this->sendEmailNotification($distribution->creator->email, [
                'subject' => 'Chi nhánh đã nhận hàng',
                'message' => "Chi nhánh {$distribution->branch->name} đã xác nhận nhận phiếu phân phối #{$distribution->code}.",
                'action_url' => route('manager.distributions.show', $distribution->id)
            ]);
        }

        // Thông báo cho nhân viên kho (người đã ship)
        // Cần xem xét ai là người ship và có muốn thông báo cho tất cả warehouse users không
        $warehouseUsers = User::where('role', 'warehouse')->get(); // Gửi cho tất cả warehouse
        foreach ($warehouseUsers as $user) {
            $this->sendEmailNotification($user->email, [
                'subject' => 'Xác nhận giao hàng',
                'message' => "Chi nhánh {$distribution->branch->name} đã xác nhận nhận phiếu phân phối #{$distribution->code}.",
                'action_url' => route('warehouse.exports.show', $distribution->id)
            ]);
        }
    }

    /**
     * MỚI: Thông báo khi chi nhánh báo cáo vấn đề với phiếu phân phối.
     * Gửi đến quản lý trung tâm và nhân viên kho.
     */
    public function notifyDistributionIssueReported(Distribution $distribution, string $issueDescription)
    {
        $managers = User::where('role', 'manager')->get();
        foreach ($managers as $manager) {
            $this->sendEmailNotification($manager->email, [
                'subject' => 'Vấn đề phiếu phân phối',
                'message' => "Chi nhánh {$distribution->branch->name} báo cáo vấn đề với phiếu phân phối #{$distribution->code}. Vấn đề: {$issueDescription}",
                'action_url' => route('manager.distributions.show', $distribution->id)
            ]);
        }

        $warehouseUsers = User::where('role', 'warehouse')->get();
        foreach ($warehouseUsers as $user) {
            $this->sendEmailNotification($user->email, [
                'subject' => 'Vấn đề phiếu phân phối',
                'message' => "Chi nhánh {$distribution->branch->name} báo cáo vấn đề với phiếu phân phối #{$distribution->code}. Vấn đề: {$issueDescription}",
                'action_url' => route('warehouse.exports.show', $distribution->id)
            ]);
        }
    }

    /**
     * MỚI: Thông báo khi phiếu nhập hàng bị hủy (từ Warehouse).
     * Gửi đến quản lý trung tâm.
     */
    public function notifyImportCancelled(Import $import)
    {
        $managers = User::where('role', 'manager')->get();
        foreach ($managers as $manager) {
            $this->sendEmailNotification($manager->email, [
                'subject' => 'Phiếu nhập hàng đã hủy',
                'message' => "Phiếu nhập hàng #{$import->code} từ nhà cung cấp {$import->supplier->name} đã bị hủy bởi nhân viên kho.",
                'action_url' => route('manager.imports.show', $import->id)
            ]);
        }
    }

    /**
     * MỚI: Thông báo khi phiếu phân phối bị hủy (từ Manager/Warehouse).
     * Gửi đến chi nhánh liên quan.
     */
    public function notifyDistributionCancelled(Distribution $distribution)
    {
        $branchUsers = User::where('branch_id', $distribution->branch_id)->get();
        foreach ($branchUsers as $user) {
            $this->sendEmailNotification($user->email, [
                'subject' => 'Phiếu phân phối đã hủy',
                'message' => "Phiếu phân phối #{$distribution->code} đến chi nhánh của bạn đã bị hủy.",
                'action_url' => route('branch.distributions.show', $distribution->id)
            ]);
        }
    }


    private function sendEmailNotification($email, $data)
    {
        // Implement your actual email sending logic here
        // For now, we'll just log it.
        Log::info("Sending email to {$email}: Subject - '{$data['subject']}' | Message - '{$data['message']}' | URL - '{$data['action_url']}'");

        // Example using Laravel Mail:
        // try {
        //     Mail::to($email)->send(new YourNotificationMail($data));
        // } catch (\Exception $e) {
        //     Log::error("Failed to send email to {$email}: " . $e->getMessage());
        // }
    }
}