@extends('layouts.admin')

@section('title', 'Quản lý Chi nhánh')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4">Danh sách Chi nhánh</h2>
        <a href="{{ route('admin.branches.create') }}" class="btn btn-primary">Thêm Chi nhánh Mới</a>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-light">
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Tên Chi nhánh</th>
                    <th scope="col">Địa chỉ</th>
                    <th scope="col">Số điện thoại</th>
                    <th scope="col">Email</th>
                    <th scope="col">Hành động</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($branches as $branch)
                    <tr>
                        <td>{{ $branch->id }}</td>
                        <td>{{ $branch->name }}</td>
                        <td>{{ $branch->address }}</td>
                        <td>{{ $branch->phone }}</td>
                        <td>{{ $branch->email }}</td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.branches.show', $branch->id) }}" class="btn btn-info btn-sm">Xem</a>
                                <a href="{{ route('admin.branches.edit', $branch->id) }}" class="btn btn-warning btn-sm">Sửa</a>
                                <form action="{{ route('admin.branches.destroy', $branch->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa chi nhánh này không?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">Không có chi nhánh nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-center mt-4">
        {{ $branches->links() }}
    </div>
@endsection