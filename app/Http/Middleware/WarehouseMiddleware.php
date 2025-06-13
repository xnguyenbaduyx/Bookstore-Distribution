<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class WarehouseMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (!auth()->user()->isWarehouse()) {
            abort(403, 'Bạn không có quyền truy cập vào trang này.');
        }

        return $next($request);
    }
}