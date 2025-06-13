@extends('layouts.admin')

@section('title', 'Quản lý Sách')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4">Danh sách Sách</h2>
        <a href="{{ route('admin.books.create') }}" class="btn btn-primary">Thêm Sách Mới</a>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-light">
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Tiêu đề</th>
                    <th scope="col">Tác giả</th>
                    <th scope="col">Thể loại</th>
                    <th scope="col">Giá</th>
                    <th scope="col">Trạng thái</th>
                    <th scope="col">Hành động</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($books as $book)
                    <tr>
                        <td>{{ $book->id }}</td>
                        <td>{{ $book->title }}</td>
                        <td>{{ $book->author }}</td>
                        <td>{{ $book->category->name ?? 'N/A' }}</td>
                        <td>{{ number_format($book->price) }} VNĐ</td>
                        <td>
                            <span class="badge {{ $book->is_active ? 'bg-success' : 'bg-danger' }}">
                                {{ $book->is_active ? 'Hoạt động' : 'Không hoạt động' }}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.books.show', $book->id) }}" class="btn btn-info btn-sm">Xem</a>
                                <a href="{{ route('admin.books.edit', $book->id) }}" class="btn btn-warning btn-sm">Sửa</a>
                                <form action="{{ route('admin.books.destroy', $book->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa sách này không?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">Không có sách nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-center mt-4">
        {{ $books->links() }} {{-- Hiển thị phân trang Bootstrap --}}
    </div>
@endsection