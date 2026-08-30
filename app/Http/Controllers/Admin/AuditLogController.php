<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::query()->with('user')->latest();

        if ($module = $request->query('module')) {
            $query->where('module', $module);
        }

        if ($action = $request->query('action')) {
            $query->where('action', 'like', "%{$action}%");
        }

        $logs = $query->paginate(25)->withQueryString();

        return view('admin.audit-logs', [
            'logs' => $logs,
            'modules' => AuditLog::query()->select('module')->distinct()->pluck('module')->filter(),
        ]);
    }
}
