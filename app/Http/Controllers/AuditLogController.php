<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\ExportService;
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

        $exportColumns = $this->exportColumns();
        $exportRoute = route('audit.export');

        return view('audit.index', compact('logs', 'actions', 'exportColumns', 'exportRoute') + ['activeAction' => $request->input('action', 'all')]);
    }

    public function export(Request $request, ExportService $export)
    {
        $query = AuditLog::with('user');

        if ($request->filled('action') && $request->input('action') !== 'all') {
            $query->where('action', 'like', '%'.$request->input('action').'%');
        }

        $available = $this->exportColumns();
        $columns = $export->resolveColumns($available, $request->input('columns'));
        $format = in_array($request->input('format', 'pdf'), ['pdf', 'excel'], true) ? $request->input('format') : 'pdf';

        $rows = $query->latest('created_at')->limit(5000)->get()->map(fn (AuditLog $log) => [
            'date' => $log->created_at->format('d M Y H:i'),
            'action' => $log->action,
            'user' => $log->user?->name ?? '—',
            'subject_type' => $log->subject_type ?? '—',
            'subject_id' => $log->subject_id ?? '—',
            'extra' => $log->extra ? json_encode($log->extra) : '—',
        ])->map(fn (array $row) => collect($columns)->mapWithKeys(fn ($col) => [$col['key'] => $row[$col['key']] ?? ''])->all());

        $title = 'Audit Log';
        $subtitle = 'Generated '.now()->format('d M Y H:i').' — '.$rows->count().' records';

        if ($format === 'excel') {
            return $export->excel($title, $columns, $rows);
        }

        return $export->pdf($title, $subtitle, $columns, $rows);
    }

    private function exportColumns(): array
    {
        return [
            ['key' => 'date', 'label' => 'Date'],
            ['key' => 'action', 'label' => 'Action'],
            ['key' => 'user', 'label' => 'User'],
            ['key' => 'subject_type', 'label' => 'Subject Type'],
            ['key' => 'subject_id', 'label' => 'Subject ID'],
            ['key' => 'extra', 'label' => 'Details'],
        ];
    }
}
