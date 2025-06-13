<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController; // Đảm bảo bạn có LoginController

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Authentication Routes
Route::get('/', function () {
    return view('auth.login'); // Chuyển hướng về trang đăng nhập mặc định
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Route Home/Dashboard sau khi đăng nhập (sẽ được chuyển hướng tùy theo vai trò)
Route::middleware(['auth'])->group(function () {
    Route::get('/home', function () {
        // Chuyển hướng người dùng về dashboard của vai trò của họ
        $user = Auth::user();
        if ($user->role == \App\Enums\UserRole::ADMIN) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->role == \App\Enums\UserRole::MANAGER) {
            return redirect()->route('manager.dashboard');
        } elseif ($user->role == \App\Enums\UserRole::WAREHOUSE) {
            return redirect()->route('warehouse.dashboard');
        } elseif ($user->role == \App\Enums\UserRole::BRANCH) {
            return redirect()->route('branch.dashboard');
        }
        return redirect()->route('login'); // Fallback nếu không có vai trò nào khớp
    })->name('home');
});

// Import các file routes cho từng vai trò
require __DIR__.'/admin.php';
// require __DIR__.'/manager.php';
// require __DIR__.'/warehouse.php';
// require __DIR__.'/branch.php';