<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Branch\{
    BranchController, // Đổi tên thành DashboardController nếu bạn dùng tên đó
    OrderController, // Quản lý yêu cầu đặt sách
    BranchInventoryController,
    DistributionController // Để chi nhánh xác nhận nhận hàng
};

Route::middleware(['auth', 'role:branch'])->name('branch.')->prefix('branch')->group(function () {
    Route::get('/dashboard', [BranchController::class, 'index'])->name('dashboard'); // Nếu BranchController là DashboardController

    Route::resource('order_requests', OrderController::class); // Branch có đầy đủ CRUD cho yêu cầu của mình
    // Thêm action hủy yêu cầu
    Route::post('order_requests/{orderRequest}/cancel', [OrderController::class, 'cancel'])->name('order_requests.cancel');

    // Tồn kho chi nhánh
    Route::get('branch_inventories', [BranchInventoryController::class, 'index'])->name('branch_inventories.index');

    // Phiếu phân phối (Branch nhận hàng)
    Route::resource('distributions', DistributionController::class)->only(['index', 'show']);
    // Thêm action xác nhận nhận hàng
    Route::post('distributions/{distribution}/receive', [DistributionController::class, 'receive'])->name('distributions.receive');
});
