<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\{
    AdminController, 
    BookController,
    BranchController,
    UserController,
    SupplierController,
    CategoryController,
    OrderRequestController,
    DistributionController,
    ImportController,
    InventoryController,
    BranchInventoryController
};

Route::middleware(['auth', 'role:admin'])->name('admin.')->prefix('admin')->group(function () {
     Route::get('dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('reports', [AdminController::class, 'reports'])->name('reports');

    // Quản lý chi nhánh
    Route::resource('branches', BranchController::class);

    // Quản lý sách
    Route::resource('books', BookController::class);

    // Quản lý thể loại
    Route::resource('categories', CategoryController::class);

    // Quản lý đơn hàng
    Route::resource('orders', OrderRequestController::class);

    // Quản lý người dùng
    Route::resource('users', UserController::class);

    // Quản lý nhà cung cấp
    Route::resource('suppliers', SupplierController::class);

    // Phân phối sách
    Route::resource('distributions', DistributionController::class);

    // Nhập hàng
    Route::resource('imports', ImportController::class);

    // Kiểm kê kho
    Route::resource('inventories', InventoryController::class);
});