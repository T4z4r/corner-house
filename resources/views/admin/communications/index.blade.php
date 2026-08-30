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
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                Templates
                @can('communications.manage_templates')
                    <button class="btn btn-ch-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createTemplateModal"><i class="bi bi-plus me-1"></i>New</button>
                @endcan
            </div>
            <div class="card-body">
                @foreach ($templates as $template)
                    <div class="small border-bottom py-2 d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ $template->name }}</strong> · {{ $template->event }} · <span class="badge {{ $template->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $template->is_active ? 'Active' : 'Inactive' }}</span>
                        </div>
                        <div class="d-flex gap-1">
                            <button class="btn btn-outline-secondary btn-sm" title="View" data-bs-toggle="modal" data-bs-target="#viewTemplate{{ $template->id }}"><i class="bi bi-eye"></i></button>
                            @can('communications.manage_templates')
                                <button class="btn btn-outline-secondary btn-sm" title="Edit" data-bs-toggle="modal" data-bs-target="#editTemplate{{ $template->id }}"><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-outline-danger btn-sm" title="Delete" data-bs-toggle="modal" data-bs-target="#deleteTemplate{{ $template->id }}"><i class="bi bi-trash"></i></button>
                            @endcan
                        </div>
                    </div>
                @endforeach
                @if ($templates->isEmpty())
                    <div class="text-muted small">No templates yet.</div>
                @endif
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
                            <tr><td colspan="5" class="text-muted">No messages yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{ $communications->links() }}</div>
    </div>
</div>

@can('communications.manage_templates')
<div class="modal fade" id="createTemplateModal" tabindex="-1" aria-labelledby="createTemplateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.communications.templates.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createTemplateModalLabel">New template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Event *</label>
                        <input type="text" name="event" class="form-control" placeholder="e.g. booking_confirmation" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Channel *</label>
                        <select name="channel" class="form-select" required>
                            <option value="email">Email</option>
                            <option value="sms">SMS</option>
                            <option value="whatsapp">WhatsApp</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Body *</label>
                        <textarea name="body" class="form-control" rows="4" placeholder="Use @{{guest_name}} tokens" required></textarea>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="create_is_active" name="is_active" value="1" checked>
                        <label class="form-check-label" for="create_is_active">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-ch-primary">Save template</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endcan

@foreach ($templates as $template)
<div class="modal fade" id="viewTemplate{{ $template->id }}" tabindex="-1" aria-labelledby="viewTemplateLabel{{ $template->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewTemplateLabel{{ $template->id }}">{{ $template->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-sm-4"><span class="text-muted">Event:</span> {{ $template->event }}</div>
                    <div class="col-sm-4"><span class="text-muted">Channel:</span> {{ ucfirst($template->channel) }}</div>
                    <div class="col-sm-4"><span class="text-muted">Status:</span> <span class="badge {{ $template->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $template->is_active ? 'Active' : 'Inactive' }}</span></div>
                </div>
                @if ($template->subject)
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Subject</label>
                        <div class="p-3 rounded" style="background:#f8f9fa;">{{ $template->subject }}</div>
                    </div>
                @endif
                <div class="mb-3">
                    <label class="form-label fw-semibold">Body</label>
                    <div class="p-3 rounded" style="background:#f8f9fa; white-space:pre-wrap;">{{ $template->body }}</div>
                </div>
                <div class="small text-muted">
                    Slug: <code>{{ $template->slug }}</code> · Created: {{ $template->created_at?->format('d M Y H:i') ?? '-' }} · Updated: {{ $template->updated_at?->format('d M Y H:i') ?? '-' }}
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@can('communications.manage_templates')
<div class="modal fade" id="editTemplate{{ $template->id }}" tabindex="-1" aria-labelledby="editTemplateLabel{{ $template->id }}" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.communications.templates.update', $template) }}">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editTemplateLabel{{ $template->id }}">Edit {{ $template->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $template->name) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Event *</label>
                        <input type="text" name="event" class="form-control" value="{{ old('event', $template->event) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Channel *</label>
                        <select name="channel" class="form-select" required>
                            @foreach (['email', 'sms', 'whatsapp'] as $ch)
                                <option value="{{ $ch }}" @selected(old('channel', $template->channel) === $ch)>{{ ucfirst($ch) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" class="form-control" value="{{ old('subject', $template->subject) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Body *</label>
                        <textarea name="body" class="form-control" rows="4" required>{{ old('body', $template->body) }}</textarea>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="edit_is_active_{{ $template->id }}" name="is_active" value="1" @checked(old('is_active', $template->is_active))>
                        <label class="form-check-label" for="edit_is_active_{{ $template->id }}">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-ch-primary">Save changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="deleteTemplate{{ $template->id }}" tabindex="-1" aria-labelledby="deleteTemplateLabel{{ $template->id }}" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('admin.communications.templates.destroy', $template) }}">
            @csrf
            @method('DELETE')
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteTemplateLabel{{ $template->id }}">Delete template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete <strong>{{ $template->name }}</strong>? This cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endcan
@endforeach
@endsection
