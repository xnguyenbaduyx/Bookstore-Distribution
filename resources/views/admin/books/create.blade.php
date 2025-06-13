@extends('layouts.admin')

@section('title', 'Thêm Sách Mới')

@section('content')
    <h2 class="h4 mb-4">Thêm Sách Mới</h2>

    <form action="{{ route('admin.books.store') }}" method="POST">
        @csrf
        <div class="row g-3"> {{-- g-3 là khoảng cách giữa các hàng và cột --}}
            <div class="col-md-6">
                <label for="title" class="form-label">Tiêu đề:</label>
                <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required>
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="author" class="form-label">Tác giả:</label>
                <input type="text" name="author" id="author" class="form-control @error('author') is-invalid @enderror" value="{{ old('author') }}" required>
                @error('author')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="publisher" class="form-label">Nhà xuất bản:</label>
                <input type="text" name="publisher" id="publisher" class="form-control @error('publisher') is-invalid @enderror" value="{{ old('publisher') }}" required>
                @error('publisher')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="isbn" class="form-label">ISBN:</label>
                <input type="text" name="isbn" id="isbn" class="form-control @error('isbn') is-invalid @enderror" value="{{ old('isbn') }}" required>
                @error('isbn')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="category_id" class="form-label">Thể loại:</label>
                <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                    <option value="">Chọn thể loại</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                @error('category_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="supplier_id" class="form-label">Nhà cung cấp:</label>
                <select name="supplier_id" id="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror">
                    <option value="">Chọn nhà cung cấp (Tùy chọn)</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>
                @error('supplier_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="price" class="form-label">Giá:</label>
                <input type="number" name="price" id="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price') }}" required min="0" step="1000">
                @error('price')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6">
                <label for="published_date" class="form-label">Ngày xuất bản:</label>
                <input type="date" name="published_date" id="published_date" class="form-control @error('published_date') is-invalid @enderror" value="{{ old('published_date') }}" required>
                @error('published_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-12"> {{-- Dùng col-12 để chiếm toàn bộ chiều rộng --}}
                <label for="description" class="form-label">Mô tả:</label>
                <textarea name="description" id="description" rows="4" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                @error('description')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-12">
                <div class="form-check">
                    <input type="checkbox" name="is_active" id="is_active" class="form-check-input" {{ old('is_active', true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Hoạt động</label>
                </div>
            </div>
        </div>
        <div class="mt-4 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary me-2">Thêm Sách</button>
            <a href="{{ route('admin.books.index') }}" class="btn btn-secondary">Hủy</a>
        </div>
    </form>
@endsection