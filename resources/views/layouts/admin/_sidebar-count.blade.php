@isset($sidebarCounts[$section])
    <span class="badge rounded-pill bg-secondary ms-auto" aria-label="{{ $sidebarCounts[$section]['total'] }} total {{ $section }}" title="Total {{ $section }}">{{ $sidebarCounts[$section]['total'] }}</span>
    @if ($sidebarCounts[$section]['pending'] > 0)
        <span class="badge rounded-pill bg-warning text-dark ms-1" aria-label="{{ $sidebarCounts[$section]['pending'] }} pending {{ $section }}" title="Pending {{ $section }}">{{ $sidebarCounts[$section]['pending'] }} pending</span>
    @endif
@endisset
