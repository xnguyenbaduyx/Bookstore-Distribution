@extends('layouts.admin')

@section('title', 'Chi tiết Người dùng: ' . $user->name)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4">Chi tiết Người dùng: {{ $user->name }}</h2>
        <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Quay lại danh sách</a>
    </div>

    <div class="card p-4 shadow-sm">
        <div class="row">
            <div class="col-md-6">
                <p><strong>ID:</strong> {{ $user->id }}</p>
                <p><strong>Tên:</strong> {{ $user->name }}</p>
                <p><strong>Email:</strong> {{ $user->email }}</p>
                <p><strong>Vai trò:</strong> 
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
                </p>
            </div>
            <div class="col-md-6">
                <p><strong>Chi nhánh:</strong> {{ $user->branch->name ?? 'N/A' }}</p>
                <p><strong>Ngày tạo:</strong> {{ $user->created_at->format('d/m/Y H:i') }}</p>
                <p><strong>Cập nhật lần cuối:</strong> {{ $user->updated_at->format('d/m/Y H:i') }}</p>
                <p><strong>Trạng thái:</strong> 
                    <span class="badge {{ $user->is_active ? 'bg-success' : 'bg-danger' }}">
                        {{ $user->is_active ? 'Hoạt động' : 'Khóa' }}
                    </span>
                </p>
            </div>
        </div>
        <div class="mt-4 d-flex justify-content-end gap-2">
            <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-warning">Chỉnh sửa</a>
            <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa người dùng này không?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Xóa</button>
            </form>
        </div>
    </div>
@endsection