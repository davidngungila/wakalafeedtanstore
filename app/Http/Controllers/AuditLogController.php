<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function __invoke(Request $request): View
    {
        $query = AuditLog::with('user');

        if ($request->filled('action') && $request->input('action') !== 'all') {
            $query->where('action', 'like', '%'.$request->input('action').'%');
        }

        $logs = $query->latest('created_at')->limit(200)->get();

        $actions = AuditLog::query()
            ->distinct()
            ->pluck('action')
            ->map(fn (string $action) => [
                'value' => $action,
                'label' => $action,
            ])
            ->sortBy('label')
            ->values();

        return view('audit.index', compact('logs', 'actions') + ['activeAction' => $request->input('action', 'all')]);
    }
}
