@extends('layouts.admin')

@section('title', 'Chi tiết Chi nhánh: ' . $branch->name)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4">Chi tiết Chi nhánh: {{ $branch->name }}</h2>
        <a href="{{ route('admin.branches.index') }}" class="btn btn-secondary">Quay lại danh sách</a>
    </div>

    <div class="card p-4 shadow-sm">
        <div class="row">
            <div class="col-md-6">
                <p><strong>ID:</strong> {{ $branch->id }}</p>
                <p><strong>Tên Chi nhánh:</strong> {{ $branch->name }}</p>
                <p><strong>Địa chỉ:</strong> {{ $branch->address }}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Số điện thoại:</strong> {{ $branch->phone ?? 'N/A' }}</p>
                <p><strong>Email:</strong> {{ $branch->email ?? 'N/A' }}</p>
                <p><strong>Ngày tạo:</strong> {{ $branch->created_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
        <div class="mt-4 d-flex justify-content-end gap-2">
            <a href="{{ route('admin.branches.edit', $branch->id) }}" class="btn btn-warning">Chỉnh sửa</a>
            <form action="{{ route('admin.branches.destroy', $branch->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa chi nhánh này không?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Xóa</button>
            </form>
        </div>
    </div>
@endsection