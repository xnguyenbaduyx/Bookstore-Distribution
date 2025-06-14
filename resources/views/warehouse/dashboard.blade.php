@extends('layouts.warehouse')

@section('title', 'Trang quản lý Kho')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-4">Bảng điều khiển Kho</h2>
        </div>
    </div>

    <!-- Alert for overdue items -->
    @if($overdue_distributions > 0)
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        <strong>Cảnh báo!</strong> Có {{ $overdue_distributions }} phiếu phân phối quá hạn cần xử lý ngay.
        <a href="{{ route('warehouse.distributions.index') }}?overdue=1" class="btn btn-sm btn-warning ml-2">
            Xem ngay
        </a>
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>
    @endif

    <!-- Stats Row -->
    <div class="row mb-4">
        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Xuất kho chờ
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['pending_distributions'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shipping-fast fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Đã xác nhận
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['confirmed_distributions'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Xuất hôm nay
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['shipped_today'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-truck fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Nhập chờ
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['pending_imports'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-boxes fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-secondary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                Nhập hôm nay
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['received_imports_today'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-dark shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-dark text-uppercase mb-1">
                                Tổng sách quản lý
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total_books_managed'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-book fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Workload Indicator -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-tasks mr-2"></i>
                        Khối lượng công việc hôm nay
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <a href="{{ route('warehouse.distributions.index') }}" 
                                       class="btn btn-warning btn-block position-relative">
                                        <i class="fas fa-shipping-fast"></i> Xuất kho
                                        @if($stats['pending_distributions'] > 0)
                                            <span class="badge badge-light position-absolute" style="top: -8px; right: -8px;">
                                                {{ $stats['pending_distributions'] }}
                                            </span>
                                        @endif
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="{{ route('warehouse.imports.index') }}" 
                                       class="btn btn-primary btn-block position-relative">
                                        <i class="fas fa-boxes"></i> Nhập kho
                                        @if($stats['pending_imports'] > 0)
                                            <span class="badge badge-light position-absolute" style="top: -8px; right: -8px;">
                                                {{ $stats['pending_imports'] }}
                                            </span>
                                        @endif
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="{{ route('warehouse.inventories.index') }}" class="btn btn-success btn-block">
                                        <i class="fas fa-warehouse"></i> Kiểm kho
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <button class="btn btn-outline-secondary btn-block" onclick="refreshDashboard()">
                                        <i class="fas fa-sync-alt"></i> Làm mới
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <div class="progress mb-2" style="height: 30px;">
                                    <div class="progress-bar bg-warning progress-bar-striped progress-bar-animated" 
                                         role="progressbar" 
                                         style="width: {{ min(100, ($stats['pending_distributions'] + $stats['pending_imports']) * 10) }}%">
                                        {{ $stats['pending_distributions'] + $stats['pending_imports'] }} việc
                                    </div>
                                </div>
                                <p class="text-muted mb-0">Tổng công việc chờ xử lý</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Pending Distributions -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-shipping-fast text-warning mr-2"></i>
                        Phiếu xuất cần xử lý
                    </h6>
                    <a href="{{ route('warehouse.distributions.index') }}" class="btn btn-sm btn-warning">
                        Xem tất cả ({{ count($pending_distributions) }})
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless">
                            <thead>
                                <tr>
                                    <th>Mã phiếu</th>
                                    <th>Chi nhánh</th>
                                    <th>Thời gian</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pending_distributions as $distribution)
                                    <tr class="{{ $distribution->created_at->diffInHours(now()) > 24 ? 'table-warning' : '' }}">
                                        <td>
                                            <span class="font-weight-bold">{{ $distribution->code }}</span>
                                            @if($distribution->created_at->diffInHours(now()) > 24)
                                                <i class="fas fa-exclamation-triangle text-danger ml-1" title="Quá hạn"></i>
                                            @endif
                                        </td>
                                        <td>{{ $distribution->branch->name }}</td>
                                        <td>
                                            <small class="text-muted">{{ $distribution->created_at->diffForHumans() }}</small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('warehouse.distributions.show', $distribution->id) }}" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @if($distribution->status == 'pending')
                                                    <button class="btn btn-outline-success btn-sm" 
                                                            onclick="quickConfirm({{ $distribution->id }})">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                @elseif($distribution->status == 'confirmed')
                                                    <button class="btn btn-outline-info btn-sm" 
                                                            onclick="quickShip({{ $distribution->id }})">
                                                        <i class="fas fa-truck"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                                            <p>Không có phiếu xuất chờ xử lý</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Imports -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-boxes text-primary mr-2"></i>
                        Phiếu nhập cần xử lý
                    </h6>
                    <a href="{{ route('warehouse.imports.index') }}" class="btn btn-sm btn-primary">
                        Xem tất cả ({{ count($pending_imports) }})
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless">
                            <thead>
                                <tr>
                                    <th>Mã phiếu</th>
                                    <th>NCC</th>
                                    <th>Giá trị</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pending_imports as $import)
                                    <tr>
                                        <td>
                                            <span class="font-weight-bold">{{ $import->code }}</span>
                                        </td>
                                        <td>{{ $import->supplier->name }}</td>
                                        <td>
                                            <span class="font-weight-bold text-success">
                                                {{ number_format($import->total_amount) }} VNĐ
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('warehouse.imports.show', $import->id) }}" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @if($import->status == 'pending')
                                                    <button class="btn btn-outline-success btn-sm" 
                                                            onclick="quickConfirmImport({{ $import->id }})">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                @elseif($import->status == 'confirmed')
                                                    <button class="btn btn-outline-info btn-sm" 
                                                            onclick="quickReceive({{ $import->id }})">
                                                        <i class="fas fa-download"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                                            <p>Không có phiếu nhập chờ xử lý</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmed Imports for receiving -->
    @if(count($confirmed_imports) > 0)
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-clipboard-check text-info mr-2"></i>
                        Phiếu nhập đã xác nhận - Cần nhận hàng
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($confirmed_imports as $import)
                            <div class="col-md-4 mb-3">
                                <div class="card border-info">
                                    <div class="card-body">
                                        <h6 class="card-title">{{ $import->code }}</h6>
                                        <p class="card-text">
                                            <strong>NCC:</strong> {{ $import->supplier->name }}<br>
                                            <strong>Giá trị:</strong> {{ number_format($import->total_amount) }} VNĐ<br>
                                            <strong>Xác nhận:</strong> {{ $import->confirmed_at->diffForHumans() }}
                                        </p>
                                        <div class="btn-group btn-group-sm w-100">
                                            <a href="{{ route('warehouse.imports.show', $import->id) }}" 
                                               class="btn btn-outline-primary">
                                                <i class="fas fa-eye"></i> Xem
                                            </a>
                                            <button class="btn btn-success" 
                                                    onclick="quickReceive({{ $import->id }})">
                                                <i class="fas fa-download"></i> Nhận
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Activities Chart -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-area mr-2"></i>
                        Hoạt động kho 7 ngày qua
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="activitiesChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Low Stock Alert -->
    @if(count($low_stock_books) > 0)
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4 border-left-danger">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-danger">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Cảnh báo tồn kho thấp ({{ count($low_stock_books) }} sản phẩm)
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($low_stock_books->take(6) as $item)
                            <div class="col-md-4 col-lg-2 mb-3">
                                <div class="card border-warning">
                                    <div class="card-body text-center p-3">
                                        <h6 class="card-title small">{{ Str::limit($item->book->title, 40) }}</h6>
                                        <div class="h4 mb-1 
                                            @if($item->available_quantity <= 0) text-danger
                                            @elseif($item->available_quantity <= 5) text-warning
                                            @else text-info
                                            @endif
                                        ">
                                            {{ $item->available_quantity }}
                                        </div>
                                        <small class="text-muted">còn lại</small>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @if(count($low_stock_books) > 6)
                        <div class="text-center">
                            <a href="{{ route('warehouse.inventories.alerts') }}" class="btn btn-warning">
                                Xem tất cả {{ count($low_stock_books) }} sản phẩm
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Quick Action Modals -->
<!-- Quick Confirm Distribution Modal -->
<div class="modal fade" id="quickConfirmModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận phiếu xuất</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xác nhận phiếu xuất này?</p>
                <div id="distributionDetails"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-success" id="confirmDistribution">Xác nhận</button>
            </div>
        </div>
    </div>
