<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Warehouse Dashboard') - Bookstore Distribution</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <link href="{{ mix('css/warehouse.css') }}" rel="stylesheet">
    <link href="{{ mix('css/custom.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>

    <div class="d-flex" id="wrapper-warehouse">
        <!-- Sidebar -->
        <div class="bg-info text-white border-right" id="sidebar-warehouse-wrapper" style="width: 250px; min-height: 100vh;">
            <div class="sidebar-heading text-center py-4 fs-4 fw-bold">Warehouse Panel</div>
            <div class="list-group list-group-flush">
                <a href="{{ route('warehouse.dashboard') }}" class="list-group-item list-group-item-action bg-info text-white border-0 py-2">Dashboard</a>
                <a href="{{ route('warehouse.imports.index') }}" class="list-group-item list-group-item-action bg-info text-white border-0 py-2">Quản lý Nhập kho</a>
                <a href="{{ route('warehouse.distributions.index') }}" class="list-group-item list-group-item-action bg-info text-white border-0 py-2">Quản lý Xuất kho (Phân phối)</a>
                <a href="{{ route('warehouse.inventories.index') }}" class="list-group-item list-group-item-action bg-info text-white border-0 py-2">Kho tổng</a>
                
                <form method="POST" action="{{ route('logout') }}" class="list-group-item bg-info border-0 py-2">
                    @csrf
                    <button type="submit" class="btn btn-link text-white text-decoration-none p-0 w-100 text-start">Đăng xuất</button>
                </form>
            </div>
        </div>
        <!-- /#sidebar-warehouse-wrapper -->

        <!-- Page Content -->
        <div id="page-content-warehouse-wrapper" class="flex-grow-1 bg-light">
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-primary" id="sidebarWarehouseToggle">Toggle Sidebar</button>
                    <div class="collapse navbar-collapse" id="navbarSupportedContentWarehouse">
                        <ul class="navbar-nav ms-auto mt-2 mt-lg-0">
                            <li class="nav-item">
                                <span class="nav-link">Chào mừng, {{ Auth::user()->name ?? 'Khách' }}!</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <div class="container-fluid py-4">
                <h1 class="mt-4 mb-4">@yield('title', 'Dashboard')</h1>

                {{-- Flash Messages --}}
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <strong>Thành công!</strong> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Lỗi!</strong> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="card p-4 shadow-sm">
                    @yield('content')
                </div>
            </div>
        </div>
        <!-- /#page-content-warehouse-wrapper -->
    </div>
    <!-- /#wrapper-warehouse -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ mix('js/app.js') }}"></script>
    <script src="{{ mix('js/warehouse.js') }}"></script>
    <script>
        var elWarehouse = document.getElementById("wrapper-warehouse");
        var toggleWarehouseButton = document.getElementById("sidebarWarehouseToggle");

        toggleWarehouseButton.onclick = function () {
            elWarehouse.classList.toggle("toggled");
        };
    </script>
    @stack('scripts')
</body>
</html>