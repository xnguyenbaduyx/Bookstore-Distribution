@extends('layouts.admin')

@section('title', 'Báo cáo hệ thống')

@section('content')
<div class="container">
    <h2 class="mb-4">Báo cáo tổng hợp</h2>

    <h4>1. Đơn hàng theo trạng thái</h4>
    <ul class="list-group mb-4">
        @foreach ($data['orders_by_status'] as $status => $count)
            <li class="list-group-item">{{ ucfirst($status) }}: {{ $count }}</li>
        @endforeach
    </ul>

    <h4>2. Đơn hàng theo chi nhánh</h4>
    <ul class="list-group mb-4">
        @foreach ($data['orders_by_branch'] as $branch)
            <li class="list-group-item">{{ $branch['branch_name'] }}: {{ $branch['count'] }}</li>
        @endforeach
    </ul>

    <h4>3. Sách theo thể loại</h4>
    <ul class="list-group">
        @foreach ($data['books_by_category'] as $category)
            <li class="list-group-item">{{ $category['category_name'] }}: {{ $category['count'] }}</li>
        @endforeach
    </ul>
</div>
@endsection
