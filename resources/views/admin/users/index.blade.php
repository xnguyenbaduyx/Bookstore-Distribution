@extends('layouts.admin')

@section('title', 'Quản lý Người dùng')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4">Danh sách Người dùng</h2>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Thêm Người dùng Mới</a>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-light">
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Tên</th>
                    <th scope="col">Email</th>
                    <th scope="col">Vai trò</th>
                    <th scope="col">Chi nhánh (nếu có)</th>
                    <th scope="col">Trạng thái</th>
                    <th scope="col">Hành động</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <span class="badge 
                                @if($user->role == \App\Enums\UserRole::ADMIN) bg-danger
                                @elseif($user->role == \App\Enums\UserRole::MANAGER) bg-primary
                                @elseif($user->role == \App\Enums\UserRole::WAREHOUSE) bg-info
                                @elseif($user->role == \App\Enums\UserRole::BRANCH) bg-success
                                @else bg-secondary
                                @endif
                            ">
                                {{ $user->role->value }}
                            </span>
                        </td>
                        <td>{{ $user->branch->name ?? 'N/A' }}</td>
                        <td>
                            <span class="badge {{ $user->is_active ? 'bg-success' : 'bg-danger' }}">
                                {{ $user->is_active ? 'Hoạt động' : 'Khóa' }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.users.show', $user->id) }}" class="btn btn-info btn-sm">Xem</a>
                                <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-warning btn-sm">Sửa</a>
                                <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa người dùng này không?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">Không có người dùng nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-center mt-4">
        {{ $users->links() }}
    </div>
@endsection