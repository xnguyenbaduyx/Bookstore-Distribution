<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $query = Branch::query();

        // Tìm kiếm
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('manager_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Lọc theo trạng thái
        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', $request->status);
        }

        $branches = $query->withCount(['users', 'orderRequests'])
            ->latest()
            ->paginate(10)
            ->appends($request->all());

        return view('admin.branches.index', compact('branches'));
    }

    public function create()
    {
        return view('admin.branches.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:branches',
            'address' => 'required|string',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255|unique:branches',
            'manager_name' => 'required|string|max:255',
        ], [
            'name.required' => 'Tên chi nhánh là bắt buộc.',
            'code.required' => 'Mã chi nhánh là bắt buộc.',
            'code.unique' => 'Mã chi nhánh đã tồn tại.',
            'email.unique' => 'Email đã được sử dụng.',
            'email.email' => 'Email không đúng định dạng.',
            'address.required' => 'Địa chỉ là bắt buộc.',
            'phone.required' => 'Số điện thoại là bắt buộc.',
            'manager_name.required' => 'Tên người quản lý là bắt buộc.',
        ]);

        Branch::create($request->all());

        return redirect()->route('admin.branches.index')
            ->with('success', 'Chi nhánh đã được tạo thành công.');
    }

    public function show(Branch $branch)
    {
        $branch->load(['users', 'orderRequests.creator']);
        
        $stats = [
            'total_users' => $branch->users()->where('is_active', true)->count(),
            'total_orders' => $branch->orderRequests()->count(),
            'pending_orders' => $branch->orderRequests()->where('status', 'pending')->count(),
            'approved_orders' => $branch->orderRequests()->where('status', 'approved')->count(),
            'completed_orders' => $branch->orderRequests()->where('status', 'completed')->count(),
            'total_order_value' => $branch->orderRequests()
                ->with('details')
                ->get()
                ->sum(function($order) {
                    return $order->details->sum('total_price');
                }),
        ];

        $recent_orders = $branch->orderRequests()
            ->with('creator')
            ->latest()
            ->take(10)
            ->get();

        return view('admin.branches.show', compact('branch', 'stats', 'recent_orders'));
    }

    public function edit(Branch $branch)
    {
        return view('admin.branches.edit', compact('branch'));
    }

    public function update(Request $request, Branch $branch)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:branches,code,' . $branch->id,
            'address' => 'required|string',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255|unique:branches,email,' . $branch->id,
            'manager_name' => 'required|string|max:255',
        ], [
            'name.required' => 'Tên chi nhánh là bắt buộc.',
            'code.required' => 'Mã chi nhánh là bắt buộc.',
            'code.unique' => 'Mã chi nhánh đã tồn tại.',
            'email.unique' => 'Email đã được sử dụng.',
            'email.email' => 'Email không đúng định dạng.',
            'address.required' => 'Địa chỉ là bắt buộc.',
            'phone.required' => 'Số điện thoại là bắt buộc.',
            'manager_name.required' => 'Tên người quản lý là bắt buộc.',
        ]);

        $branch->update($request->all());

        return redirect()->route('admin.branches.index')
            ->with('success', 'Chi nhánh đã được cập nhật thành công.');
    }

    public function destroy(Branch $branch)
    {
        // Kiểm tra chi nhánh có người dùng đang hoạt động không
        if ($branch->users()->where('is_active', true)->count() > 0) {
            return back()->with('error', 'Không thể xóa chi nhánh có người dùng đang hoạt động.');
        }

        // Kiểm tra chi nhánh có đơn hàng đang chờ duyệt không
        if ($branch->orderRequests()->where('status', 'pending')->count() > 0) {
            return back()->with('error', 'Không thể xóa chi nhánh có đơn hàng đang chờ duyệt.');
        }

        $branch->update(['is_active' => false]);
        
        return redirect()->route('admin.branches.index')
            ->with('success', 'Chi nhánh đã được vô hiệu hóa.');
    }

    public function restore($id)
    {
        $branch = Branch::find($id);
        if ($branch) {
            $branch->update(['is_active' => true]);
            return back()->with('success', 'Chi nhánh đã được khôi phục.');
        }
        return back()->with('error', 'Không tìm thấy chi nhánh.');
    }
}