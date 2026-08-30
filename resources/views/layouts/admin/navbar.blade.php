<nav class="navbar navbar-expand navbar-light ch-navbar px-3">
    <button class="btn btn-light d-md-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas">
        <i class="bi bi-list"></i>
    </button>
    <button class="btn btn-light d-none d-md-inline-flex me-2" type="button" data-toggle-sidebar title="Toggle sidebar">
        <i class="bi bi-layout-sidebar"></i>
    </button>
    <span class="navbar-brand mb-0 h6 d-md-none">{{ $propertyName }}</span>
    <div class="ms-auto d-flex align-items-center gap-2">
        <span class="ch-badge ch-badge-muted d-none d-sm-inline"><i class="bi bi-calendar3 me-1"></i>{{ now()->format('l, d M Y') }}</span>
        <button type="button" class="btn btn-light" data-open-chat-widget title="Open AI assistant">
            <i class="bi bi-robot"></i>
            <span class="d-none d-md-inline ms-1">Assistant</span>
        </button>
        <form method="POST" action="{{ route('logout') }}" class="d-none d-lg-inline-flex">
            @csrf
            <button type="submit" class="btn btn-outline-danger d-flex align-items-center gap-2" title="Sign out">
                <i class="bi bi-box-arrow-right"></i>
                <span>Sign out</span>
            </button>
        </form>
        <div class="dropdown">
            <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                <i class="bi bi-person-circle fs-5"></i>
                <span class="d-none d-md-inline">{{ auth()->user()->name }}</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                @can('settings.view')
                    <li><a class="dropdown-item" href="{{ route('admin.settings') }}"><i class="bi bi-gear me-2"></i>Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                @endcan
                <li>
                    <form method="POST" action="{{ route('logout') }}" id="navbarLogoutForm">
                        @csrf
                        <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i>Sign out</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>
