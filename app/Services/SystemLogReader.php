<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Reads the application's Monolog files for the admin System Logs page.
 *
 * Two rules shape this class:
 *
 *  - Only files whose basename matches self::FILE_PATTERN are ever opened, and
 *    the resolved path is re-checked with realpath() to stay inside the log
 *    directory. A caller can therefore never point the reader at an arbitrary
 *    path such as .env.
 *  - Only the tail of a file is read (self::MAX_BYTES). A production log grows
 *    without bound, so loading it whole would eventually exhaust memory.
 */
class SystemLogReader
{
    /**
     * @param  string|null  $directory  Overrides the log directory. Defaults to
     *                                  the directory of the configured `single`
     *                                  channel, which is where Laravel actually
     *                                  writes. Tests pass a temporary directory
     *                                  so the real log is never read or replaced.
     */
    public function __construct(private readonly ?string $directory = null) {}

    /**
     * PSR-3 log levels, ordered by increasing severity.
     *
     * @var array<int, string>
     */
    public const LEVELS = ['DEBUG', 'INFO', 'NOTICE', 'WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'];

    /**
     * The largest amount of a single file read per request.
     */
    private const MAX_BYTES = 2_000_000;

    /**
     * How many entries are kept after parsing.
     */
    private const MAX_ENTRIES = 1_000;

    /**
     * The only basenames that may be opened: the current single-channel file
     * and the rotated files produced by the daily channel.
     */
    private const FILE_PATTERN = '/^laravel(?:-\d{4}-\d{2}-\d{2})?\.log$/';

    /**
     * A Monolog line header: [Y-m-d H:i:s] env.LEVEL: message
     */
    private const HEADER_PATTERN = '/^\[(?<timestamp>\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:[+-]\d{4})?)\]\s+(?<env>[\w.-]+)\.(?<level>[A-Z]+):\s?(?<rest>.*)$/';

    /**
     * Log files available to read, newest first.
     *
     * @return Collection<int, array{name: string, label: string, size: int, modified: Carbon|null}>
     */
    public function files(): Collection
    {
        $directory = $this->directory();

        if (! is_dir($directory)) {
            return collect();
        }

        $files = [];

        foreach ((array) scandir($directory) as $entry) {
            if (! is_string($entry) || ! preg_match(self::FILE_PATTERN, $entry)) {
                continue;
            }

            $path = $directory.DIRECTORY_SEPARATOR.$entry;

            if (! is_file($path)) {
                continue;
            }

            $files[] = [
                'name' => $entry,
                'label' => $this->label($entry),
                'size' => (int) filesize($path),
                'modified' => $this->modified($path),
            ];
        }

        usort($files, fn (array $a, array $b): int => ($b['modified']?->getTimestamp() ?? 0) <=> ($a['modified']?->getTimestamp() ?? 0));

        return collect($files)->values();
    }

    /**
     * Whether the given basename is a log file this reader may open.
     */
    public function isAllowed(string $name): bool
    {
        return (bool) preg_match(self::FILE_PATTERN, $name);
    }

    /**
     * The absolute path for a log basename, or null when the name is not
     * allowed or resolves outside the log directory.
     */
    public function path(string $name): ?string
    {
        if (! $this->isAllowed($name)) {
            return null;
        }

        $directory = realpath($this->directory());

        if ($directory === false) {
            return null;
        }

        $path = realpath($directory.DIRECTORY_SEPARATOR.$name);

        if ($path === false || ! is_file($path) || ! str_starts_with($path, $directory.DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $path;
    }

    /**
     * Read the tail of a log file into parsed entries, newest first.
     *
     * @return Collection<int, array{
     *     level: string,
     *     env: string,
     *     timestamp: Carbon|null,
     *     time: string,
     *     message: string,
     *     context: array<string, mixed>,
     *     exception: string|null,
     *     trace: array<int, string>,
     *     raw: string
     * }>
     */
    public function read(string $name): Collection
    {
        $path = $this->path($name);

        if ($path === null) {
            return collect();
        }

        return $this->parse($this->tail($path));
    }

    /**
     * Read at most MAX_BYTES from the end of a file, discarding the partial
     * first entry that the seek lands in the middle of.
     */
    private function tail(string $path): string
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return '';
        }

        $size = (int) (fstat($handle)['size'] ?? 0);
        $offset = $size > self::MAX_BYTES ? $size - self::MAX_BYTES : 0;

        if ($offset > 0) {
            fseek($handle, $offset);
        }

        $chunk = stream_get_contents($handle);
        fclose($handle);

        $chunk = $chunk === false ? '' : $chunk;

        if ($offset > 0) {
            // Cut everything before the first complete entry boundary.
            $boundary = strpos($chunk, "\n[");
            $chunk = $boundary === false ? '' : substr($chunk, $boundary + 1);
        }

        return $chunk;
    }

