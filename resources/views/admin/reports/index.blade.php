@extends('layouts.admin.app')
@section('title', 'Reports')
@section('content')
<div class="ch-page-header">
    <div><h4>Reports</h4></div>
    @can('reports.export')
        <a class="btn btn-ch-primary" href="{{ route('admin.reports.export', request()->query()) }}">Export CSV</a>
    @endcan
</div>
<form class="ch-toolbar mb-3 row g-2">
    <div class="col-md-3">
        <select name="type" class="form-select">
            @foreach (['revenue' => 'Revenue', 'occupancy' => 'Occupancy', 'bookings' => 'Booking source', 'cancellations' => 'Cancellations', 'payments' => 'Payments'] as $value => $label)
                <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3"><input type="date" name="from" class="form-control" value="{{ $from }}"></div>
    <div class="col-md-3"><input type="date" name="to" class="form-control" value="{{ $to }}"></div>
    <div class="col-md-3"><button class="btn btn-ch-primary">Run</button></div>
</form>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-striped mb-0">
            @if ($rows)
                <thead><tr>@foreach (array_keys($rows[0]) as $heading)<th>{{ ucfirst(str_replace('_', ' ', $heading)) }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>@foreach ($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
                    @endforeach
                </tbody>
            @else
                <tbody><tr><td class="text-muted p-3">No rows for this report.</td></tr></tbody>
            @endif
        </table>
    </div>
</div>
@endsection
