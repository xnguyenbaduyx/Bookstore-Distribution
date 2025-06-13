<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Manager Dashboard') - Bookstore Distribution</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <link href="{{ mix('css/manager.css') }}" rel="stylesheet">
    <link href="{{ mix('css/custom.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>

    <div class="d-flex" id="wrapper-manager">
        <!-- Sidebar -->
        <div class="bg-primary text-white border-right" id="sidebar-manager-wrapper" style="width: 250px; min-height: 100vh;">
            <div class="sidebar-heading text-center py-4 fs-4 fw-bold">Manager Panel</div>
            <div class="list-group list-group-flush">
                <a href="{{ route('manager.dashboard') }}" class="list-group-item list-group-item-action bg-primary text-white border-0 py-2">Dashboard</a>
                <a href="{{ route('manager.order_requests.index') }}" class="list-group-item list-group-item-action bg-primary text-white border-0 py-2">Quản lý Yêu cầu ĐH</a>
                <a href="{{ route('manager.distributions.index') }}" class="list-group-item list-group-item-action bg-primary text-white border-0 py-2">Quản lý Phân phối</a>
                <a href="{{ route('manager.imports.index') }}" class="list-group-item list-group-item-action bg-primary text-white border-0 py-2">Quản lý Nhập hàng</a>
                
                <form method="POST" action="{{ route('logout') }}" class="list-group-item bg-primary border-0 py-2">
                    @csrf
                    <button type="submit" class="btn btn-link text-white text-decoration-none p-0 w-100 text-start">Đăng xuất</button>
                </form>
            </div>
        </div>
        <!-- /#sidebar-manager-wrapper -->

        <!-- Page Content -->
        <div id="page-content-manager-wrapper" class="flex-grow-1 bg-light">
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-primary" id="sidebarManagerToggle">Toggle Sidebar</button>
                    <div class="collapse navbar-collapse" id="navbarSupportedContentManager">
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
        <!-- /#page-content-manager-wrapper -->
    </div>
    <!-- /#wrapper-manager -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ mix('js/app.js') }}"></script>
    <script src="{{ mix('js/manager.js') }}"></script>
    <script>
        var elManager = document.getElementById("wrapper-manager");
        var toggleManagerButton = document.getElementById("sidebarManagerToggle");

        toggleManagerButton.onclick = function () {
            elManager.classList.toggle("toggled");
        };
    </script>
    @stack('scripts')
</body>
</html>