@extends('layouts.admin')

@section('title', 'Chi tiết Sách: ' . $book->title)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4">Chi tiết Sách</h2>
        <a href="{{ route('admin.books.index') }}" class="btn btn-secondary">Quay lại danh sách</a>
    </div>

    <div class="card p-4 shadow-sm">
        <div class="row">
            <div class="col-md-6">
                <p><strong>ID:</strong> {{ $book->id }}</p>
                <p><strong>Tiêu đề:</strong> {{ $book->title }}</p>
                <p><strong>Tác giả:</strong> {{ $book->author }}</p>
                <p><strong>Nhà xuất bản:</strong> {{ $book->publisher }}</p>
                <p><strong>ISBN:</strong> {{ $book->isbn }}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Thể loại:</strong> {{ $book->category->name ?? 'N/A' }}</p>
                <p><strong>Nhà cung cấp:</strong> {{ $book->supplier->name ?? 'N/A' }}</p>
                <p><strong>Giá:</strong> {{ number_format($book->price) }} VNĐ</p>
                <p><strong>Ngày xuất bản:</strong> {{ $book->published_date }}</p>
                <p><strong>Trạng thái:</strong> 
                    <span class="badge {{ $book->is_active ? 'bg-success' : 'bg-danger' }}">
                        {{ $book->is_active ? 'Hoạt động' : 'Không hoạt động' }}
                    </span>
                </p>
            </div>
        </div>
        <div class="mt-4">
            <p><strong>Mô tả:</strong></p>
            <p>{{ $book->description }}</p>
        </div>
        <div class="mt-4 d-flex justify-content-end gap-2">
            <a href="{{ route('admin.books.edit', $book->id) }}" class="btn btn-warning">Chỉnh sửa</a>
            <form action="{{ route('admin.books.destroy', $book->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa sách này không?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Xóa</button>
            </form>
        </div>
    </div>
@endsection