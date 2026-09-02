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
        <div
            class="dropdown"
            data-notifications-widget
            data-notifications-feed-url="{{ route('admin.notifications.feed') }}"
            data-notifications-read-all-url="{{ route('admin.notifications.mark-all-read') }}"
            data-notifications-index-url="{{ route('admin.notifications.index') }}"
            data-notifications-count="{{ $navUnreadCount }}"
            data-notifications-latest-id="{{ $navNotifications->first()?->id }}"
        >
            <button class="btn btn-light position-relative dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="System notifications">
                <i class="bi bi-bell"></i>
                <span class="d-none d-md-inline ms-1">Notifications</span>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger {{ $navUnreadCount > 0 ? '' : 'd-none' }}" data-notifications-badge>
                    {{ $navUnreadCount }}
                </span>
            </button>
            <div class="dropdown-menu dropdown-menu-end p-0 shadow ch-notification-menu">
                <div class="d-flex align-items-center justify-content-between gap-3 px-3 py-2 border-bottom">
                    <div>
                        <div class="fw-semibold">System notifications</div>
                        <div class="small text-muted" data-notifications-summary>{{ $navUnreadCount }} unread</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-link text-decoration-none px-0" data-notifications-mark-all-read>
                        Mark all read
                    </button>
                </div>
                <div data-notifications-list>
                    @forelse ($navNotifications as $notification)
                        @php
                            $level = $notification->data['level'] ?? 'info';
                            $icon = $notification->data['icon'] ?? 'bi-bell';
                            $isUnread = is_null($notification->read_at);
                            $badgeClass = match ($level) {
                                'success' => 'text-bg-success',
                                'warning' => 'text-bg-warning',
                                'danger' => 'text-bg-danger',
                                default => 'text-bg-info',
                            };
                        @endphp
                        <a
                            class="dropdown-item py-3 border-bottom {{ $isUnread ? 'ch-notification-unread' : '' }}"
                            href="{{ $notification->data['url'] ?? route('admin.notifications.index') }}"
                            data-notification-link
                            data-notification-id="{{ $notification->id }}"
                        >
                            <div class="d-flex gap-3">
                                <div class="ch-notification-icon {{ $badgeClass }}">
                                    <i class="bi {{ $icon }}"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-start justify-content-between gap-2">
                                        <div class="fw-semibold text-dark">{{ $notification->data['title'] ?? 'Notification' }}</div>
                                        @if ($isUnread)
                                            <span class="badge text-bg-primary-subtle text-primary border border-primary-subtle">New</span>
                                        @endif
                                    </div>
                                    <div class="small text-muted">{{ $notification->data['message'] ?? '' }}</div>
                                    <div class="small text-muted mt-1">{{ $notification->created_at?->diffForHumans() }}</div>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="px-3 py-4 text-center text-muted">
                            <i class="bi bi-bell-slash fs-3 d-block mb-2"></i>
                            No notifications yet.
                        </div>
                    @endforelse
                </div>
                <div class="border-top">
                    <a class="dropdown-item text-center py-2" href="{{ route('admin.notifications.index') }}">View all notifications</a>
                </div>
            </div>
        </div>
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
