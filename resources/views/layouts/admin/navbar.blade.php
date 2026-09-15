<nav class="navbar navbar-expand navbar-light ch-navbar px-3">
    @php
        $navUser = auth()->user();
        $navNotifications = $navUser
            ? $navUser->notifications()->latest()->limit(5)->get()
            : collect();
        $navUnreadCount = $navUser ? $navUser->unreadNotifications()->count() : 0;
    @endphp
    <button class="btn btn-light d-md-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas">
        <i class="bi bi-list"></i>
    </button>
    <button class="btn btn-light d-none d-md-inline-flex me-2" type="button" data-toggle-sidebar title="Toggle sidebar">
        <i class="bi bi-layout-sidebar"></i>
    </button>
    <span class="navbar-brand mb-0 h6 d-md-none">{{ $propertyName }}</span>
    <div class="ms-auto d-flex align-items-center gap-2">
        <div class="dropdown d-none d-md-inline-block" id="deviceSwitcher">
            <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Device preview">
                <i class="bi bi-phone me-1"></i><span class="d-none d-lg-inline">Device</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><button class="dropdown-item" data-device="desktop"><i class="bi bi-display me-2"></i>Desktop</button></li>
                <li><button class="dropdown-item" data-device="tablet"><i class="bi bi-tablet me-2"></i>Tablet (768px)</button></li>
                <li><button class="dropdown-item" data-device="mobile"><i class="bi bi-phone me-2"></i>Mobile (375px)</button></li>
            </ul>
        </div>
        <span class="ch-badge ch-badge-muted d-none d-sm-inline"><i class="bi bi-calendar3 me-1"></i>{{ now()->format('l, d M Y') }}</span>
        <a
            class="btn btn-light position-relative"
            href="{{ route('admin.notifications.index', [], false) }}"
            title="System notifications"
            data-notifications-widget
            data-notifications-feed-url="{{ route('admin.notifications.feed', [], false) }}"
            data-notifications-count="{{ $navUnreadCount }}"
            data-notifications-latest-id="{{ $navNotifications->first()?->id }}"
        >
            <i class="bi bi-bell"></i>
            <span class="d-none d-md-inline ms-1">Notifications</span>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger {{ $navUnreadCount > 0 ? '' : 'd-none' }}" data-notifications-badge>
                {{ $navUnreadCount }}
            </span>
        </a>
        <button type="button" class="btn btn-light" data-open-chat-widget title="Open AI assistant">
            <i class="bi bi-robot"></i>
            <span class="d-none d-md-inline ms-1">Assistant</span>
        </button>
        <form method="POST" action="{{ route('logout') }}">
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
                <li><a class="dropdown-item" href="{{ route('account.show') }}"><i class="bi bi-person me-2"></i>My account</a></li>
                <li><hr class="dropdown-divider"></li>
                @can('settings.view')
                    <li><a class="dropdown-item" href="{{ route('admin.settings') }}"><i class="bi bi-gear me-2"></i>Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                @endcan
            </ul>
        </div>
    </div>
</nav>