</div>

<!-- Quick Ship Modal -->
<div class="modal fade" id="quickShipModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xuất kho</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Xác nhận xuất kho và cập nhật tồn kho?</p>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-2"></i>
                    Hành động này sẽ giảm tồn kho trung tâm và không thể hoàn tác.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-info" id="confirmShip">Xuất kho</button>
            </div>
        </div>
    </div>
</div>

<!-- Quick Receive Import Modal -->
<div class="modal fade" id="quickReceiveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nhận hàng</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Xác nhận đã nhận đủ hàng theo phiếu nhập?</p>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle mr-2"></i>
                    Hành động này sẽ cập nhật tồn kho trung tâm.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-success" id="confirmReceive">Nhận hàng</button>
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
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}
.border-left-danger {
    border-left: 0.25rem solid #e74a3b !important;
}
.border-left-secondary {
    border-left: 0.25rem solid #6c757d !important;
}
.border-left-dark {
    border-left: 0.25rem solid #5a5c69 !important;
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
.card-header {
    background-color: #f8f9fc;
    border-bottom: 1px solid #e3e6f0;
}
.table-warning {
    background-color: rgba(255, 193, 7, 0.1);
}
.progress {
    border-radius: 15px;
}
.progress-bar {
    border-radius: 15px;
}
.position-relative .badge {
    position: absolute !important;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentDistributionId = null;
    let currentImportId = null;

    // Activities Chart
    const ctx = document.getElementById('activitiesChart').getContext('2d');
    const activitiesChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: @json(array_column($daily_activities, 'date')),
            datasets: [{
                label: 'Phiếu xuất xử lý',
                data: @json(array_column($daily_activities, 'distributions_shipped')),
                backgroundColor: 'rgba(54, 185, 204, 0.8)',
                borderColor: 'rgba(54, 185, 204, 1)',
                borderWidth: 1
            }, {
                label: 'Phiếu nhập xử lý',
                data: @json(array_column($daily_activities, 'imports_received')),
                backgroundColor: 'rgba(78, 115, 223, 0.8)',
                borderColor: 'rgba(78, 115, 223, 1)',
                borderWidth: 1
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
                        text: 'Số lượng phiếu'
                    },
                    beginAtZero: true
                }
            }
        }
    });

    // Quick Actions for Distributions
    window.quickConfirm = function(distributionId) {
        currentDistributionId = distributionId;
        $('#quickConfirmModal').modal('show');
        
        // Load distribution details
        fetch(`/warehouse/distributions/${distributionId}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('distributionDetails').innerHTML = `
                    <div class="alert alert-info">
                        <strong>Mã phiếu:</strong> ${data.code}<br>
                        <strong>Chi nhánh:</strong> ${data.branch.name}<br>
                        <strong>Số sản phẩm:</strong> ${data.details_count}
                    </div>
                `;
            })
            .catch(error => {
                console.error('Error:', error);
            });
    };

    window.quickShip = function(distributionId) {
        currentDistributionId = distributionId;
        $('#quickShipModal').modal('show');
    };

    window.quickConfirmImport = function(importId) {
        currentImportId = importId;
        
        const form = new FormData();
        form.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        
        fetch(`/warehouse/imports/${importId}/confirm`, {
            method: 'POST',
            body: form
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', 'Phiếu nhập đã được xác nhận!');
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert('error', data.message || 'Có lỗi xảy ra!');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'Có lỗi xảy ra khi xác nhận phiếu nhập!');
        });
    };

    window.quickReceive = function(importId) {
        currentImportId = importId;
        $('#quickReceiveModal').modal('show');
    };

    // Confirm distribution
    document.getElementById('confirmDistribution').addEventListener('click', function() {
        if (currentDistributionId) {
            const form = new FormData();
            form.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            
            fetch(`/warehouse/distributions/${currentDistributionId}/confirm`, {
                method: 'POST',
                body: form
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    $('#quickConfirmModal').modal('hide');
                    showAlert('success', 'Phiếu xuất đã được xác nhận!');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('error', data.message || 'Có lỗi xảy ra!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'Có lỗi xảy ra khi xác nhận phiếu xuất!');
            });
        }
    });

    // Confirm ship
    document.getElementById('confirmShip').addEventListener('click', function() {
        if (currentDistributionId) {
            const form = new FormData();
            form.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            
            fetch(`/warehouse/distributions/${currentDistributionId}/ship`, {
                method: 'POST',
                body: form
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    $('#quickShipModal').modal('hide');
                    showAlert('success', 'Đã xuất kho thành công!');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('error', data.message || 'Có lỗi xảy ra!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'Có lỗi xảy ra khi xuất kho!');
            });
        }
    });

    // Confirm receive
    document.getElementById('confirmReceive').addEventListener('click', function() {
        if (currentImportId) {
            const form = new FormData();
            form.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            
            fetch(`/warehouse/imports/${currentImportId}/receive`, {
                method: 'POST',
                body: form
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    $('#quickReceiveModal').modal('hide');
                    showAlert('success', 'Đã nhận hàng và cập nhật tồn kho!');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('error', data.message || 'Có lỗi xảy ra!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'Có lỗi xảy ra khi nhận hàng!');
            });
        }
    });

    window.refreshDashboard = function() {
        location.reload();
    };

    // Alert function
    window.showAlert = function(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 
                          type === 'error' ? 'alert-danger' : 
                          type === 'warning' ? 'alert-warning' : 'alert-info';
        
        const alert = $(`
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `);
        
        $('.container-fluid').prepend(alert);
        
        setTimeout(() => {
            alert.alert('close');
        }, 5000);
    };

    // Auto refresh every 3 minutes for warehouse operations
    setInterval(function() {
        location.reload();
    }, 180000);

    // Add CSRF token to meta for AJAX requests
    if (!document.querySelector('meta[name="csrf-token"]')) {
        const meta = document.createElement('meta');
        meta.name = 'csrf-token';
        meta.content = '{{ csrf_token() }}';
        document.getElementsByTagName('head')[0].appendChild(meta);
    }
});
</script>
@endpush