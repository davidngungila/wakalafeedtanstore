<?php

namespace Tests\Feature;

use App\Http\Controllers\SmsController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class SmsStreamTest extends TestCase
{
    use RefreshDatabase;

    /**
     * SmsController::stream() was declared as returning StreamedResponse with no
     * import, so the type resolved to the non-existent
     * App\Http\Controllers\StreamedResponse and every request to /sms/stream
     * died with a TypeError before a byte was streamed.
     */
    public function test_stream_return_type_resolves_to_a_real_class(): void
    {
        $type = (new ReflectionMethod(SmsController::class, 'stream'))->getReturnType();

        $this->assertNotNull($type);
        $this->assertTrue($type->isBuiltin() === false, 'Return type must be a class.');
        $this->assertTrue(class_exists($type->getName()), 'Return type must be a class that exists.');
        $this->assertSame(
            StreamedResponse::class,
            $type->getName(),
            'stream() must return Symfony\Component\HttpFoundation\StreamedResponse.'
        );
    }

    /**
     * Guards the whole bug class: any controller return type that names a
     * Symfony/Illuminate HTTP class must be imported, otherwise PHP silently
     * resolves it against the file's own namespace.
     */
    public function test_no_controller_returns_an_unimported_http_class(): void
    {
        $httpClasses = [
            'StreamedResponse', 'BinaryFileResponse', 'JsonResponse', 'RedirectResponse',
            'StreamedJsonResponse', 'Response', 'Request', 'View',
        ];

        $offenders = [];

        foreach ($this->controllerFiles() as $file) {
            $code = file_get_contents($file);
            $namespace = $this->namespaceOf($code);

            preg_match_all('/^use\s+([^;]+);/m', $code, $uses);
            $aliases = [];
            foreach ($uses[1] as $import) {
                $import = trim($import);
                $aliases[str_contains($import, ' as ')
                    ? trim(substr($import, strpos($import, ' as ') + 4))
                    : substr($import, (int) strrpos($import, '\\') + 1)] = true;
            }

            preg_match_all('/function\s+\w+\s*\([^)]*\)\s*:\s*([\\\\\w]+)/m', $code, $returns);
            foreach ($returns[1] as $type) {
                $type = ltrim($type, '\\');
                if (str_contains($type, '\\')) {
                    continue;
                }
                if (! in_array($type, $httpClasses, true) || isset($aliases[$type])) {
                    continue;
                }

                $offenders[] = basename($file).' -> '.$type.' (namespace '.$namespace.')';
            }
        }

        $this->assertSame([], $offenders, 'Unimported HTTP return types resolve to the wrong namespace.');
    }

    /**
     * @return array<int, string>
     */
    private function controllerFiles(): array
    {
        $files = glob(app_path('Http/Controllers/*.php')) ?: [];

        return array_values(array_filter($files, 'is_file'));
    }

    private function namespaceOf(string $code): string
    {
        return preg_match('/^namespace\s+([^;]+);/m', $code, $m) ? trim($m[1]) : '(global)';
    }
}
