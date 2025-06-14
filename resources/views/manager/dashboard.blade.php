@extends('layouts.manager')

@section('title', 'Trang quản lý Manager')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-4">Bảng điều khiển Quản lý</h2>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row mb-4">
        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Chờ duyệt
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

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Đã duyệt
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

        <div class="col-xl-2 col-md-4 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Phân phối
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
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Nhập hàng
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
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Sắp hết hàng
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['low_stock_books'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
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
                                Hết hàng
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['out_of_stock_books'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Thao tác nhanh</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2 mb-3">
                            <a href="{{ route('manager.order_requests.index') }}" class="btn btn-warning btn-block">
                                <i class="fas fa-list-alt"></i> Duyệt đơn hàng
                            </a>
                        </div>
                        <div class="col-md-2 mb-3">
                            <a href="{{ route('manager.imports.create') }}" class="btn btn-primary btn-block">
                                <i class="fas fa-plus"></i> Tạo phiếu nhập
                            </a>
                        </div>
                        <div class="col-md-2 mb-3">
                            <a href="{{ route('manager.distributions.index') }}" class="btn btn-info btn-block">
                                <i class="fas fa-truck"></i> Quản lý phân phối
                            </a>
                        </div>
                        <div class="col-md-2 mb-3">
                            <a href="{{ route('manager.inventory.index') }}" class="btn btn-success btn-block">
                                <i class="fas fa-warehouse"></i> Kiểm tra kho
                            </a>
                        </div>
                        <div class="col-md-2 mb-3">
                            <a href="{{ route('manager.reports.index') }}" class="btn btn-secondary btn-block">
                                <i class="fas fa-chart-bar"></i> Báo cáo
                            </a>
                        </div>
                        <div class="col-md-2 mb-3">
                            <button class="btn btn-outline-primary btn-block" onclick="refreshDashboard()">
                                <i class="fas fa-sync-alt"></i> Làm mới
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Pending Orders -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-clock text-warning mr-2"></i>
                        Đơn hàng chờ duyệt
                    </h6>
                    <a href="{{ route('manager.order_requests.index') }}?status=pending" class="btn btn-sm btn-warning">
                        Xem tất cả ({{ count($recent_orders) }})
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless">
                            <thead>
                                <tr>
                                    <th>Mã đơn</th>
                                    <th>Chi nhánh</th>
                                    <th>Ngày tạo</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recent_orders as $order)
                                    <tr>
                                        <td>
                                            <span class="font-weight-bold">{{ $order->code }}</span>
                                        </td>
                                        <td>{{ $order->branch->name ?? 'N/A' }}</td>
                                        <td>
                                            <small class="text-muted">{{ $order->created_at->diffForHumans() }}</small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('manager.order_requests.show', $order->id) }}" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <button class="btn btn-outline-success btn-sm" 
                                                        onclick="quickApprove({{ $order->id }})">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn btn-outline-danger btn-sm" 
                                                        onclick="quickReject({{ $order->id }})">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                                            <p>Không có đơn hàng chờ duyệt</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low Stock Alert -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-exclamation-triangle text-danger mr-2"></i>
                        Cảnh báo tồn kho thấp
                    </h6>
                </div>
                <div class="card-body">
                    @forelse($low_stock_books as $book)
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="flex-grow-1">
                                <div class="font-weight-bold">{{ $book->book->title }}</div>
                                <div class="small text-gray-600">{{ $book->book->category->name ?? 'N/A' }}</div>
                            </div>
                            <div class="text-right">
                                <div class="h6 mb-0 
                                    @if($book->available_quantity <= 0) text-danger
                                    @elseif($book->available_quantity <= 5) text-warning
                                    @else text-info
                                    @endif
                                ">
                                    {{ $book->available_quantity }}/{{ $book->quantity }}
                                </div>
                                <div class="small">có sẵn/tổng</div>
                            </div>
                            <div class="ml-2">
                                <button class="btn btn-sm btn-outline-primary" 
                                        onclick="createImportOrder({{ $book->book_id }})">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        @if(!$loop->last)<hr>@endif
                    @empty
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                            <p>Tất cả sách đều đủ hàng</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Pending Distributions -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-shipping-fast text-info mr-2"></i>
                        Phân phối chờ xử lý
                    </h6>
                </div>
                <div class="card-body">
                    @forelse($pending_distributions as $distribution)
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <div class="font-weight-bold">{{ $distribution->code }}</div>
                                <div class="small text-gray-600">
                                    {{ $distribution->branch->name }}
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="badge badge-warning">{{ ucfirst($distribution->status) }}</span>
                                <div class="small">{{ $distribution->created_at->diffForHumans() }}</div>
                            </div>
                        </div>
                        @if(!$loop->last)<hr>@endif
                    @empty
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-truck fa-3x mb-3 text-info"></i>
                            <p>Không có phân phối chờ xử lý</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Pending Imports -->
        <div class="col-xl-6 col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-boxes text-primary mr-2"></i>
                        Nhập hàng chờ xử lý
                    </h6>
                </div>
                <div class="card-body">
                    @forelse($pending_imports as $import)
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <div class="font-weight-bold">{{ $import->code }}</div>
                                <div class="small text-gray-600">
                                    {{ $import->supplier->name }}
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="badge badge-primary">{{ ucfirst($import->status) }}</span>
                                <div class="small">{{ number_format($import->total_amount) }} VNĐ</div>
                            </div>
                        </div>
                        @if(!$loop->last)<hr>@endif
                    @empty
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-boxes fa-3x mb-3 text-primary"></i>
                            <p>Không có nhập hàng chờ xử lý</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Activities Chart -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-line mr-2"></i>
                        Hoạt động 7 ngày qua
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="dailyChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Action Modals -->
<!-- Quick Approve Modal -->
<div class="modal fade" id="quickApproveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Duyệt đơn hàng nhanh</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn duyệt đơn hàng này?</p>
                <div id="orderDetails"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-success" id="confirmApprove">Duyệt</button>
            </div>
        </div>
    </div>
