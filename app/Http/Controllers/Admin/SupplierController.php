<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Branch;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('branch');

        // Tìm kiếm
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Lọc theo vai trò
        if ($request->has('role') && !empty($request->role)) {
            $query->where('role', $request->role);
        }

        // Lọc theo chi nhánh
        if ($request->has('branch') && !empty($request->branch)) {
            $query->where('branch_id', $request->branch);
        }

        // Lọc theo trạng thái
        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', $request->status);
        }

        $users = $query->latest()->paginate(15)->appends($request->all());
        $branches = Branch::where('is_active', true)->get();
        $roles = UserRole::labels();

        return view('admin.users.index', compact('users', 'branches', 'roles'));
    }

    public function create()
    {
        $branches = Branch::where('is_active', true)->get();
        $roles = UserRole::labels();
        return view('admin.users.create', compact('branches', 'roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users|max:255',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:' . implode(',', UserRole::all()),
            'branch_id' => 'nullable|exists:branches,id',
        ], [
            'name.required' => 'Tên là bắt buộc.',
            'email.required' => 'Email là bắt buộc.',
            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Email đã được sử dụng.',
            'password.required' => 'Mật khẩu là bắt buộc.',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'role.required' => 'Vai trò là bắt buộc.',
            'role.in' => 'Vai trò không hợp lệ.',
        ]);

        $data = $request->all();
        $data['password'] = Hash::make($request->password);

        // Người dùng chi nhánh phải có branch_id
        if ($request->role === 'branch' && !$request->branch_id) {
            return back()->withErrors(['branch_id' => 'Chi nhánh là bắt buộc cho người dùng chi nhánh.']);
        }

        // Người dùng không phải chi nhánh thì không cần branch_id
        if ($request->role !== 'branch') {
            $data['branch_id'] = null;
        }

        User::create($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'Người dùng đã được tạo thành công.');
    }

    public function show(User $user)
    {
        $user->load('branch');
        
        $stats = [
            'total_orders' => 0,
            'approved_orders' => 0,
            'rejected_orders' => 0,
            'last_login' => $user->updated_at,
        ];

        // Tính thống kê dựa trên vai trò
        if ($user->role === 'branch') {
            $stats['total_orders'] = $user->orderRequests()->count();
            $stats['approved_orders'] = $user->orderRequests()->where('status', 'approved')->count();
            $stats['rejected_orders'] = $user->orderRequests()->where('status', 'rejected')->count();
            
            $recent_orders = $user->orderRequests()->with('branch')->latest()->take(5)->get();
        } elseif ($user->role === 'manager') {
            $stats['approved_orders'] = $user->approvedOrders()->count();
            $stats['rejected_orders'] = $user->approvedOrders()->where('status', 'rejected')->count();
            
            $recent_orders = $user->approvedOrders()->with('branch')->latest()->take(5)->get();
        } else {
            $recent_orders = collect();
        }

        return view('admin.users.show', compact('user', 'stats', 'recent_orders'));
    }

    public function edit(User $user)
    {
        $branches = Branch::where('is_active', true)->get();
        $roles = UserRole::labels();
        return view('admin.users.edit', compact('user', 'branches', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id . '|max:255',
            'password' => 'nullable|string|min:6|confirmed',
            'role' => 'required|in:' . implode(',', UserRole::all()),
            'branch_id' => 'nullable|exists:branches,id',
        ], [
            'name.required' => 'Tên là bắt buộc.',
            'email.required' => 'Email là bắt buộc.',
            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Email đã được sử dụng.',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'role.required' => 'Vai trò là bắt buộc.',
            'role.in' => 'Vai trò không hợp lệ.',
        ]);

        $data = $request->except('password');

        // Cập nhật mật khẩu nếu có
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        // Người dùng chi nhánh phải có branch_id
        if ($request->role === 'branch' && !$request->branch_id) {
            return back()->withErrors(['branch_id' => 'Chi nhánh là bắt buộc cho người dùng chi nhánh.']);
        }

        // Người dùng không phải chi nhánh thì không cần branch_id
        if ($request->role !== 'branch') {
            $data['branch_id'] = null;
        }

        $user->update($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'Người dùng đã được cập nhật thành công.');
    }

    public function destroy(User $user)
    {
        // Không cho phép xóa tài khoản đang đăng nhập
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Không thể xóa tài khoản đang đăng nhập.');
        }

        // Kiểm tra người dùng có đơn hàng đang chờ duyệt không
        if ($user->role === 'branch' && $user->orderRequests()->where('status', 'pending')->count() > 0) {
            return back()->with('error', 'Không thể xóa người dùng có đơn hàng đang chờ duyệt.');
        }

        $user->update(['is_active' => false]);
        
        return redirect()->route('admin.users.index')
            ->with('success', 'Người dùng đã được vô hiệu hóa.');
    }

    public function restore($id)
    {
        $user = User::find($id);
        if ($user) {
            $user->update(['is_active' => true]);
            return back()->with('success', 'Người dùng đã được khôi phục.');
        }
        return back()->with('error', 'Không tìm thấy người dùng.');
    }

    public function resetPassword(User $user)
    {
        $newPassword = '123456'; // Mật khẩu mặc định
        $user->update(['password' => Hash::make($newPassword)]);
        
        return back()->with('success', 'Mật khẩu đã được reset thành: ' . $newPassword);
    }

    public function toggleStatus(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);
        
        $status = $user->is_active ? 'kích hoạt' : 'vô hiệu hóa';
        return back()->with('success', "Tài khoản đã được {$status}.");
    }
}