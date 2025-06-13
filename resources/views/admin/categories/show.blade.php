@extends('layouts.admin')

@section('title', 'Chi tiết Thể loại: ' . $category->name)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4">Chi tiết Thể loại: {{ $category->name }}</h2>
        <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary">Quay lại danh sách</a>
    </div>

    <div class="card p-4 shadow-sm">
        <p><strong>ID:</strong> {{ $category->id }}</p>
        <p><strong>Tên Thể loại:</strong> {{ $category->name }}</p>
        <p><strong>Mô tả:</strong> {{ $category->description ?? 'N/A' }}</p>
        <p><strong>Ngày tạo:</strong> {{ $category->created_at->format('d/m/Y H:i') }}</p>
        <div class="mt-4 d-flex justify-content-end gap-2">
            <a href="{{ route('admin.categories.edit', $category->id) }}" class="btn btn-warning">Chỉnh sửa</a>
            <form action="{{ route('admin.categories.destroy', $category->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa thể loại này không?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Xóa</button>
            </form>
        </div>
    </div>
@endsection