    /**
     * Split a chunk into entries. Monolog writes one header line per record
     * and every following line belongs to that record until the next header.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function parse(string $chunk): Collection
    {
        if (trim($chunk) === '') {
            return collect();
        }

        $records = [];
        $header = null;
        $lines = [];

        foreach (preg_split('/\R/', $chunk) ?: [] as $line) {
            if (preg_match(self::HEADER_PATTERN, $line, $matches) === 1) {
                if ($header !== null) {
                    $records[] = ['header' => $header, 'lines' => $lines];
                }

                $header = $matches;
                $lines = [$line];

                continue;
            }

            if ($header !== null) {
                $lines[] = $line;
            }
        }

        if ($header !== null) {
            $records[] = ['header' => $header, 'lines' => $lines];
        }

        return collect($records)
            ->reverse()
            ->take(self::MAX_ENTRIES)
            ->map(fn (array $record): array => $this->shape($record['header'], $record['lines']))
            ->values();
    }

    /**
     * Turn one raw record into the shape the view renders.
     *
     * @param  array<string, string>  $header
     * @param  array<int, string>  $lines
     * @return array<string, mixed>
     */
    private function shape(array $header, array $lines): array
    {
        // Everything after the "[stacktrace]" marker is the trace; what came
        // before it is the message and its JSON context.
        $continuation = array_slice($lines, 1);
        $traceAt = array_search('[stacktrace]', $continuation, true);

        if ($traceAt === false) {
            $body = array_merge([$header['rest']], $continuation);
            $trace = [];
        } else {
            $body = array_merge([$header['rest']], array_slice($continuation, 0, $traceAt));
            $trace = array_values(array_filter(
                array_slice($continuation, $traceAt + 1),
                fn (string $line): bool => trim($line) !== ''
            ));
        }

        $text = trim(implode("\n", $body));

        // A Throwable logged as the message itself is stringified by PHP, which
        // puts the trace inline after a "Stack trace:" line instead of using
        // Monolog's [stacktrace] marker. Both shapes occur in real logs.
        if ($trace === [] && preg_match('/^Stack trace:\s*$/mi', $text) === 1) {
            [$text, $inline] = preg_split('/^Stack trace:\s*$/mi', $text, 2);
            $trace = array_values(array_filter(
                preg_split('/\R/', (string) $inline) ?: [],
                fn (string $line): bool => trim($line) !== ''
            ));
            $text = trim((string) $text);
        }

        $context = $this->extractContext($text);
        $message = $context === [] ? $text : trim(substr($text, 0, (int) strrpos($text, '{')));

        $timestamp = $this->parseTimestamp($header['timestamp']);

        return [
            'level' => $header['level'],
            'env' => $header['env'],
            'timestamp' => $timestamp,
            'time' => $timestamp?->format('d M Y H:i:s') ?? $header['timestamp'],
            'message' => $message === '' ? '(no message)' : $message,
            'context' => $context,
            'exception' => $this->exception($context, $message),
            'trace' => $trace,
            'raw' => trim(implode("\n", $lines)),
        ];
    }

    /**
     * Pull the trailing JSON context Monolog appends to a record.
     *
     * @return array<string, mixed>
     */
    private function extractContext(string $text): array
    {
        $text = rtrim($text);

        if (! str_ends_with($text, '}')) {
            return [];
        }

        // Try each opening brace from the left: the first one that decodes to
        // a JSON object is the context. Log messages can themselves contain
        // braces, so scanning from the right alone is not reliable.
        $offset = 0;

        while (($brace = strpos($text, '{', $offset)) !== false) {
            $decoded = json_decode(substr($text, $brace), true);

            if (is_array($decoded)) {
                return $decoded;
            }

            $offset = $brace + 1;
        }

        return [];
    }

    /**
     * The exception line for a record, when Monolog recorded one.
     */
    private function exception(array $context, string $message): ?string
    {
        $exception = $context['exception'] ?? null;

        if (is_string($exception) && $exception !== '') {
            // Laravel serialises this as "[object] (Class: message)".
            if (preg_match('/\[object\]\s*\((?<inner>.+)\)$/s', $exception, $matches) === 1) {
                return trim($matches['inner']);
            }

            return $exception;
        }

        return preg_match('/^(?<class>[\w\\\\]*(?:Exception|Error|Failure))\b/', $message, $matches) === 1
            ? $message
            : null;
    }

    private function parseTimestamp(string $value): ?Carbon
    {
        try {
            return Carbon::createFromFormat('Y-m-d H:i:s', $value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * A readable label: the rotated filename carries its own date.
     */
    private function label(string $name): string
    {
        if (preg_match('/^laravel-(?<date>\d{4}-\d{2}-\d{2})\.log$/', $name, $matches) === 1) {
            return 'Laravel — '.Carbon::parse($matches['date'])->format('d M Y');
        }

        return 'Laravel — current';
    }

    private function modified(string $path): ?Carbon
    {
        $time = @filemtime($path);

        return $time === false ? null : Carbon::createFromTimestamp($time);
    }

    private function directory(): string
    {
        if ($this->directory !== null) {
            return $this->directory;
        }

        $configured = config('logging.channels.single.path');

        return is_string($configured) && $configured !== ''
            ? dirname($configured)
            : storage_path('logs');
    }
}
