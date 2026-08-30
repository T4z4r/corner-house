@extends('layouts.admin.app')
@section('title', 'Communications')
@section('content')
<div class="ch-page-header"><div><h4>Guest communications</h4></div></div>
<div class="row g-3">
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white">Send message</div>
            <div class="card-body">
                @can('communications.send')
                    <form method="POST" action="{{ route('admin.communications.send') }}">
                        @csrf
                        <div class="mb-2">
                            <select name="guest_id" class="form-select">
                                <option value="">Guest (optional)</option>
                                @foreach ($guests as $guest)
                                    <option value="{{ $guest->id }}" @selected(old('guest_id') == $guest->id)>{{ $guest->full_name }} — {{ $guest->email }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2"><input type="email" name="recipient" class="form-control" placeholder="Recipient email" value="{{ old('recipient') }}" required></div>
                        <div class="mb-2">
                            <select name="channel" class="form-select" required>
                                <option value="email" @selected(old('channel', 'email') === 'email')>Email</option>
                                <option value="sms" @selected(old('channel') === 'sms')>SMS</option>
                                <option value="whatsapp" @selected(old('channel') === 'whatsapp')>WhatsApp</option>
                            </select>
                        </div>
                        <div class="mb-2"><input type="text" name="subject" class="form-control" placeholder="Subject" value="{{ old('subject') }}" required></div>
                        <div class="mb-2"><textarea name="body" class="form-control" rows="4" required>{{ old('body') }}</textarea></div>
                        <button class="btn btn-ch-primary">Send message</button>
                    </form>
                @endcan
            </div>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">Templates</div>
            <div class="card-body">
                @can('communications.manage_templates')
                    <form method="POST" action="{{ route('admin.communications.templates.store') }}" class="mb-3">
                        @csrf
                        <input name="name" class="form-control mb-2" placeholder="Name" required>
                        <input name="event" class="form-control mb-2" placeholder="Event e.g. booking_confirmation" required>
                        <select name="channel" class="form-select mb-2"><option value="email">Email</option></select>
                        <input name="subject" class="form-control mb-2" placeholder="Subject">
                        <textarea name="body" class="form-control mb-2" rows="3" placeholder="Body with @{{guest_name}} tokens" required></textarea>
                        <button class="btn btn-ch-primary btn-sm">Save template</button>
                    </form>
                @endcan
                @foreach ($templates as $template)
                    <div class="small border-bottom py-2"><strong>{{ $template->name }}</strong> · {{ $template->event }}</div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">History</div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Direction</th><th>To / From</th><th>Subject</th><th>Status</th><th>Sent</th></tr></thead>
                    <tbody>
                        @forelse ($communications as $communication)
                            <tr>
                                <td>{{ ucfirst($communication->direction ?? 'outbound') }}</td>
                                <td>{{ $communication->sender_name ? $communication->sender_name.' · ' : '' }}{{ $communication->recipient }}</td>
                                <td>{{ $communication->subject }}</td>
                                <td>{{ $communication->status }}</td>
                                <td>{{ $communication->sent_at?->diffForHumans() ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-muted">No messages yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $communications->links() }}</div>
    </div>
</div>
@endsection
