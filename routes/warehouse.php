<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Warehouse\{
    WarehouseController, // Đổi tên thành DashboardController nếu bạn dùng tên đó
    ExportController, // Sử dụng ExportController cho Distributions
    ImportController,
    InventoryController // Cho tồn kho tổng mà Warehouse quản lý
};

Route::middleware(['auth', 'role:warehouse'])->name('warehouse.')->prefix('warehouse')->group(function () {
    Route::get('/dashboard', [WarehouseController::class, 'index'])->name('dashboard'); // Nếu WarehouseController là DashboardController

    Route::resource('imports', ImportController::class)->only(['index', 'show']);
    // Thêm action xác nhận hoàn thành nhập kho
    Route::post('imports/{import}/complete', [ImportController::class, 'complete'])->name('imports.complete');

    // ExportController sẽ quản lý Distributions từ góc nhìn của Warehouse
    Route::resource('distributions', ExportController::class)->only(['index', 'show']);
    // Thêm action xác nhận xuất kho
    Route::post('distributions/{distribution}/ship', [ExportController::class, 'ship'])->name('distributions.ship');
    
    // Tồn kho tổng cho Warehouse
    Route::get('inventories', [InventoryController::class, 'index'])->name('inventories.index');
});