</div>

<!-- Quick Reject Modal -->
<div class="modal fade" id="quickRejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Từ chối đơn hàng</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="rejectReason">Lý do từ chối:</label>
                    <textarea class="form-control" id="rejectReason" rows="3" placeholder="Nhập lý do từ chối..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger" id="confirmReject">Từ chối</button>
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
.btn-group-sm > .btn, .btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    line-height: 1.5;
    border-radius: 0.2rem;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentOrderId = null;

    // Daily Activities Chart
    const ctx = document.getElementById('dailyChart').getContext('2d');
    const dailyChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json(array_column($daily_stats ?? [], 'date')),
            datasets: [{
                label: 'Đơn hàng tạo',
                data: @json(array_column($daily_stats ?? [], 'orders')),
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                fill: true,
                tension: 0.3
            }, {
                label: 'Đơn hàng duyệt',
                data: @json(array_column($daily_stats ?? [], 'approved')),
                borderColor: '#1cc88a',
                backgroundColor: 'rgba(28, 200, 138, 0.1)',
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

    // Quick Actions
    window.quickApprove = function(orderId) {
        currentOrderId = orderId;
        $('#quickApproveModal').modal('show');
        
        // Load order details
        fetch(`/manager/order_requests/${orderId}`)
            .then(response => response.json())
            .then(data => {
                document.getElementById('orderDetails').innerHTML = `
                    <div class="alert alert-info">
                        <strong>Mã đơn:</strong> ${data.code}<br>
                        <strong>Chi nhánh:</strong> ${data.branch.name}<br>
                        <strong>Tổng giá trị:</strong> ${new Intl.NumberFormat('vi-VN').format(data.total_amount)} VNĐ
                    </div>
                `;
            })
            .catch(error => {
                console.error('Error:', error);
            });
    };

    window.quickReject = function(orderId) {
        currentOrderId = orderId;
        $('#quickRejectModal').modal('show');
    };

    // Confirm approve
    document.getElementById('confirmApprove').addEventListener('click', function() {
        if (currentOrderId) {
            const form = new FormData();
            form.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            
            fetch(`/manager/order_requests/${currentOrderId}/approve`, {
                method: 'POST',
                body: form
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    $('#quickApproveModal').modal('hide');
                    showAlert('success', 'Đơn hàng đã được duyệt thành công!');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('error', data.message || 'Có lỗi xảy ra!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'Có lỗi xảy ra khi duyệt đơn hàng!');
            });
        }
    });

    // Confirm reject
    document.getElementById('confirmReject').addEventListener('click', function() {
        const reason = document.getElementById('rejectReason').value.trim();
        if (!reason) {
            showAlert('warning', 'Vui lòng nhập lý do từ chối!');
            return;
        }

        if (currentOrderId) {
            const form = new FormData();
            form.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            form.append('rejection_reason', reason);
            
            fetch(`/manager/order_requests/${currentOrderId}/reject`, {
                method: 'POST',
                body: form
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    $('#quickRejectModal').modal('hide');
                    showAlert('success', 'Đơn hàng đã được từ chối!');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('error', data.message || 'Có lỗi xảy ra!');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'Có lỗi xảy ra khi từ chối đơn hàng!');
            });
        }
    });

    window.createImportOrder = function(bookId) {
        window.location.href = `/manager/imports/create?book_id=${bookId}`;
    };

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

    // Auto refresh every 5 minutes
    setInterval(function() {
        location.reload();
    }, 300000);

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