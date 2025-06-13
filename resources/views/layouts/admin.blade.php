<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard') - Bookstore Distribution</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <link href="{{ mix('css/admin.css') }}" rel="stylesheet">
    <link href="{{ mix('css/custom.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>

    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <div class="bg-dark text-white border-right" id="sidebar-wrapper" style="width: 250px; min-height: 100vh;">
            <div class="sidebar-heading text-center py-4 fs-4 fw-bold">Admin Panel</div>
            <div class="list-group list-group-flush">
                <a href="{{ route('admin.dashboard') }}" class="list-group-item list-group-item-action bg-dark text-white border-0 py-2">Dashboard</a>
                <a href="{{ route('admin.books.index') }}" class="list-group-item list-group-item-action bg-dark text-white border-0 py-2">Quản lý Sách</a>
                <a href="{{ route('admin.categories.index') }}" class="list-group-item list-group-item-action bg-dark text-white border-0 py-2">Quản lý Thể loại</a>
                <a href="{{ route('admin.suppliers.index') }}" class="list-group-item list-group-item-action bg-dark text-white border-0 py-2">Quản lý NCC</a>
                <a href="{{ route('admin.branches.index') }}" class="list-group-item list-group-item-action bg-dark text-white border-0 py-2">Quản lý Chi nhánh</a>
                <a href="{{ route('admin.users.index') }}" class="list-group-item list-group-item-action bg-dark text-white border-0 py-2">Quản lý Người dùng</a>
                <a href="{{ route('admin.order_requests.index') }}" class="list-group-item list-group-item-action bg-dark text-white border-0 py-2">Yêu cầu đặt hàng</a>
                <a href="{{ route('admin.imports.index') }}" class="list-group-item list-group-item-action bg-dark text-white border-0 py-2">Nhập hàng</a>
                <a href="{{ route('admin.distributions.index') }}" class="list-group-item list-group-item-action bg-dark text-white border-0 py-2">Phân phối</a>
                <a href="{{ route('admin.inventories.index') }}" class="list-group-item list-group-item-action bg-dark text-white border-0 py-2">Kho tổng</a>
                <a href="{{ route('admin.branch_inventories.index') }}" class="list-group-item list-group-item-action bg-dark text-white border-0 py-2">Kho chi nhánh</a>
                
                <form method="POST" action="{{ route('logout') }}" class="list-group-item bg-dark border-0 py-2">
                    @csrf
                    <button type="submit" class="btn btn-link text-white text-decoration-none p-0 w-100 text-start">Đăng xuất</button>
                </form>
            </div>
        </div>
        <!-- /#sidebar-wrapper -->

        <!-- Page Content -->
        <div id="page-content-wrapper" class="flex-grow-1 bg-light">
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-primary" id="sidebarToggle">Toggle Sidebar</button>
                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
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
        <!-- /#page-content-wrapper -->
    </div>
    <!-- /#wrapper -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ mix('js/app.js') }}"></script>
    <script src="{{ mix('js/admin.js') }}"></script>
    <script>
        var el = document.getElementById("wrapper");
        var toggleButton = document.getElementById("sidebarToggle");

        toggleButton.onclick = function () {
            el.classList.toggle("toggled");
        };
    </script>
    @stack('scripts')
</body>
</html>