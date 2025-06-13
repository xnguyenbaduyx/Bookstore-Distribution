@extends('layouts.admin')

@section('title', 'Trang quản trị')

@section('content')
<div class="container">
    <h2 class="mb-4">Tổng quan hệ thống</h2>

    <div class="row">
        @foreach ($stats as $key => $value)
            <div class="col-md-3 mb-3">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">{{ ucwords(str_replace('_', ' ', $key)) }}</h5>
                        <h3>{{ $value }}</h3>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <h4 class="mt-5">Đơn hàng gần đây</h4>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Chi nhánh</th>
                <th>Người tạo</th>
                <th>Thời gian</th>
                <th>Trạng thái</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($recent_orders as $order)
                <tr>
                    <td>{{ $order->branch->name ?? 'N/A' }}</td>
                    <td>{{ $order->creator->name ?? 'N/A' }}</td>
                    <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                    <td>{{ ucfirst($order->status) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h4 class="mt-5">Người dùng mới</h4>
    <ul class="list-group">
        @foreach ($recent_users as $user)
            <li class="list-group-item d-flex justify-content-between">
                <span>{{ $user->name }} ({{ $user->email }})</span>
                <span class="text-muted">{{ $user->branch->name ?? 'N/A' }}</span>
            </li>
        @endforeach
    </ul>

    <h4 class="mt-5">Sách sắp hết hàng</h4>
    <ul class="list-group">
        @foreach ($low_stock_books as $inventory)
            <li class="list-group-item d-flex justify-content-between">
                <span>{{ $inventory->book->title }}</span>
                <span class="text-danger">Còn lại: {{ $inventory->available_quantity }}</span>
            </li>
        @endforeach
    </ul>

    <h4 class="mt-5">Biểu đồ đơn hàng theo tháng ({{ now()->year }})</h4>
    <canvas id="monthlyChart" height="100"></canvas>

    <h4 class="mt-5">Phân bổ người dùng theo vai trò</h4>
    <ul class="list-group">
        @foreach ($user_by_roles as $role => $count)
            <li class="list-group-item">{{ ucfirst($role) }}: {{ $count }}</li>
        @endforeach
    </ul>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('monthlyChart').getContext('2d');
    const monthlyChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Th1', 'Th2', 'Th3', 'Th4', 'Th5', 'Th6', 'Th7', 'Th8', 'Th9', 'Th10', 'Th11', 'Th12'],
            datasets: [{
                label: 'Đơn hàng',
                data: @json($monthly_data),
                borderColor: 'blue',
                fill: false,
                tension: 0.1
            }]
        }
    });
</script>
@endsection
