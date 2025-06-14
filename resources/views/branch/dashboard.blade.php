@extends('layouts.branch')

@section('title', 'Trang quản lý Chi nhánh')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-4">Tổng quan Chi nhánh</h2>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Đơn hàng chờ duyệt
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['pending_orders'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Đơn hàng đã duyệt
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['approved_orders'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Tồn kho sách
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_inventory_items'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-boxes fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Sách sắp hết
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['low_stock_items'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Thao tác nhanh</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('branch.orders.create') }}" class="btn btn-success btn-block">
                                <i class="fas fa-plus-circle"></i> Tạo yêu cầu đặt sách
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('branch.inventory.index') }}" class="btn btn-info btn-block">
                                <i class="fas fa-warehouse"></i> Kiểm tra tồn kho
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('branch.distributions.index') }}" class="btn btn-warning btn-block">
                                <i class="fas fa-shipping-fast"></i> Phiếu phân phối
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('branch.reports.index') }}" class="btn btn-secondary btn-block">
                                <i class="fas fa-chart-bar"></i> Báo cáo
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Orders -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Đơn hàng gần đây</h6>
                    <a href="{{ route('branch.orders.index') }}" class="btn btn-sm btn-primary">
                        Xem tất cả
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Mã đơn</th>
                                    <th>Ngày tạo</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recent_orders as $order)
                                    <tr>
                                        <td>{{ $order->code }}</td>
                                        <td>{{ $order->created_at->format('d/m/Y') }}</td>
                                        <td>
                                            @if($order->status == 'pending')
                                                <span class="badge badge-warning">Chờ duyệt</span>
                                            @elseif($order->status == 'approved')
                                                <span class="badge badge-success">Đã duyệt</span>
                                            @elseif($order->status == 'rejected')
                                                <span class="badge badge-danger">Từ chối</span>
                                            @else
                                                <span class="badge badge-secondary">{{ ucfirst($order->status) }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('branch.orders.show', $order->id) }}" class="btn btn-sm btn-outline-primary">
                                                Xem
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Chưa có đơn hàng nào</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low Stock Alert -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Cảnh báo tồn kho</h6>
                </div>
                <div class="card-body">
                    @forelse($inventory_items->take(5) as $item)
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <div class="font-weight-bold">{{ $item->book->title }}</div>
                                <div class="small text-gray-600">{{ $item->book->category->name ?? 'N/A' }}</div>
                            </div>
                            <div class="text-right">
                                <div class="h6 mb-0 
                                    @if($item->quantity <= 0) text-danger
                                    @elseif($item->quantity <= 5) text-warning
                                    @else text-success
                                    @endif
                                ">
                                    {{ $item->quantity }}
                                </div>
                                <div class="small">còn lại</div>
                            </div>
                        </div>
                        @if(!$loop->last)<hr>@endif
                    @empty
                        <div class="text-center text-muted">
                            <i class="fas fa-check-circle fa-2x mb-2"></i>
                            <p>Tất cả sách đều đủ hàng</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Incoming Shipments -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Hàng đang về</h6>
                </div>
                <div class="card-body">
                    @forelse($incoming_distributions as $distribution)
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <div class="font-weight-bold">{{ $distribution->code }}</div>
                                <div class="small text-gray-600">
                                    {{ $distribution->details->count() }} sản phẩm
                                </div>
                            </div>
                            <div class="text-right">
                                @if($distribution->status == 'shipped')
                                    <span class="badge badge-info">Đang giao</span>
                                @else
                                    <span class="badge badge-warning">Chuẩn bị</span>
                                @endif
                            </div>
                        </div>
                        @if(!$loop->last)<hr>@endif
                    @empty
                        <div class="text-center text-muted">
                            <i class="fas fa-truck fa-2x mb-2"></i>
                            <p>Không có hàng đang về</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Weekly Performance Chart -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Hiệu suất 7 ngày qua</h6>
                </div>
                <div class="card-body">
                    <canvas id="weeklyChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}
.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}
.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}
.border-left-danger {
    border-left: 0.25rem solid #e74a3b !important;
}
.text-gray-800 {
    color: #5a5c69 !important;
}
.text-gray-600 {
    color: #858796 !important;
}
.text-gray-300 {
    color: #dddfeb !important;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Weekly Performance Chart
    const ctx = document.getElementById('weeklyChart').getContext('2d');
    const weeklyChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json(array_column($weekly_stats, 'date')),
            datasets: [{
                label: 'Đơn hàng tạo',
                data: @json(array_column($weekly_stats, 'orders_created')),
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78, 115, 223, 0.05)',
                fill: true,
                tension: 0.3
            }, {
                label: 'Đơn hàng được duyệt',
                data: @json(array_column($weekly_stats, 'orders_approved')),
                borderColor: '#1cc88a',
                backgroundColor: 'rgba(28, 200, 138, 0.05)',
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Ngày'
                    }
                },
                y: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Số lượng'
                    },
                    beginAtZero: true
                }
            }
        }
    });

    // Auto refresh stats every 5 minutes
    setInterval(function() {
        location.reload();
    }, 300000);
});
</script>
@endpush