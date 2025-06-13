@extends('layouts.admin')

@section('title', 'Quản lý Nhà cung cấp')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4">Danh sách Nhà cung cấp</h2>
        <a href="{{ route('admin.suppliers.create') }}" class="btn btn-primary">Thêm Nhà cung cấp Mới</a>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-light">
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Tên Nhà cung cấp</th>
                    <th scope="col">Địa chỉ</th>
                    <th scope="col">Số điện thoại</th>
                    <th scope="col">Email</th>
                    <th scope="col">Hành động</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($suppliers as $supplier)
                    <tr>
                        <td>{{ $supplier->id }}</td>
                        <td>{{ $supplier->name }}</td>
                        <td>{{ $supplier->address }}</td>
                        <td>{{ $supplier->phone ?? 'N/A' }}</td>
                        <td>{{ $supplier->email ?? 'N/A' }}</td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.suppliers.show', $supplier->id) }}" class="btn btn-info btn-sm">Xem</a>
                                <a href="{{ route('admin.suppliers.edit', $supplier->id) }}" class="btn btn-warning btn-sm">Sửa</a>
                                <form action="{{ route('admin.suppliers.destroy', $supplier->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa nhà cung cấp này không?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">Không có nhà cung cấp nào.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-center mt-4">
        {{ $suppliers->links() }}
    </div>
@endsection