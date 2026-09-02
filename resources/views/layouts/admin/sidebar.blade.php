<aside class="ch-sidebar d-none d-md-flex flex-column flex-shrink-0 p-3" style="width: 260px;">
    <a href="{{ route('admin.dashboard') }}" class="ch-brand d-flex align-items-center mb-3 mb-md-0 me-md-auto text-decoration-none">
        <span class="fs-4"><i class="bi bi-house-heart-fill brand-mark"></i><span class="brand-name">{{ $propertyName }}</span></span>
    </a>
    <hr class="text-secondary">
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="sidebar-heading">Overview</li>
        <li class="nav-item">
            <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2 me-2"></i><span class="nav-text">Dashboard</span>
            </a>
        </li>
        @can('calendar.view')
            <li class="nav-item">
                <a href="{{ route('admin.calendar') }}" class="nav-link {{ request()->routeIs('admin.calendar') ? 'active' : '' }}">
                    <i class="bi bi-calendar3 me-2"></i><span class="nav-text">Calendar</span>
                </a>
            </li>
        @endcan
        @can('settings.view')
            <li class="nav-item">
                <a href="{{ route('admin.website.index') }}" class="nav-link {{ request()->routeIs('admin.website.*') ? 'active' : '' }}">
                    <i class="bi bi-globe me-2"></i><span class="nav-text">Website</span>
                </a>
            </li>
        @endcan

        <li class="sidebar-heading">Management</li>
        @can('properties.view')
            <li class="nav-item">
                <a href="{{ route('admin.properties.index') }}" class="nav-link {{ request()->routeIs('admin.properties.*') ? 'active' : '' }}">
                    <i class="bi bi-building me-2"></i><span class="nav-text">Properties</span>
                </a>
            </li>
        @endcan
        @can('rooms.view')
            <li class="nav-item">
                <a href="{{ route('admin.rooms.manage') }}" class="nav-link {{ request()->routeIs('admin.rooms.manage') ? 'active' : '' }}">
                    <i class="bi bi-door-open me-2"></i><span class="nav-text">Rooms</span>
                </a>
            </li>
        @endcan
        @can('amenities.view')
            <li class="nav-item">
                <a href="{{ route('admin.amenities.index') }}" class="nav-link {{ request()->routeIs('admin.amenities.*') ? 'active' : '' }}">
                    <i class="bi bi-plus-circle me-2"></i><span class="nav-text">Amenities</span>
                </a>
            </li>
        @endcan
        @can('food-drink.view')
            <li class="nav-item">
                <a href="{{ route('admin.food-drink.index') }}" class="nav-link {{ request()->routeIs('admin.food-drink.*') ? 'active' : '' }}">
                    <i class="bi bi-cup-hot me-2"></i><span class="nav-text">Food & Drink</span>
                </a>
            </li>
        @endcan
        @can('places.view')
            <li class="nav-item">
                <a href="{{ route('admin.places.index') }}" class="nav-link {{ request()->routeIs('admin.places.*') ? 'active' : '' }}">
                    <i class="bi bi-geo-alt me-2"></i><span class="nav-text">Places of Interest</span>
                </a>
            </li>
        @endcan
        @can('addons.view')
            <li class="nav-item">
                <a href="{{ route('admin.addons.index') }}" class="nav-link {{ request()->routeIs('admin.addons.*') ? 'active' : '' }}">
                    <i class="bi bi-gift me-2"></i><span class="nav-text">Add-Ons</span>
                </a>
            </li>
        @endcan
        @can('guests.view')
            <li class="nav-item">
                <a href="{{ route('admin.guests.index') }}" class="nav-link {{ request()->routeIs('admin.guests.*') ? 'active' : '' }}">
                    <i class="bi bi-people me-2"></i><span class="nav-text">Guests</span>
                </a>
            </li>
        @endcan
        @can('reservations.view')
            <li class="nav-item">
                <a href="{{ route('admin.reservations.index') }}" class="nav-link {{ request()->routeIs('admin.reservations.*') ? 'active' : '' }}">
                    <i class="bi bi-journal-bookmark me-2"></i><span class="nav-text">Bookings</span>
                </a>
            </li>
        @endcan

        <li class="sidebar-heading">Revenue</li>
        @can('pricing.view')
            <li class="nav-item">
                <a href="{{ route('admin.pricing.index') }}" class="nav-link {{ request()->routeIs('admin.pricing.*') ? 'active' : '' }}">
                    <i class="bi bi-tags me-2"></i><span class="nav-text">Pricing</span>
                </a>
            </li>
        @endcan
        @can('payments.view')
            <li class="nav-item">
                <a href="{{ route('admin.payments.index') }}" class="nav-link {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
                    <i class="bi bi-credit-card me-2"></i><span class="nav-text">Payments</span>
                </a>
            </li>
        @endcan
        @can('reports.view')
            <li class="nav-item">
                <a href="{{ route('admin.revenue.index') }}" class="nav-link {{ request()->routeIs('admin.revenue.*') ? 'active' : '' }}">
                    <i class="bi bi-graph-up me-2"></i><span class="nav-text">Revenue</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.reports.index') }}" class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-bar-graph me-2"></i><span class="nav-text">Reports</span>
                </a>
            </li>
        @endcan

        <li class="sidebar-heading">Channels</li>
        @can('channels.view')
            <li class="nav-item">
                <a href="{{ route('admin.channels.integrations') }}" class="nav-link {{ request()->routeIs('admin.channels.integrations') ? 'active' : '' }}">
                    <i class="bi bi-globe me-2"></i><span class="nav-text">Beds24 integrations</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.channels.airbnb') }}" class="nav-link {{ request()->routeIs('admin.channels.airbnb') ? 'active' : '' }}">
                    <i class="bi bi-house-heart me-2"></i><span class="nav-text">Airbnb via Beds24</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.channels.booking') }}" class="nav-link {{ request()->routeIs('admin.channels.booking') ? 'active' : '' }}">
                    <i class="bi bi-building me-2"></i><span class="nav-text">Booking.com via Beds24</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.channels.vrbo') }}" class="nav-link {{ request()->routeIs('admin.channels.vrbo') ? 'active' : '' }}">
                    <i class="bi bi-house-door me-2"></i><span class="nav-text">VRBO via Beds24</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.channels.setup.page') }}" class="nav-link {{ request()->routeIs('admin.channels.setup.page') ? 'active' : '' }}">
                    <i class="bi bi-key me-2"></i><span class="nav-text">Beds24 setup</span>
                </a>
            </li>
        @endcan
        @can('communications.view')
            <li class="nav-item">
                <a href="{{ route('admin.communications.index') }}" class="nav-link {{ request()->routeIs('admin.communications.*') ? 'active' : '' }}">
                    <i class="bi bi-earbuds me-2"></i><span class="nav-text">Communications</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.messages.index') }}" class="nav-link {{ request()->routeIs('admin.messages.*') ? 'active' : '' }}">
                    <i class="bi bi-chat-dots me-2"></i><span class="nav-text">Messages</span>
                </a>
            </li>
        @endcan
        @can('chatbot.view')
            <li class="nav-item">
                <a href="{{ route('admin.chatbot.index') }}" class="nav-link {{ request()->routeIs('admin.chatbot.*') ? 'active' : '' }}">
                    <i class="bi bi-robot me-2"></i><span class="nav-text">AI Assistant</span>
                </a>
            </li>
        @endcan

        <li class="sidebar-heading">System</li>
        @can('users.view')
            <li class="nav-item">
                <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <i class="bi bi-person-gear me-2"></i><span class="nav-text">Users</span>
                </a>
            </li>
        @endcan
        <li class="nav-item">
            <a href="{{ route('admin.audit-logs') }}" class="nav-link {{ request()->routeIs('admin.audit-logs') ? 'active' : '' }}">
                <i class="bi bi-shield-check me-2"></i><span class="nav-text">Audit Logs</span>
            </a>
        </li>
            <li class="nav-item">
                <a href="{{ route('admin.settings') }}" class="nav-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
                    <i class="bi bi-gear me-2"></i><span class="nav-text">Settings</span>
                </a>
            </li>
            @can('settings.view')
                <li class="nav-item">
                    <a href="{{ route('admin.settings.mail') }}" class="nav-link ps-4 {{ request()->routeIs('admin.settings.mail') ? 'active' : '' }}">
                        <i class="bi bi-envelope me-2"></i><span class="nav-text">Email settings</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.settings.notifications') }}" class="nav-link ps-4 {{ request()->routeIs('admin.settings.notifications') ? 'active' : '' }}">
                        <i class="bi bi-bell me-2"></i><span class="nav-text">Email notifications</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.schedule-settings') }}" class="nav-link ps-4 {{ request()->routeIs('admin.schedule-settings') ? 'active' : '' }}">
                        <i class="bi bi-clock-history me-2"></i><span class="nav-text">Schedule settings</span>
                    </a>
                </li>
            @endcan
    </ul>
    <hr class="text-secondary">
    <div class="dropdown mb-2">
        <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle text-light" data-bs-toggle="dropdown">
            <i class="bi bi-person-circle profile-icon fs-5 me-2"></i>
            <span class="small profile-name">{{ auth()->user()->name }}</span>
        </a>
        <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
            <li><a class="dropdown-item" href="{{ route('account.show') }}"><i class="bi bi-person me-2"></i>My account</a></li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i>Sign out</button>
                </form>
            </li>
        </ul>
    </div>
    <button type="button" class="collapse-toggle d-flex align-items-center" id="sidebarCollapseToggle" data-toggle-sidebar>
        <i class="bi bi-layout-sidebar me-2"></i><span>Collapse</span>
    </button>
