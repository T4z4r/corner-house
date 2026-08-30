@php
    $icon = $icon ?? 'bi-inbox';
    $message = $message ?? 'Nothing to show here yet.';
    $hint = $hint ?? '';
    $colspan = $colspan ?? 1;
    $actionUrl = $actionUrl ?? null;
    $actionLabel = $actionLabel ?? 'Add';
    $actionIcon = $actionIcon ?? 'bi-plus-lg';
@endphp
<tr>
    <td colspan="{{ $colspan }}">
        <div class="ch-empty">
            <i class="bi {{ $icon }}"></i>
            <div class="lead">{{ $message }}</div>
            @if ($hint)
                <div class="small">{{ $hint }}</div>
            @endif
            @if ($actionUrl)
                <div class="mt-3 ch-empty-action">
                    <a href="{{ $actionUrl }}" class="btn btn-ch-primary">
                        <i class="bi {{ $actionIcon }} me-1"></i>{{ $actionLabel }}
                    </a>
                </div>
            @endif
        </div>
    </td>
</tr>
