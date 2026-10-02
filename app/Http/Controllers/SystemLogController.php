<?php

namespace App\Http\Controllers;

use App\Services\SystemLogReader;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SystemLogController extends Controller
{
    /**
     * Entries shown per page.
     */
    private const PER_PAGE = 25;

    public function __construct(private readonly SystemLogReader $logs) {}

    /**
     * Show the tail of the application log.
     *
     * Gated to administrators by the `role:admin` middleware on the route:
     * log files carry SQL bindings, file paths and session details, so they are
     * never exposed to supervisors or cashiers.
     */
    public function __invoke(Request $request): View
    {
        $files = $this->logs->files();
        $available = $files->pluck('name')->all();

        $request->validate([
            // Only a discovered log basename is ever accepted, so a crafted
            // ?file= value cannot walk out of storage/logs.
            'file' => ['nullable', 'string', Rule::in($available)],
            'level' => ['nullable', 'string', Rule::in(SystemLogReader::LEVELS)],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $name = (string) $request->query('file', $available[0] ?? '');
        $level = $request->query('level');
        $search = trim((string) $request->query('search', ''));

        $entries = $name === '' ? collect() : $this->logs->read($name);
        $levels = $this->levelCounts($entries);

        $filtered = $this->filter($entries, $level, $search);

        $logs = new LengthAwarePaginator(
            $filtered->forPage((int) $request->query('page', 1), self::PER_PAGE)->values(),
            $filtered->count(),
            self::PER_PAGE,
            (int) $request->query('page', 1),
            ['path' => $request->url()]
        );
        $logs->withQueryString();

        return view('system-logs.index', [
            'logs' => $logs,
            'files' => $files,
            'selectedFile' => $name,
            'fileMeta' => $files->firstWhere('name', $name),
            'levelCounts' => $levels,
            'selectedLevel' => $level,
            'search' => $search,
            'availableLevels' => SystemLogReader::LEVELS,
            'totalScanned' => $entries->count(),
        ]);
    }

    /**
     * How many entries each level has in this file, for the filter chips.
     *
     * @param  Collection<int, array<string, mixed>>  $entries
     * @return array<string, int>
     */
    private function levelCounts(Collection $entries): array
    {
        $counts = array_fill_keys(SystemLogReader::LEVELS, 0);

        foreach ($entries as $entry) {
            if (isset($counts[$entry['level']])) {
                $counts[$entry['level']]++;
            }
        }

        return $counts;
    }

    /**
     * Apply the level and search filters. Search covers the message, the
     * context and the trace so a stack frame can be found by file name.
     *
     * @param  Collection<int, array<string, mixed>>  $entries
     * @return Collection<int, array<string, mixed>>
     */
    private function filter(Collection $entries, ?string $level, string $search): Collection
    {
        if ($level !== null && $level !== '') {
            $entries = $entries->where('level', $level)->values();
        }

        if ($search === '') {
            return $entries;
        }

        $needle = mb_strtolower($search);

        // The search covers the message, the exception, the JSON context and the
        // raw record, so a stack frame can be located by file name.
        return $entries
            ->filter(fn (array $entry): bool => str_contains(
                mb_strtolower(implode("\n", [
                    $entry['message'],
                    (string) ($entry['exception'] ?? ''),
                    (string) json_encode($entry['context']),
                    $entry['raw'],
                ])),
                $needle
            ))
            ->values();
    }
}
