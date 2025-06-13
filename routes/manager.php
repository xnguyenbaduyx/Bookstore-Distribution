<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Manager\{
    ManagerController, // Đổi tên thành DashboardController nếu bạn dùng tên đó
    OrderRequestController,
    DistributionController,
    ImportController
};

Route::middleware(['auth', 'role:manager'])->name('manager.')->prefix('manager')->group(function () {
    Route::get('/dashboard', [ManagerController::class, 'index'])->name('dashboard'); // Nếu ManagerController là DashboardController

    Route::resource('order_requests', OrderRequestController::class)->only(['index', 'show']);
    // Thêm route cho duyệt/từ chối
    Route::post('order_requests/{orderRequest}/approve', [OrderRequestController::class, 'approve'])->name('order_requests.approve');
    Route::post('order_requests/{orderRequest}/reject', [OrderRequestController::class, 'reject'])->name('order_requests.reject');

    Route::resource('distributions', DistributionController::class)->only(['index', 'show']);
    // Thêm các action khác nếu Manager có thể tạo/sửa/hủy Distribution
    // Route::get('distributions/create', [DistributionController::class, 'create'])->name('distributions.create');
    // Route::post('distributions', [DistributionController::class, 'store'])->name('distributions.store');


    Route::resource('imports', ImportController::class)->only(['index', 'show', 'create', 'store']);
    // Thêm các action khác nếu Manager có thể sửa/hủy Import
    // Route::post('imports/{import}/cancel', [ImportController::class, 'cancel'])->name('imports.cancel');

});