@extends('layouts.admin')

@section('title', 'Chỉnh sửa Nhà cung cấp: ' . $supplier->name)

@section('content')
    <h2 class="h4 mb-4">Chỉnh sửa Nhà cung cấp: {{ $supplier->name }}</h2>

    <form action="{{ route('admin.suppliers.update', $supplier->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="row g-3">
            <div class="col-md-6">
                <label for="name" class="form-label">Tên Nhà cung cấp:</label>
                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $supplier->name) }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="address" class="form-label">Địa chỉ:</label>
                <input type="text" name="address" id="address" class="form-control @error('address') is-invalid @enderror" value="{{ old('address', $supplier->address) }}" required>
                @error('address')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="phone" class="form-label">Số điện thoại:</label>
                <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $supplier->phone) }}">
                @error('phone')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="email" class="form-label">Email:</label>
                <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $supplier->email) }}">
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <div class="mt-4 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary me-2">Cập nhật Nhà cung cấp</button>
            <a href="{{ route('admin.suppliers.index') }}" class="btn btn-secondary">Hủy</a>
        </div>
    </form>
@endsection