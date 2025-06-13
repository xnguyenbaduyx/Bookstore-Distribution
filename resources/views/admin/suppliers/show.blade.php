@extends('layouts.admin')

@section('title', 'Chi tiết Nhà cung cấp: ' . $supplier->name)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4">Chi tiết Nhà cung cấp: {{ $supplier->name }}</h2>
        <a href="{{ route('admin.suppliers.index') }}" class="btn btn-secondary">Quay lại danh sách</a>
    </div>

    <div class="card p-4 shadow-sm">
        <div class="row">
            <div class="col-md-6">
                <p><strong>ID:</strong> {{ $supplier->id }}</p>
                <p><strong>Tên Nhà cung cấp:</strong> {{ $supplier->name }}</p>
                <p><strong>Địa chỉ:</strong> {{ $supplier->address }}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Số điện thoại:</strong> {{ $supplier->phone ?? 'N/A' }}</p>
                <p><strong>Email:</strong> {{ $supplier->email ?? 'N/A' }}</p>
                <p><strong>Ngày tạo:</strong> {{ $supplier->created_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
        <div class="mt-4 d-flex justify-content-end gap-2">
            <a href="{{ route('admin.suppliers.edit', $supplier->id) }}" class="btn btn-warning">Chỉnh sửa</a>
            <form action="{{ route('admin.suppliers.destroy', $supplier->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa nhà cung cấp này không?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Xóa</button>
            </form>
        </div>
    </div>
@endsection