</aside>

<div class="offcanvas offcanvas-start ch-sidebar text-bg-dark" tabindex="-1" id="sidebarOffcanvas">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title ch-brand"><i class="bi bi-house-heart-fill brand-mark me-2"></i>{{ $propertyName }}</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column">
        <ul class="nav nav-pills flex-column">
            <li class="nav-item">
                <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2 me-2"></i><span class="nav-text">Dashboard</span>
                </a>
            </li>
            @can('settings.view')
                <li class="nav-item">
                    <a href="{{ route('admin.website.index') }}" class="nav-link {{ request()->routeIs('admin.website.*') ? 'active' : '' }}">
                        <i class="bi bi-globe me-2"></i><span class="nav-text">Website</span>
                    </a>
                </li>
            @endcan
            @can('channels.view')
                <li class="nav-item">
                    <a href="{{ route('admin.channels.integrations') }}" class="nav-link {{ request()->routeIs('admin.channels.integrations') ? 'active' : '' }}">
                        <i class="bi bi-globe me-2"></i><span class="nav-text">Beds24 integrations</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.channels.airbnb') }}" class="nav-link {{ request()->routeIs('admin.channels.airbnb') ? 'active' : '' }}">
                        <i class="bi bi-house-heart me-2"></i><span class="nav-text">Airbnb via Beds24</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.channels.booking') }}" class="nav-link {{ request()->routeIs('admin.channels.booking') ? 'active' : '' }}">
                        <i class="bi bi-building me-2"></i><span class="nav-text">Booking.com via Beds24</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.channels.vrbo') }}" class="nav-link {{ request()->routeIs('admin.channels.vrbo') ? 'active' : '' }}">
                        <i class="bi bi-house-door me-2"></i><span class="nav-text">VRBO via Beds24</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.channels.setup.page') }}" class="nav-link {{ request()->routeIs('admin.channels.setup.page') ? 'active' : '' }}">
                        <i class="bi bi-key me-2"></i><span class="nav-text">Beds24 setup</span>
                    </a>
                </li>
            @endcan
            @can('reservations.view')
                <li class="nav-item"><a href="{{ route('admin.reservations.index') }}" class="nav-link">Bookings</a></li>
            @endcan
            @can('food-drink.view')
                <li class="nav-item"><a href="{{ route('admin.food-drink.index') }}" class="nav-link">Food & Drink</a></li>
            @endcan
            @can('places.view')
                <li class="nav-item"><a href="{{ route('admin.places.index') }}" class="nav-link">Places of Interest</a></li>
            @endcan
            @can('addons.view')
                <li class="nav-item"><a href="{{ route('admin.addons.index') }}" class="nav-link">Add-Ons</a></li>
            @endcan
            @can('reports.view')
                <li class="nav-item"><a href="{{ route('admin.revenue.index') }}" class="nav-link">Revenue</a></li>
            @endcan
            <li class="nav-item">
                <a href="{{ route('admin.audit-logs') }}" class="nav-link {{ request()->routeIs('admin.audit-logs') ? 'active' : '' }}">
                    <i class="bi bi-shield-check me-2"></i><span class="nav-text">Audit Logs</span>
                </a>
            </li>
            <li class="nav-item">
            <a href="{{ route('admin.settings') }}" class="nav-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
                <i class="bi bi-gear me-2"></i><span class="nav-text">Settings</span>
            </a>
        </li>
        @can('settings.view')
            <li class="nav-item">
                <a href="{{ route('admin.settings.mail') }}" class="nav-link ps-4 {{ request()->routeIs('admin.settings.mail') ? 'active' : '' }}">
                    <i class="bi bi-envelope me-2"></i><span class="nav-text">Email settings</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.settings.notifications') }}" class="nav-link ps-4 {{ request()->routeIs('admin.settings.notifications') ? 'active' : '' }}">
                    <i class="bi bi-bell me-2"></i><span class="nav-text">Email notifications</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('admin.schedule-settings') }}" class="nav-link ps-4 {{ request()->routeIs('admin.schedule-settings') ? 'active' : '' }}">
                    <i class="bi bi-clock-history me-2"></i><span class="nav-text">Schedule settings</span>
                </a>
            </li>
        @endcan
    </ul>
        <div class="mt-auto">
            <a href="{{ route('account.show') }}" class="btn btn-outline-light w-100 mb-2"><i class="bi bi-person me-2"></i>My account</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline-light w-100"><i class="bi bi-box-arrow-right me-2"></i>Sign out</button>
            </form>
        </div>
    </div>
</div>
