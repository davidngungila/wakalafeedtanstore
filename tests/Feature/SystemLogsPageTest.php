<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SystemLogReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use ReflectionMethod;
use Tests\TestCase;

class SystemLogsPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * An isolated log directory. The reader is pointed here so the suite never
     * reads, writes or deletes the real storage/logs/laravel.log.
     */
    private string $logDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'wakala-logs-'.bin2hex(random_bytes(6));
        mkdir($this->logDir, 0777, true);

        $this->app->instance(SystemLogReader::class, new SystemLogReader($this->logDir));
    }

    protected function tearDown(): void
    {
        if (is_dir($this->logDir)) {
            foreach ((array) glob($this->logDir.DIRECTORY_SEPARATOR.'*') as $file) {
                if (is_string($file) && is_file($file)) {
                    @unlink($file);
                }
            }

            @rmdir($this->logDir);
        }

        parent::tearDown();
    }

    private function logPath(): string
    {
        return $this->logDir.DIRECTORY_SEPARATOR.'laravel.log';
    }

    public function test_admin_can_open_the_system_logs_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->writeLog([
            '[2026-10-02 09:15:00] local.INFO: Application booted {"release":"1.0"}',
            '[2026-10-02 09:16:00] local.ERROR: Payment gateway unreachable {"attempt":2}',
        ]);

        $this->actingAs($admin)
            ->get(route('system.logs'))
            ->assertOk()
            ->assertSee('System Logs')
            ->assertSee('Application booted')
            ->assertSee('Payment gateway unreachable')
            ->assertSee('INFO')
            ->assertSee('ERROR');
    }

    public function test_supervisor_and_cashier_are_forbidden(): void
    {
        foreach (['supervisor', 'cashier'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get(route('system.logs'))
                ->assertForbidden();
        }
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('system.logs'))->assertRedirect(route('login'));
    }

    public function test_the_page_rejects_a_file_outside_the_log_directory(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->writeLog(['[2026-10-02 09:15:00] local.INFO: booted']);

        // Path traversal must never resolve to a readable file.
        $this->actingAs($admin)
            ->get(route('system.logs', ['file' => '../../.env']))
            ->assertSessionHasErrors('file');

        $this->actingAs($admin)
            ->get(route('system.logs', ['file' => 'laravel.log.evil']))
            ->assertSessionHasErrors('file');
    }

    public function test_entries_can_be_filtered_by_level_and_searched(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->writeLog([
            '[2026-10-02 09:15:00] local.INFO: Order received {"id":7}',
            '[2026-10-02 09:16:00] local.WARNING: Float running low {"network":"Tigo"}',
            '[2026-10-02 09:17:00] local.ERROR: Payment gateway unreachable {"attempt":2}',
            '[2026-10-02 09:18:00] local.ERROR: Unreachable {"attempt":3}',
            '[stacktrace]',
            '#0 /app/Http/Controllers/SomeController.php(88): SomeController->handle()',
            '#1 /app/Http/Controllers/SomeController.php(120): SomeController->__invoke(Array)',
        ]);

        $this->actingAs($admin)
            ->get(route('system.logs', ['level' => 'ERROR']))
            ->assertOk()
            ->assertSee('Payment gateway unreachable')
            ->assertDontSee('Order received');

        $this->actingAs($admin)
            ->get(route('system.logs', ['search' => 'Float']))
            ->assertOk()
            ->assertSee('Float running low')
            ->assertDontSee('Order received');

        // Search also reaches the trace, so a stack frame can be found by file name.
        $this->actingAs($admin)
            ->get(route('system.logs', ['search' => 'SomeController.php(88)']))
            ->assertOk()
            ->assertSee('Unreachable')
            ->assertDontSee('Order received')
            ->assertDontSee('Float running low');
    }

    public function test_the_page_handles_a_missing_log_file(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('system.logs'))
            ->assertOk()
            ->assertSee('No log files are present');
    }

    public function test_the_reader_parses_multi_line_entries_with_context_and_trace(): void
    {
        $this->writeLog([
            '[2026-10-02 09:15:00] local.INFO: Order received {"id":7,"total":1500}',
            '[2026-10-02 09:16:00] local.ERROR: Call to undefined method Foo() {"attempt":2}',
            '[stacktrace]',
            '#0 /app/Http/Controllers/SomeController.php(88): SomeController->handle()',
            '#1 /app/vendor/laravel/framework/src/Router.php(816): SomeController->__invoke(Array)',
        ]);

        $entries = app(SystemLogReader::class)->read('laravel.log');

        $this->assertCount(2, $entries);

        // Newest first.
        $error = $entries->first();
        $this->assertSame('ERROR', $error['level']);
        $this->assertSame('local', $error['env']);
        $this->assertSame('Call to undefined method Foo()', $error['message']);
        $this->assertSame(['attempt' => 2], $error['context']);
        $this->assertCount(2, $error['trace']);
        $this->assertStringContainsString('SomeController.php(88)', $error['trace'][0]);

        $info = $entries->last();
        $this->assertSame('INFO', $info['level']);
        $this->assertSame('Order received', $info['message']);
        $this->assertSame(['id' => 7, 'total' => 1500], $info['context']);
        $this->assertSame([], $info['trace']);
    }

    public function test_the_reader_ignores_files_that_are_not_laravel_logs(): void
    {
        $reader = app(SystemLogReader::class);

        $this->assertTrue($reader->isAllowed('laravel.log'));
        $this->assertTrue($reader->isAllowed('laravel-2026-10-01.log'));

        $this->assertFalse($reader->isAllowed('.env'));
        $this->assertFalse($reader->isAllowed('../../.env'));
        $this->assertFalse($reader->isAllowed('laravel.log.bak'));
        $this->assertFalse($reader->isAllowed('/etc/passwd'));

        $this->assertNull($reader->path('../.env'));
        $this->assertNull($reader->path('does-not-exist.log'));
    }

    /**
     * Guards a destructive mistake: the reader must default to the directory
     * Laravel actually logs into, and this suite must never write there.
     */
    public function test_the_reader_defaults_to_the_configured_logging_directory(): void
    {
        $reader = new SystemLogReader;

        $expected = dirname((string) config('logging.channels.single.path'));

        $this->assertSame($expected, (new ReflectionMethod($reader, 'directory'))->invoke($reader));
        $this->assertStringStartsWith(storage_path(), $expected);
    }

    public function test_this_suite_never_touches_the_real_application_log(): void
    {
        $real = storage_path('logs/laravel.log');
        $existed = is_file($real);

        $this->writeLog(['[2026-10-02 09:15:00] local.INFO: isolated fixture']);

        $this->assertNotSame(realpath($this->logDir), realpath(storage_path('logs')),
            'The fixture directory must not be the real log directory.');
        $this->assertSame(
            $existed,
            is_file($real),
            'Writing a fixture must never create or remove storage/logs/laravel.log.'
        );
    }

    /**
     * Real logs contain two different trace layouts: Monolog's own
     * "[stacktrace]" marker, and PHP's inline "Stack trace:" when a Throwable
     * was passed as the log message. Both must be lifted out of the message.
     */
    public function test_the_reader_handles_both_stack_trace_layouts(): void
    {
        $this->writeLog([
            '[2026-10-02 09:16:00] local.ERROR: Gateway unreachable {"attempt":2}',
            '[stacktrace]',
            '#0 /app/Http/Controllers/SomeController.php(88): SomeController->handle()',
            '[2026-10-02 09:17:00] local.ERROR: RuntimeException: inline trace in D:/app/Foo.php(12)',
            'Stack trace:',
            '#0 /app/Http/Controllers/OtherController.php(44): OtherController->run()',
            '#1 /app/public/index.php(13): OtherController->__construct()',
        ]);

        $entries = app(SystemLogReader::class)->read('laravel.log');

        // Marker layout.
        $marker = $entries->firstWhere('message', 'Gateway unreachable');
        $this->assertNotNull($marker, 'Marker-layout entry was not parsed.');
        $this->assertCount(1, $marker['trace']);
        $this->assertStringContainsString('SomeController.php(88)', $marker['trace'][0]);

        // Inline layout.
        $inline = $entries->first(fn (array $e): bool => str_contains($e['message'], 'RuntimeException'));
        $this->assertNotNull($inline, 'Inline-layout entry was not parsed.');
        $this->assertCount(2, $inline['trace']);
        $this->assertStringContainsString('OtherController.php(44)', $inline['trace'][0]);
        // The trace must not leak into the message.
        $this->assertStringNotContainsString('Stack trace:', $inline['message']);
        $this->assertStringNotContainsString('#0 /app', $inline['message']);
        $this->assertStringContainsString('RuntimeException', $inline['exception']);
    }

    public function test_the_reader_reads_the_tail_of_a_large_file(): void
    {
        // A leading entry that sits before the read window must not appear,
        // and the newest entry must.
        $lines = ['[2026-10-02 08:00:00] local.INFO: OLDEST ENTRY MARKER'];
        for ($i = 0; $i < 60_000; $i++) {
            $lines[] = '[2026-10-02 09:00:00] local.INFO: filler '.$i.' '.str_repeat('x', 60);
        }
        $lines[] = '[2026-10-02 09:30:00] local.INFO: NEWEST ENTRY MARKER';

        $this->writeLog($lines);

        $raw = file_get_contents($this->logPath());
        $this->assertGreaterThan(2_000_000, strlen($raw), 'Fixture must exceed the read window.');

        $entries = app(SystemLogReader::class)->read('laravel.log');
        $rendered = $entries->pluck('raw')->implode("\n");

        $this->assertStringContainsString('NEWEST ENTRY MARKER', $rendered);
        $this->assertStringNotContainsString('OLDEST ENTRY MARKER', $rendered);

        // The partial first entry produced by the seek must never be shown.
        foreach ($entries as $entry) {
            $this->assertMatchesRegularExpression(
                '/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/',
                $entry['raw'],
                'Every entry must start at a record header.'
            );
        }
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function writeLog(array $lines): void
    {
        file_put_contents($this->logPath(), implode("\n", $lines)."\n");
        touch($this->logPath(), Carbon::now()->getTimestamp());
    }
}
