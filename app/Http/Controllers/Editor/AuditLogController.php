<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\Editor\EditorAuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = EditorAuditLog::with('editor:id,name,email')
            ->when($request->editor_id, fn ($q, $id) => $q->where('editor_id', $id))
            ->when($request->action,    fn ($q, $a)  => $q->where('action', $a))
            ->when($request->type,      fn ($q, $t)  => $q->where('target_type', $t))
            ->latest()
            ->paginate(25)
            ->through(fn ($l) => [
                'id'           => $l->id,
                'editor_name'  => $l->editor?->name ?? 'Unknown',
                'editor_email' => $l->editor?->email ?? '—',
                'action'       => $l->action,
                'target_type'  => $l->target_type,
                'target_id'    => $l->target_id,
                'old_values'   => $l->old_values,
                'new_values'   => $l->new_values,
                'ip_address'   => $l->ip_address,
                'created_at'   => $l->created_at->toDateTimeString(),
            ]);

        return Inertia::render('Editor/AuditLog', [
            'logs'    => $logs,
            'filters' => $request->only(['editor_id', 'action', 'type']),
        ]);
    }
}