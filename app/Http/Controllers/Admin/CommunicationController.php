<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Communication;
use App\Models\CommunicationTemplate;
use App\Models\Guest;
use App\Services\Audit\AuditLogger;
use App\Services\Notification\NotificationService;
use App\Services\Notification\SystemNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CommunicationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly AuditLogger $auditLogger,
        private readonly SystemNotificationService $systemNotifications,
    ) {}

    public function index(): View
    {
        return view('admin.communications.index', [
            'communications' => Communication::query()->with(['guest', 'reservation'])->latest()->paginate(20),
            'templates' => CommunicationTemplate::query()->orderBy('name')->get(),
            'guests' => Guest::query()->orderBy('last_name')->limit(200)->get(),
        ]);
    }

    public function storeTemplate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'event' => ['required', 'string', 'max:100'],
            'channel' => ['required', 'in:email,sms,whatsapp'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        CommunicationTemplate::create([
            ...$data,
            'slug' => Str::slug($data['name']).'-'.Str::random(4),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->auditLogger->log('communications.template_created', 'communications');

        return back()->with('status', 'Template saved.');
    }

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'guest_id' => ['nullable', 'exists:guests,id'],
            'recipient' => ['required', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'channel' => ['required', 'in:email,sms,whatsapp'],
        ]);

        $communication = $this->notifications->sendManual($data);
        $this->systemNotifications->communicationQueued($communication, auth()->id());
        $this->auditLogger->log('communications.sent', 'communications');

        return back()->with('status', 'Message queued.');
    }

    public function updateTemplate(Request $request, CommunicationTemplate $template): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'event' => ['required', 'string', 'max:100'],
            'channel' => ['required', 'in:email,sms,whatsapp'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $template->update([
            ...$data,
            'slug' => Str::slug($data['name']).'-'.Str::random(4),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->auditLogger->log('communications.template_updated', 'communications');

        return redirect()->route('admin.communications.index')->with('status', 'Template updated.');
    }

    public function destroyTemplate(CommunicationTemplate $template): RedirectResponse
    {
        $template->delete();
        $this->auditLogger->log('communications.template_deleted', 'communications');

        return redirect()->route('admin.communications.index')->with('status', 'Template deleted.');
    }
}
