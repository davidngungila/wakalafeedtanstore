<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\User;
use App\Services\ExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::with('agent');

        if ($request->filled('role') && $request->input('role') !== 'all') {
            $query->where('role', $request->input('role'));
        }

        $users = $query->orderByDesc('is_active')->orderBy('created_at')->get();

        $agents = Agent::where('status', 'active')->orderBy('name')->get(['id', 'name', 'code']);

        $exportColumns = $this->exportColumns();
        $exportRoute = route('users.export');

        return view('users.index', compact('users', 'agents', 'exportColumns', 'exportRoute') + ['activeRole' => $request->input('role', 'all')]);
    }

    public function export(Request $request, ExportService $export)
    {
        $query = User::with('agent');

        if ($request->filled('role') && $request->input('role') !== 'all') {
            $query->where('role', $request->input('role'));
        }

        $available = $this->exportColumns();
        $columns = $export->resolveColumns($available, $request->input('columns'));
        $format = in_array($request->input('format', 'pdf'), ['pdf', 'excel'], true) ? $request->input('format') : 'pdf';

        $rows = $query->orderBy('created_at')->get()->map(fn (User $u) => [
            'name' => $u->name,
            'email' => $u->email,
            'phone' => $u->phone ?? '—',
            'role' => ucfirst($u->role),
            'agent' => $u->agent?->name ?? '—',
            'status' => $u->is_active ? 'Active' : 'Inactive',
            'joined' => $u->created_at->format('d M Y'),
        ])->map(fn (array $row) => collect($columns)->mapWithKeys(fn ($col) => [$col['key'] => $row[$col['key']] ?? ''])->all());

        $title = 'Users Report';
        $subtitle = 'Generated '.now()->format('d M Y H:i').' — '.$rows->count().' records';

        if ($format === 'excel') {
            return $export->excel($title, $columns, $rows);
        }

        return $export->pdf($title, $subtitle, $columns, $rows);
    }

    private function exportColumns(): array
    {
        return [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'email', 'label' => 'Email'],
            ['key' => 'phone', 'label' => 'Phone'],
            ['key' => 'role', 'label' => 'Role'],
            ['key' => 'agent', 'label' => 'Agent'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'joined', 'label' => 'Joined'],
        ];
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', Rule::in(['cashier', 'supervisor', 'admin'])],
            'agent_id' => ['nullable', 'exists:agents,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user = User::create($validated + ['is_active' => $request->boolean('is_active')]);

        $this->recordAudit('User account created', 'User', $user->id, ['email' => $user->email, 'role' => $user->role]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'User account created successfully.']);
        }

        return back()->with('status', 'User account created successfully.');
    }

    public function update(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email,'.$user->id],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['required', Rule::in(['cashier', 'supervisor', 'admin'])],
            'agent_id' => ['nullable', 'exists:agents,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($user->id === auth()->id() && ($validated['role'] !== 'admin' || ! $request->boolean('is_active'))) {
            return response()->json(['success' => false, 'message' => 'You cannot remove your own administrator access.'], 422);
        }

        if ($user->role === 'admin' && $user->id !== auth()->id() && ! User::where('role', 'admin')->where('id', '<>', $user->id)->exists()
            && ($validated['role'] !== 'admin' || ! $request->boolean('is_active'))) {
            return response()->json(['success' => false, 'message' => 'A system must always retain at least one active administrator.'], 422);
        }

        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        $validated['is_active'] = $request->boolean('is_active');

        $user->update($validated);

        $this->recordAudit('User account updated', 'User', $user->id, ['email' => $user->email]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'User account updated successfully.']);
        }

        return back()->with('status', 'User account updated successfully.');
    }

    public function show(User $user): View
    {
        $user->load('agent');

        return view('users.show', compact('user'));
    }

    public function edit(User $user): View
    {
        $user->load('agent');
        $agents = Agent::where('status', 'active')->orderBy('name')->get(['id', 'name', 'code']);

        return view('users.edit', compact('user', 'agents'));
    }

    public function destroy(Request $request, User $user): JsonResponse|RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return response()->json(['success' => false, 'message' => 'You cannot delete your own account.'], 422);
        }

        if ($user->role === 'admin' && ! User::where('role', 'admin')->where('id', '<>', $user->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'A system must always retain at least one active administrator.'], 422);
        }

        $this->recordAudit('User account deleted', 'User', $user->id, ['email' => $user->email]);

        $user->delete();

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'User account removed successfully.']);
        }

        return back()->with('status', 'User account removed successfully.');
    }
}
