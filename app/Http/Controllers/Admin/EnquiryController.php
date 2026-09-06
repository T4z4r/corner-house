<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EnquiryController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function index(Request $request): View
    {
        $query = Enquiry::query()
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = $request->string('search')->toString();

                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function (Builder $query) use ($request): void {
                $query->where('status', $request->string('status')->toString());
            })
            ->when($request->filled('type'), function (Builder $query) use ($request): void {
                $query->where('type', $request->string('type')->toString());
            })
            ->orderByDesc('id');

        $items = $query->paginate(20)->withQueryString();

        return view('admin.enquiries.index', [
            'items' => $items,
            'newCount' => Enquiry::new()->count(),
        ]);
    }

    public function markRead(Enquiry $enquiry): RedirectResponse
    {
        if ($enquiry->status !== Enquiry::STATUS_READ) {
            $enquiry->update(['status' => Enquiry::STATUS_READ]);
            $this->auditLogger->log('enquiry.read', 'enquiries', 'enquiry', (string) $enquiry->id);
        }

        return back()->with('status', 'Enquiry marked as read.');
    }

    public function destroy(Enquiry $enquiry): RedirectResponse
    {
        $enquiry->delete();
        $this->auditLogger->log('enquiry.deleted', 'enquiries', 'enquiry', (string) $enquiry->id);

        return back()->with('status', 'Enquiry deleted.');
    }
}
