<?php

/**
 * ============================================================
 * FEEDTAN STORE VFD BRIDGE — SINGLE FILE (Laravel)
 * ============================================================
 *
 * File:    routes/vfd.php
 * Server:  http://127.0.0.1:8004
 * Port:    COM10
 * Baud:    9600 8N1
 * Panel:   http://127.0.0.1:8004/vfd
 *
 * Provides:
 *   - REST API under /vfd/*
 *   - Web control panel (HTML + CSS + JS)
 *   - Serial VFD driver for Windows COM10
 *   - Token authentication
 *   - CORS headers
 *   - CSRF bypass for /vfd/*
 *
 * Setup (2 steps):
 *   1. Save this file as routes/vfd.php
 *   2. Add this line to the BOTTOM of routes/web.php:
 *          require __DIR__ . '/vfd.php';
 *
 * Then run:
 *   php artisan serve --port=8004
 *
 * ============================================================
 */

use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

/* ============================================================
   CONFIGURATION — edit these values only
   ============================================================ */

if (! function_exists('vfd_config')) {
    function vfd_config(): array
    {
        $defaultBaudRate = 9600;
        $defaultPort = 'COM8';
        $defaultDataBits = 8;
        $defaultStopBits = 1;
        $defaultParity = 'none';

        // Check if baud rate is in session
        if (session()->has('vfd_baud_rate')) {
            $defaultBaudRate = session('vfd_baud_rate');
        }

        // Check if port is in session
        if (session()->has('vfd_port')) {
            $defaultPort = session('vfd_port');
        }

        // Check if data bits is in session
        if (session()->has('vfd_data_bits')) {
            $defaultDataBits = session('vfd_data_bits');
        }

        // Check if stop bits is in session
        if (session()->has('vfd_stop_bits')) {
            $defaultStopBits = session('vfd_stop_bits');
        }

        // Check if parity is in session
        if (session()->has('vfd_parity')) {
            $defaultParity = session('vfd_parity');
        }

        return [
            'port' => $defaultPort,
            'baud_rate' => $defaultBaudRate,
            'data_bits' => $defaultDataBits,
            'stop_bits' => $defaultStopBits,
            'parity' => $defaultParity,
            'columns' => 20,
            'lines' => 2,
            'clear_before_write' => true,
            'token' => 'FEEDTAN-VFD-2026',
            'store_name' => 'FEEDTAN STORE',
            'currency' => 'TZS',
            'max_body' => 10000,
            'encoding' => 'CP437',
        ];
    }
}

/* ============================================================
   CSRF BYPASS — /vfd/* uses token auth, not CSRF
   ============================================================ */

if (class_exists(VerifyCsrfToken::class)) {
    // Nothing to do here at runtime, but the exception list is
    // documented below. If you get 419 errors, add 'vfd/*' to
    // app/Http/Middleware/VerifyCsrfToken.php $except array.
}

/* ============================================================
   AUTH
   ============================================================ */

if (! function_exists('vfd_check_auth')) {
    function vfd_check_auth(Request $request, array $config): ?JsonResponse
    {
        $token = $request->header('X-VFD-TOKEN')
              ?? $request->header('x-vfd-token')
              ?? $request->bearerToken()
              ?? $request->query('token')
              ?? '';

        if (! hash_equals((string) $config['token'], (string) $token)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        return null;
    }
}

/* ============================================================
   SERIAL PORT HELPERS
   ============================================================ */

if (! function_exists('vfd_port_name')) {
    function vfd_port_name(array $config): string
    {
        $port = strtoupper(trim($config['port']));

        if (str_starts_with($port, 'COM')) {
            $number = (int) substr($port, 3);

            if ($number > 9) {
                // Windows needs this UNC form for COM10 and above
                return '\\\\.\\'.$port;
            }
        }

        return $port;
    }
}

if (! function_exists('vfd_configure_serial')) {
    function vfd_configure_serial(array $config): array
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return ['success' => false, 'message' => 'Windows only.'];
        }

        $port = strtoupper(trim($config['port']));

        if (str_starts_with($port, '\\\\.\\')) {
            $port = substr($port, 4);
        }

        $command = sprintf(
            'mode %s BAUD=%d DATA=%d STOP=%d PARITY=%s',
            escapeshellarg($port),
            (int) $config['baud_rate'],
            (int) $config['data_bits'],
            (int) $config['stop_bits'],
            strtoupper($config['parity'])
        );

        $output = [];
        $returnCode = 0;
        @exec($command.' 2>&1', $output, $returnCode);

        return [
            'success' => $returnCode === 0,
            'message' => $returnCode === 0 ? 'Serial port configured.' : 'Could not configure serial port.',
            'command' => $command,
            'output' => $output,
            'return_code' => $returnCode,
        ];
    }
}

if (! function_exists('vfd_open')) {
    function vfd_open(array $config): array
    {
        $portName = vfd_port_name($config);
        $configure = vfd_configure_serial($config);

        usleep(50000);

        $handle = @fopen($portName, 'w+b');

        if ($handle === false) {
            return [
                'handle' => false,
                'error' => 'Unable to open VFD serial port '.$config['port'].
                               ' ('.$portName.'). Check: '.
                               '(1) VFD is connected, '.
                               '(2) correct COM port in config, '.
                               '(3) no other program is using it '.
                               '(PuTTY, Arduino, POS).',
                'configure' => $configure,
            ];
        }

        stream_set_blocking($handle, false);
        @stream_set_write_buffer($handle, 0);

        if (function_exists('stream_set_timeout')) {
            @stream_set_timeout($handle, 1);
        }

        return [
            'handle' => $handle,
            'error' => null,
            'configure' => $configure,
        ];
    }
}

/* ============================================================
   SERIAL WRITE
   ============================================================ */

if (! function_exists('vfd_write')) {
    function vfd_write($handle, string $data): bool
    {
        if (! $handle) {
            return false;
        }

        if ($data === '') {
            return true;
        }

        $written = @fwrite($handle, $data);
        @fflush($handle);

        return $written !== false && $written >= strlen($data);
    }
}

if (! function_exists('vfd_init')) {
    function vfd_init($handle): bool
    {
        $ok = true;

        // Try multiple initialization sequences to get display out of segment-test mode

        // Sequence 1: Standard ESC/POS initialization
        $ok = vfd_write($handle, chr(0x1B).chr(0x40)) && $ok; // ESC @ - Initialize
        usleep(50000);

        // Sequence 2: Epson DM-D compatible
        $ok = vfd_write($handle, chr(0x1B).chr(0x3D).chr(0x01)) && $ok; // ESC = 1 - Enable
        usleep(30000);

        // Sequence 3: Clear display multiple times
        for ($i = 0; $i < 3; $i++) {
            $ok = vfd_write($handle, chr(0x0C)) && $ok; // Form Feed - Clear
            usleep(30000);
        }

        // Sequence 4: Set cursor position
        $ok = vfd_write($handle, chr(0x1B).chr(0x48).chr(0x01)) && $ok; // ESC H 1 - Home cursor
        usleep(30000);

        // Sequence 5: Turn display on
        $ok = vfd_write($handle, chr(0x1B).chr(0x5C).chr(0x01)) && $ok; // ESC \ 1 - Display on
        usleep(30000);

        return $ok;
    }
}

if (! function_exists('vfd_clear')) {
    function vfd_clear($handle): bool
    {
        // Form Feed
        return vfd_write($handle, chr(0x0C));
    }
}

if (! function_exists('vfd_newline')) {
    function vfd_newline($handle): bool
    {
        return vfd_write($handle, "\r\n");
    }
}

/* ============================================================
   TEXT FORMATTING
   ============================================================ */

if (! function_exists('vfd_clean_text')) {
    function vfd_clean_text(string $text, int $columns): string
    {
        $text = strip_tags($text);
        $text = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $text);
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
        $text = trim($text);

        // Try pure ASCII first (most VFDs expect standard ASCII)
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($converted !== false && $converted !== '') {
            $text = $converted;
        }

        return substr($text, 0, $columns);
    }
}

if (! function_exists('vfd_format_line')) {
    function vfd_format_line(string $text, array $config): string
    {
        return str_pad(
            vfd_clean_text($text, (int) $config['columns']),
            (int) $config['columns'],
            ' '
        );
    }
}

if (! function_exists('vfd_format_money')) {
    function vfd_format_money(float $amount, array $config): string
    {
        return $config['currency'].' '.number_format($amount, 0, '.', ',');
    }
}

/* ============================================================
   CORE: WRITE TWO LINES
   ============================================================ */

if (! function_exists('vfd_write_two_lines')) {
    function vfd_write_two_lines($handle, string $line1, string $line2, array $config): bool
    {
        $line1 = vfd_format_line($line1, $config);
        $line2 = vfd_format_line($line2, $config);

        if (! empty($config['clear_before_write'])) {
            vfd_clear($handle); // ignore failure; some VFDs don't support FF
            usleep(50000);
        }

        $ok = true;
        $ok = vfd_write($handle, $line1) && $ok;
        $ok = vfd_newline($handle) && $ok;
        $ok = vfd_write($handle, $line2) && $ok;

        return $ok;
    }
}

/* ============================================================
   CORE: DISPLAY MESSAGE (open → init → write → close)
   ============================================================ */

if (! function_exists('vfd_display_message')) {
    function vfd_display_message(string $line1, string $line2, array $config): array
    {
        $opened = vfd_open($config);

        if ($opened['handle'] === false) {
            return [
                'success' => false,
                'message' => $opened['error'],
                'port' => $config['port'],
                'port_unc' => vfd_port_name($config),
                'configure' => $opened['configure'],
            ];
        }

        $handle = $opened['handle'];

        $initialised = vfd_init($handle);
        usleep(50000);

        $written = vfd_write_two_lines($handle, $line1, $line2, $config);

        fclose($handle);

        if (! $initialised) {
            return [
                'success' => false,
                'message' => 'VFD initialisation failed.',
                'port' => $config['port'],
            ];
        }

        if (! $written) {
            return [
                'success' => false,
                'message' => 'Failed to write to VFD.',
                'port' => $config['port'],
            ];
        }

        return [
            'success' => true,
            'message' => 'VFD display updated successfully.',
            'line1' => vfd_clean_text($line1, (int) $config['columns']),
            'line2' => vfd_clean_text($line2, (int) $config['columns']),
            'port' => $config['port'],
        ];
    }
}

/* ============================================================
   CORS HEADERS (applied to all /vfd/* responses)
   ============================================================ */

if (! function_exists('vfd_cors')) {
    function vfd_cors(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, X-VFD-TOKEN, Authorization');
        header('Access-Control-Max-Age: 86400');
    }
}

/* ============================================================
   ROUTES
   ============================================================ */

Route::prefix('vfd')->group(function () {

    /* --------------------------------------------------------
       OPTIONS — CORS preflight
       -------------------------------------------------------- */
    Route::options('/{any?}', function () {
        vfd_cors();

        return response()->noContent();
    })->where('any', '.*');

    /* --------------------------------------------------------
       GET /vfd/status
       -------------------------------------------------------- */
    Route::get('/status', function (Request $request) {
        vfd_cors();
        $config = vfd_config();

        if ($r = vfd_check_auth($request, $config)) {
            return $r;
        }

        $portExists = false;
        $portError = null;

        if (PHP_OS_FAMILY === 'Windows') {
            $test = @fopen(vfd_port_name($config), 'r+b');

            if ($test !== false) {
                $portExists = true;
                fclose($test);
            } else {
                $last = error_get_last();
                $portError = $last['message'] ?? 'Unknown error';
            }
        }

        return response()->json([
            'success' => true,
            'status' => 'online',
            'application' => 'FeedTan Store VFD Bridge (Laravel single-file)',
            'version' => '3.0.0',
            'store' => $config['store_name'],
            'vfd_port' => $config['port'],
            'vfd_port_unc' => vfd_port_name($config),
            'baud_rate' => $config['baud_rate'],
            'data_bits' => $config['data_bits'],
            'stop_bits' => $config['stop_bits'],
            'parity' => $config['parity'],
            'columns' => $config['columns'],
            'lines' => $config['lines'],
            'serial_port_detected' => $portExists,
            'serial_port_error' => $portError,
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
            'operating_system' => PHP_OS_FAMILY,
            'endpoints' => [
                'GET  /vfd',
                'GET  /vfd/status',
                'GET  /vfd/test',
                'POST /vfd/display',
                'POST /vfd/clear',
                'POST /vfd/welcome',
                'POST /vfd/total',
                'POST /vfd/change',
                'POST /vfd/sale',
            ],
        ]);
    });

    /* --------------------------------------------------------
       GET /vfd/test
       -------------------------------------------------------- */
    Route::get('/test', function (Request $request) {
        vfd_cors();
        $config = vfd_config();

        if ($r = vfd_check_auth($request, $config)) {
            return $r;
        }

        $result = vfd_display_message('ABC123', 'XYZ789', $config);

        return response()->json($result, $result['success'] ? 200 : 500);
    });

    /* --------------------------------------------------------
       POST /vfd/display
       -------------------------------------------------------- */
    Route::post('/display', function (Request $request) {
        vfd_cors();
        $config = vfd_config();

        if ($r = vfd_check_auth($request, $config)) {
            return $r;
        }

        $line1 = trim((string) $request->input('line1', 'FEEDTAN STORE'));
        $line2 = trim((string) $request->input('line2', ''));

        $result = vfd_display_message($line1, $line2, $config);

        return response()->json($result, $result['success'] ? 200 : 500);
    });

    /* --------------------------------------------------------
       POST /vfd/clear
       -------------------------------------------------------- */
    Route::post('/clear', function (Request $request) {
        vfd_cors();
        $config = vfd_config();

        if ($r = vfd_check_auth($request, $config)) {
            return $r;
        }

        $opened = vfd_open($config);

        if ($opened['handle'] === false) {
            return response()->json([
                'success' => false,
                'message' => $opened['error'],
            ], 500);
        }

        $handle = $opened['handle'];
        vfd_init($handle);
        usleep(30000);
        $success = vfd_clear($handle);
        fclose($handle);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'VFD cleared successfully.' : 'Failed to clear VFD.',
        ], $success ? 200 : 500);
    });

    /* --------------------------------------------------------
       POST /vfd/welcome
       -------------------------------------------------------- */
    Route::post('/welcome', function (Request $request) {
        vfd_cors();
        $config = vfd_config();

        if ($r = vfd_check_auth($request, $config)) {
            return $r;
        }

        $result = vfd_display_message('FEEDTAN STORE', 'KARIBU SANA!', $config);

        return response()->json($result, $result['success'] ? 200 : 500);
    });

    /* --------------------------------------------------------
       POST /vfd/total
       -------------------------------------------------------- */
    Route::post('/total', function (Request $request) {
        vfd_cors();
        $config = vfd_config();

        if ($r = vfd_check_auth($request, $config)) {
            return $r;
        }

        $amount = (float) $request->input('amount', 0);

        $result = vfd_display_message('TOTAL', vfd_format_money($amount, $config), $config);

        return response()->json($result, $result['success'] ? 200 : 500);
    });

    /* --------------------------------------------------------
       POST /vfd/change
       -------------------------------------------------------- */
    Route::post('/change', function (Request $request) {
        vfd_cors();
        $config = vfd_config();

        if ($r = vfd_check_auth($request, $config)) {
            return $r;
        }

        $amount = (float) $request->input('amount', 0);

        $result = vfd_display_message('CHANGE', vfd_format_money($amount, $config), $config);

        return response()->json($result, $result['success'] ? 200 : 500);
    });

    /* --------------------------------------------------------
       POST /vfd/sale
       -------------------------------------------------------- */
    Route::post('/sale', function (Request $request) {
        vfd_cors();
        $config = vfd_config();

        if ($r = vfd_check_auth($request, $config)) {
            return $r;
        }

        $item = trim((string) $request->input('item', 'ITEM'));
        $quantity = (float) $request->input('quantity', 1);
        $price = (float) $request->input('price', 0);
        $total = $quantity * $price;

        $result = vfd_display_message($item, vfd_format_money($total, $config), $config);

        $result['quantity'] = $quantity;
        $result['unit_price'] = $price;
        $result['total'] = $total;

        return response()->json($result, $result['success'] ? 200 : 500);
    });

    /* --------------------------------------------------------
       POST /vfd/baud-rate
       -------------------------------------------------------- */
    Route::post('/baud-rate', function (Request $request) {
        vfd_cors();
        $config = vfd_config();

        if ($r = vfd_check_auth($request, $config)) {
            return $r;
        }

        $baudRate = (int) $request->input('baud_rate', 2400);
        $validRates = [1200, 2400, 4800, 9600, 19200, 38400, 57600, 115200];

        if (! in_array($baudRate, $validRates)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid baud rate. Valid rates: '.implode(', ', $validRates),
            ], 400);
        }

        session(['vfd_baud_rate' => $baudRate]);

        return response()->json([
            'success' => true,
            'message' => 'Baud rate set to '.$baudRate,
            'baud_rate' => $baudRate,
        ]);
    });

    /* --------------------------------------------------------
       POST /vfd/port
       -------------------------------------------------------- */
    Route::post('/port', function (Request $request) {
        vfd_cors();
        $config = vfd_config();

        if ($r = vfd_check_auth($request, $config)) {
            return $r;
        }

        $port = trim((string) $request->input('port', 'COM10'));
        $port = strtoupper($port);

        if (! preg_match('/^COM\d+$/', $port)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid port format. Use COM1, COM2, etc.',
            ], 400);
        }

        session(['vfd_port' => $port]);

        return response()->json([
            'success' => true,
            'message' => 'Port set to '.$port,
            'port' => $port,
        ]);
    });

    /* --------------------------------------------------------
       POST /vfd/settings/save
       -------------------------------------------------------- */
    Route::post('/settings/save', function (Request $request) {
        vfd_cors();
        $config = vfd_config();

        if ($r = vfd_check_auth($request, $config)) {
            return $r;
        }

        $port = trim((string) $request->input('port', 'COM10'));
        $port = strtoupper($port);
        $baudRate = (int) $request->input('baud_rate', 2400);
        $dataBits = (int) $request->input('data_bits', 8);
        $stopBits = (int) $request->input('stop_bits', 1);
        $parity = trim((string) $request->input('parity', 'none'));

        if (! preg_match('/^COM\d+$/', $port)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid port format. Use COM1, COM2, etc.',
            ], 400);
        }

        $validRates = [1200, 2400, 4800, 9600, 19200, 38400, 57600, 115200];
        if (! in_array($baudRate, $validRates)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid baud rate. Valid rates: '.implode(', ', $validRates),
            ], 400);
        }

        $validDataBits = [7, 8];
        if (! in_array($dataBits, $validDataBits)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid data bits. Valid: 7, 8',
            ], 400);
        }

        $validStopBits = [1, 2];
        if (! in_array($stopBits, $validStopBits)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid stop bits. Valid: 1, 2',
            ], 400);
        }

        $validParity = ['none', 'odd', 'even'];
        if (! in_array($parity, $validParity)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid parity. Valid: none, odd, even',
            ], 400);
        }

        // Save to session
        session([
            'vfd_port' => $port,
            'vfd_baud_rate' => $baudRate,
            'vfd_data_bits' => $dataBits,
            'vfd_stop_bits' => $stopBits,
            'vfd_parity' => $parity,
        ]);

        // Save to config file
        $configFile = base_path('config/vfd.php');
        $configContent = "<?php\n\nreturn [\n    'port' => '{$port}',\n    'baud_rate' => {$baudRate},\n    'data_bits' => {$dataBits},\n    'stop_bits' => {$stopBits},\n    'parity' => '{$parity}',\n    'columns' => 20,\n    'lines' => 2,\n    'clear_before_write' => true,\n    'token' => 'FEEDTAN-VFD-2026',\n    'store_name' => 'FEEDTAN STORE',\n    'currency' => 'TZS',\n    'max_body' => 10000,\n];\n";

        file_put_contents($configFile, $configContent);

        return response()->json([
            'success' => true,
            'message' => 'Settings saved',
            'port' => $port,
            'baud_rate' => $baudRate,
            'data_bits' => $dataBits,
            'stop_bits' => $stopBits,
            'parity' => $parity,
        ]);
    });

    /* --------------------------------------------------------
       GET /vfd  — Web Control Panel (HTML + CSS + JS)
       -------------------------------------------------------- */
    Route::get('/', function () {
        $config = vfd_config();

        return response()->view('vfd-panel', ['config' => $config]);
    });

});

/* ============================================================
   INLINE BLADE VIEW — the control panel
   ============================================================
   Registers a Blade view named "vfd-panel" from inside this
   file. No need to create resources/views/vfd-panel.blade.php.
   ============================================================ */

if (! function_exists('vfd_register_panel_view')) {

    function vfd_register_panel_view(): void
    {
        if (! app()->bound('view')) {
            return;
        }

        $view = app('view');

        if ($view->exists('vfd-panel')) {
            return;
        }

        $view->addNamespace('vfd', __DIR__);

        // Register an inline Blade string as a view
        View::addLocation(__DIR__);

        $panelFile = __DIR__.'/vfd-panel.blade.php';

        if (! file_exists($panelFile)) {
            file_put_contents($panelFile, vfd_panel_blade_source());
        }
    }

    function vfd_panel_blade_source(): string
    {
        return <<<'BLADE'
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FeedTan Store VFD Control Panel</title>
<style>
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; min-height: 100%; font-family: Arial, Helvetica, sans-serif; background: #f4f7f5; color: #17201b; }
body { padding: 25px; }
.container { max-width: 1100px; margin: 0 auto; }
.header { background: linear-gradient(135deg, #0b5d32, #148447); color: white; padding: 25px; border-radius: 18px; box-shadow: 0 10px 30px rgba(0,0,0,.12); margin-bottom: 20px; }
.header-row { display: flex; justify-content: space-between; align-items: center; gap: 20px; }
.logo-title { font-size: 26px; font-weight: 800; }
.subtitle { margin-top: 6px; opacity: .85; font-size: 14px; }
.connection { display: flex; align-items: center; gap: 8px; background: rgba(255,255,255,.12); padding: 10px 14px; border-radius: 30px; font-size: 13px; }
.dot { width: 11px; height: 11px; background: #f44336; border-radius: 50%; }
.dot.online { background: #65e572; }
.grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; }
.card { background: white; border-radius: 18px; padding: 22px; box-shadow: 0 5px 20px rgba(0,0,0,.07); }
.card h2 { margin-top: 0; font-size: 18px; }
.info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
.info { background: #f5f8f6; border-radius: 10px; padding: 12px; }
.info-label { font-size: 11px; color: #69756e; text-transform: uppercase; }
.info-value { font-size: 15px; font-weight: bold; margin-top: 4px; }
.vfd { background: #101010; color: #ffdf00; border: 7px solid #444; border-radius: 10px; padding: 15px; max-width: 500px; margin: 0 auto; font-family: "Courier New", monospace; box-shadow: inset 0 0 20px rgba(255,255,255,.05), 0 10px 30px rgba(0,0,0,.2); }
.vfd-line { height: 38px; display: flex; align-items: center; white-space: pre; overflow: hidden; font-size: 21px; letter-spacing: 1px; }
.vfd-status { text-align: center; margin-top: 12px; font-size: 13px; color: #65736a; }
button { border: 0; border-radius: 10px; padding: 12px 18px; cursor: pointer; font-size: 14px; font-weight: bold; margin: 4px; }
button:hover { opacity: .9; }
.btn-primary { background: #0b713b; color: white; }
.btn-secondary { background: #e8eee9; color: #183021; }
.btn-danger { background: #c62828; color: white; }
.button-row { display: flex; flex-wrap: wrap; gap: 5px; }
label { display: block; font-size: 13px; font-weight: bold; margin-bottom: 6px; }
input { width: 100%; padding: 12px; border: 1px solid #d5ddd7; border-radius: 9px; font-size: 14px; outline: none; }
input:focus { border-color: #16894a; }
.form-group { margin-bottom: 14px; }
.form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
.log { background: #101512; color: #b9eac7; border-radius: 12px; padding: 14px; height: 280px; overflow-y: auto; font-family: "Courier New", monospace; font-size: 12px; }
.log-line { margin-bottom: 7px; border-bottom: 1px solid rgba(255,255,255,.05); padding-bottom: 6px; }
.success { color: #7ee58e; }
.error { color: #ff8585; }
.warning { color: #ffd66b; }
.code { background: #101512; color: #d8f5df; border-radius: 10px; padding: 15px; overflow-x: auto; font-family: "Courier New", monospace; font-size: 12px; white-space: pre-wrap; }
.footer { text-align: center; margin-top: 25px; color: #6d776f; font-size: 12px; }
@media (max-width: 650px) { body { padding: 12px; } .header-row { flex-direction: column; align-items: flex-start; } .form-grid { grid-template-columns: 1fr; } .info-grid { grid-template-columns: 1fr; } .vfd-line { font-size: 17px; } }
</style>
</head>
<body>
<div class="container">

    <div class="header">
        <div class="header-row">
            <div>
                <div class="logo-title">FEEDTAN STORE</div>
                <div class="subtitle">Customer VFD Display Control Panel</div>
            </div>
            <div class="connection">
                <span id="statusDot" class="dot"></span>
                <span id="statusText">Checking...</span>
            </div>
        </div>
    </div>

    <div class="grid">

        <div class="card">
            <h2>Connection Information</h2>
            <div class="info-grid">
                <div class="info">
                    <div class="info-label">VFD Endpoint</div>
                    <div class="info-value" id="endpointText">{{ $config['store_name'] }}</div>
                </div>
                <div class="info">
                    <div class="info-label">Serial Port</div>
                    <div class="info-value">{{ $config['port'] }}</div>
                </div>
                <div class="info">
                    <div class="info-label">Baud Rate</div>
                    <div class="info-value">{{ $config['baud_rate'] }}</div>
                </div>
                <div class="info">
                    <div class="info-label">Display</div>
                    <div class="info-value">{{ $config['columns'] }} × {{ $config['lines'] }}</div>
                </div>
            </div>
            <div class="form-grid" style="margin-top:15px">
                <div class="form-group">
                    <label>Serial Port</label>
                    <select id="portSelect" onchange="changePort()">
                        <option value="COM1" {{ $config['port'] == 'COM1' ? 'selected' : '' }}>COM1</option>
                        <option value="COM2" {{ $config['port'] == 'COM2' ? 'selected' : '' }}>COM2</option>
                        <option value="COM3" {{ $config['port'] == 'COM3' ? 'selected' : '' }}>COM3</option>
                        <option value="COM4" {{ $config['port'] == 'COM4' ? 'selected' : '' }}>COM4</option>
                        <option value="COM5" {{ $config['port'] == 'COM5' ? 'selected' : '' }}>COM5</option>
                        <option value="COM6" {{ $config['port'] == 'COM6' ? 'selected' : '' }}>COM6</option>
                        <option value="COM7" {{ $config['port'] == 'COM7' ? 'selected' : '' }}>COM7</option>
                        <option value="COM8" {{ $config['port'] == 'COM8' ? 'selected' : '' }}>COM8</option>
                        <option value="COM9" {{ $config['port'] == 'COM9' ? 'selected' : '' }}>COM9</option>
                        <option value="COM10" {{ $config['port'] == 'COM10' ? 'selected' : '' }}>COM10</option>
                        <option value="COM11" {{ $config['port'] == 'COM11' ? 'selected' : '' }}>COM11</option>
                        <option value="COM12" {{ $config['port'] == 'COM12' ? 'selected' : '' }}>COM12</option>
                        <option value="COM13" {{ $config['port'] == 'COM13' ? 'selected' : '' }}>COM13</option>
                        <option value="COM14" {{ $config['port'] == 'COM14' ? 'selected' : '' }}>COM14</option>
                        <option value="COM15" {{ $config['port'] == 'COM15' ? 'selected' : '' }}>COM15</option>
                        <option value="COM16" {{ $config['port'] == 'COM16' ? 'selected' : '' }}>COM16</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Baud Rate</label>
                    <select id="baudRateSelect" onchange="changeBaudRate()">
                        <option value="1200" {{ $config['baud_rate'] == 1200 ? 'selected' : '' }}>1200</option>
                        <option value="2400" {{ $config['baud_rate'] == 2400 ? 'selected' : '' }}>2400</option>
                        <option value="4800" {{ $config['baud_rate'] == 4800 ? 'selected' : '' }}>4800</option>
                        <option value="9600" {{ $config['baud_rate'] == 9600 ? 'selected' : '' }}>9600</option>
                        <option value="19200" {{ $config['baud_rate'] == 19200 ? 'selected' : '' }}>19200</option>
                        <option value="38400" {{ $config['baud_rate'] == 38400 ? 'selected' : '' }}>38400</option>
                        <option value="57600" {{ $config['baud_rate'] == 57600 ? 'selected' : '' }}>57600</option>
                        <option value="115200" {{ $config['baud_rate'] == 115200 ? 'selected' : '' }}>115200</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Data Bits</label>
                    <select id="dataBitsSelect">
                        <option value="7" {{ $config['data_bits'] == 7 ? 'selected' : '' }}>7</option>
                        <option value="8" {{ $config['data_bits'] == 8 ? 'selected' : '' }}>8</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Stop Bits</label>
                    <select id="stopBitsSelect">
                        <option value="1" {{ $config['stop_bits'] == 1 ? 'selected' : '' }}>1</option>
                        <option value="2" {{ $config['stop_bits'] == 2 ? 'selected' : '' }}>2</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Parity</label>
                    <select id="paritySelect">
                        <option value="none" {{ $config['parity'] == 'none' ? 'selected' : '' }}>None</option>
                        <option value="odd" {{ $config['parity'] == 'odd' ? 'selected' : '' }}>Odd</option>
                        <option value="even" {{ $config['parity'] == 'even' ? 'selected' : '' }}>Even</option>
                    </select>
                </div>
            </div>
            <div class="button-row" style="margin-top:15px">
                <button class="btn-primary" onclick="checkStatus()">Check Status</button>
                <button class="btn-secondary" onclick="testDisplay()">Test Display</button>
                <button class="btn-danger" onclick="clearDisplay()">Clear</button>
                <button class="btn-primary" onclick="saveSettings()">Save Settings</button>
                <button class="btn-secondary" onclick="autoTestBaudRates()">Auto Test Baud</button>
                <button class="btn-secondary" onclick="autoTestPorts()">Auto Test Ports</button>
            </div>
        </div>

        <div class="card">
            <h2>VFD Preview</h2>
            <div class="vfd">
                <div id="previewLine1" class="vfd-line">FEEDTAN STORE</div>
                <div id="previewLine2" class="vfd-line">KARIBU SANA!</div>
            </div>
            <div id="vfdStatus" class="vfd-status">Ready</div>
        </div>

        <div class="card">
            <h2>Custom Display</h2>
            <div class="form-group">
                <label>Line 1</label>
                <input id="line1" maxlength="20" value="FEEDTAN STORE">
            </div>
            <div class="form-group">
                <label>Line 2</label>
                <input id="line2" maxlength="20" value="KARIBU SANA!">
            </div>
            <button class="btn-primary" onclick="sendDisplay()">Send to VFD</button>
        </div>

        <div class="card">
            <h2>Quick Actions</h2>
            <div class="button-row">
                <button class="btn-primary" onclick="welcome()">Welcome</button>
                <button class="btn-secondary" onclick="showTotalPrompt()">Total</button>
                <button class="btn-secondary" onclick="showChangePrompt()">Change</button>
            </div>
        </div>

        <div class="card">
            <h2>Display Total</h2>
            <div class="form-group">
                <label>Total Amount</label>
                <input id="totalAmount" type="number" min="0" step="1" placeholder="e.g. 12500">
            </div>
            <button class="btn-primary" onclick="sendTotal()">Display Total</button>
        </div>

        <div class="card">
            <h2>Display Change</h2>
            <div class="form-group">
                <label>Change Amount</label>
                <input id="changeAmount" type="number" min="0" step="1" placeholder="e.g. 2500">
            </div>
            <button class="btn-primary" onclick="sendChange()">Display Change</button>
        </div>

        <div class="card">
            <h2>Display Sale Item</h2>
            <div class="form-group">
                <label>Item Name</label>
                <input id="saleItem" maxlength="20" placeholder="e.g. SUGAR 1KG">
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Quantity</label>
                    <input id="saleQuantity" type="number" min="0.01" step="0.01" value="1">
                </div>
                <div class="form-group">
                    <label>Unit Price</label>
                    <input id="salePrice" type="number" min="0" step="1" placeholder="3500">
                </div>
            </div>
            <button class="btn-primary" onclick="sendSale()">Display Sale</button>
        </div>

        <div class="card">
            <h2>Activity Log</h2>
            <div id="activityLog" class="log"></div>
        </div>

        <div class="card" style="grid-column: 1 / -1">
            <h2>POS JavaScript Integration</h2>
            <p style="font-size:13px">
                Call these from any Blade view in your Laravel POS.
                Same origin, same port, same token.
            </p>
            <div class="code">const VFD_TOKEN = '{{ $config['token'] }}';

async function updateVFD(line1, line2) {
    const res = await fetch('/vfd/display', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-VFD-TOKEN': VFD_TOKEN
        },
        body: JSON.stringify({ line1, line2 })
    });
    return res.json();
}

// After a sale:
updateVFD('SUGAR 1KG', 'TZS 3,500');

// Show total:
updateVFD('TOTAL', 'TZS 12,500');

// Show change:
updateVFD('CHANGE', 'TZS 2,500');</div>
        </div>

    </div>

    <div class="footer">FEEDTAN STORE — LET'S GROW TOGETHER</div>
</div>

<div id="autoTestModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:white; padding:30px; border-radius:15px; max-width:400px; width:90%; text-align:center;">
        <h3 style="margin-top:0; color:#0b5d32;">Auto Testing Baud Rates</h3>
        <div id="modalProgress" style="font-size:18px; font-weight:bold; margin:20px 0; color:#333;">Starting...</div>
        <div id="modalStatus" style="font-size:14px; color:#666; margin-bottom:20px;">Please wait...</div>
        <div style="background:#f0f0f0; border-radius:10px; height:10px; overflow:hidden; margin-bottom:20px;">
            <div id="modalProgressBar" style="background:#0b5d32; height:100%; width:0%; transition:width 0.3s;"></div>
        </div>
        <button onclick="closeModal()" style="background:#c62828; color:white; padding:10px 20px; border:none; border-radius:8px; cursor:pointer; font-size:14px; font-weight:bold;">Cancel</button>
    </div>
</div>

<script>
const VFD_TOKEN = '{{ $config['token'] }}';

const activityLog = document.getElementById('activityLog');
const statusDot   = document.getElementById('statusDot');
const statusText  = document.getElementById('statusText');
const vfdStatus   = document.getElementById('vfdStatus');
const endpointText = document.getElementById('endpointText');

endpointText.textContent = window.location.origin + '/vfd';

function logMessage(message, type = '') {
    const now = new Date().toLocaleTimeString();
    const div = document.createElement('div');
    div.className = 'log-line ' + type;
    div.textContent = '[' + now + '] ' + message;
    activityLog.appendChild(div);
    activityLog.scrollTop = activityLog.scrollHeight;
}

function updatePreview(line1, line2) {
    document.getElementById('previewLine1').textContent = padPreview(line1);
    document.getElementById('previewLine2').textContent = padPreview(line2);
}

function padPreview(text) {
    text = String(text || '').substring(0, 20);
    return text.padEnd(20, ' ');
}

async function changeBaudRate() {
    const baudRate = document.getElementById('baudRateSelect').value;
    try {
        const data = await vfdRequest('/baud-rate', {
            method: 'POST',
            body: JSON.stringify({ baud_rate: parseInt(baudRate) })
        });
        if (!data.success) throw new Error(data.message || 'Baud rate change failed.');
        logMessage('Baud rate changed to ' + baudRate, 'success');
        setTimeout(checkStatus, 500);
    } catch (error) {
        logMessage('Baud rate error: ' + error.message, 'error');
    }
}

async function changePort() {
    const port = document.getElementById('portSelect').value;
    try {
        const data = await vfdRequest('/port', {
            method: 'POST',
            body: JSON.stringify({ port: port })
        });
        if (!data.success) throw new Error(data.message || 'Port change failed.');
        logMessage('Port changed to ' + port, 'success');
        setTimeout(checkStatus, 500);
    } catch (error) {
        logMessage('Port error: ' + error.message, 'error');
    }
}

async function saveSettings() {
    const port = document.getElementById('portSelect').value;
    const baudRate = document.getElementById('baudRateSelect').value;
    const dataBits = document.getElementById('dataBitsSelect').value;
    const stopBits = document.getElementById('stopBitsSelect').value;
    const parity = document.getElementById('paritySelect').value;
    try {
        const data = await vfdRequest('/settings/save', {
            method: 'POST',
            body: JSON.stringify({
                port: port,
                baud_rate: parseInt(baudRate),
                data_bits: parseInt(dataBits),
                stop_bits: parseInt(stopBits),
                parity: parity
            })
        });
        if (!data.success) throw new Error(data.message || 'Settings save failed.');
        logMessage('Settings saved: ' + dataBits + dataBits + stopBits + parity, 'success');
        setTimeout(checkStatus, 500);
    } catch (error) {
        logMessage('Settings save error: ' + error.message, 'error');
    }
}

async function autoTestBaudRates() {
    const baudRates = [1200, 2400, 4800, 9600, 19200, 38400, 57600, 115200];
    const delay = 3000; // 3 seconds between each test

    // Show modal
    document.getElementById('autoTestModal').style.display = 'flex';

    logMessage('Starting auto-test of all baud rates...', 'warning');

    // Clear display first
    try {
        await vfdRequest('/clear', { method: 'POST', body: '{}' });
        logMessage('Display cleared', 'success');
    } catch (error) {
        logMessage('Clear error: ' + error.message, 'error');
    }

    await new Promise(resolve => setTimeout(resolve, 1000));

    // Test each baud rate
    for (let i = 0; i < baudRates.length; i++) {
        const baudRate = baudRates[i];
        const progress = Math.round(((i + 1) / baudRates.length) * 100);

        // Update modal
        document.getElementById('modalProgress').textContent = `Testing ${baudRate} baud (${i + 1}/${baudRates.length})`;
        document.getElementById('modalStatus').textContent = 'Check VFD display for readable text';
        document.getElementById('modalProgressBar').style.width = progress + '%';

        logMessage(`Testing baud rate ${baudRate} (${i + 1}/${baudRates.length})...`, 'warning');

        // Set baud rate
        try {
            const data = await vfdRequest('/baud-rate', {
                method: 'POST',
                body: JSON.stringify({ baud_rate: baudRate })
            });
            if (!data.success) throw new Error(data.message || 'Baud rate change failed.');
            document.getElementById('baudRateSelect').value = baudRate;
        } catch (error) {
            logMessage('Baud rate error: ' + error.message, 'error');
            continue;
        }

        await new Promise(resolve => setTimeout(resolve, 500));

        // Test display
        try {
            const data = await vfdRequest('/test?token=' + encodeURIComponent(VFD_TOKEN));
            if (!data.success) throw new Error(data.message || 'Test failed.');
            updatePreview('ABC123', 'XYZ789');
            logMessage(`Test sent at ${baudRate} baud - check VFD display`, 'success');
        } catch (error) {
            logMessage('Test error: ' + error.message, 'error');
        }

        // Wait before next test
        if (i < baudRates.length - 1) {
            await new Promise(resolve => setTimeout(resolve, delay));
        }
    }

    // Update modal for completion
    document.getElementById('modalProgress').textContent = 'Testing Complete!';
    document.getElementById('modalStatus').textContent = 'Check which baud rate showed readable text';
    document.getElementById('modalProgressBar').style.width = '100%';

    logMessage('Auto-test complete. Check which baud rate showed readable text.', 'success');

    // Auto-close modal after 5 seconds
    setTimeout(() => {
        closeModal();
    }, 5000);
}

function closeModal() {
    document.getElementById('autoTestModal').style.display = 'none';
}

async function autoTestPorts() {
    const ports = ['COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6', 'COM7', 'COM8', 'COM9', 'COM10', 'COM11', 'COM12', 'COM13', 'COM14', 'COM15', 'COM16'];
    const delay = 3000; // 3 seconds between each test

    // Show modal
    document.getElementById('autoTestModal').style.display = 'flex';

    logMessage('Starting auto-test of all COM ports...', 'warning');

    // Test each port
    for (let i = 0; i < ports.length; i++) {
        const port = ports[i];
        const progress = Math.round(((i + 1) / ports.length) * 100);

        // Update modal
        document.getElementById('modalProgress').textContent = `Testing ${port} (${i + 1}/${ports.length})`;
        document.getElementById('modalStatus').textContent = 'Check VFD display for text';
        document.getElementById('modalProgressBar').style.width = progress + '%';

        logMessage(`Testing port ${port} (${i + 1}/${ports.length})...`, 'warning');

        // Set port
        try {
            const data = await vfdRequest('/port', {
                method: 'POST',
                body: JSON.stringify({ port: port })
            });
            if (!data.success) throw new Error(data.message || 'Port change failed.');
            document.getElementById('portSelect').value = port;
        } catch (error) {
            logMessage('Port error: ' + error.message, 'error');
            continue;
        }

        await new Promise(resolve => setTimeout(resolve, 500));

        // Test display
        try {
            const data = await vfdRequest('/test?token=' + encodeURIComponent(VFD_TOKEN));
            if (!data.success) throw new Error(data.message || 'Test failed.');
            updatePreview('ABC123', 'XYZ789');
            logMessage(`Test sent to ${port} - check VFD display`, 'success');
        } catch (error) {
            logMessage('Test error: ' + error.message, 'error');
        }

        // Wait before next test
        if (i < ports.length - 1) {
            await new Promise(resolve => setTimeout(resolve, delay));
        }
    }

    // Update modal for completion
    document.getElementById('modalProgress').textContent = 'Testing Complete!';
    document.getElementById('modalStatus').textContent = 'Check which COM port showed text';
    document.getElementById('modalProgressBar').style.width = '100%';

    logMessage('Auto-test complete. Check which COM port showed text.', 'success');

    // Auto-close modal after 5 seconds
    setTimeout(() => {
        closeModal();
    }, 5000);
}

async function vfdRequest(path, options = {}) {
    const url = window.location.origin + '/vfd' + path;

    const requestOptions = {
        ...options,
        headers: {
            'Content-Type': 'application/json',
            'X-VFD-TOKEN': VFD_TOKEN,
            ...(options.headers || {})
        }
    };

    logMessage((requestOptions.method || 'GET') + ' /vfd' + path);

    let response;
    try {
        response = await fetch(url, requestOptions);
    } catch (error) {
        throw new Error('Cannot reach /vfd bridge. Is php artisan serve running?');
    }

    const responseText = await response.text();

    let data;
    try {
        data = JSON.parse(responseText);
    } catch (error) {
        throw new Error(
            'VFD bridge returned non-JSON. HTTP ' + response.status +
            '. Response starts with: ' + responseText.substring(0, 200)
        );
    }

    if (!response.ok) {
        throw new Error(data.message || ('HTTP ' + response.status));
    }

    return data;
}

async function checkStatus() {
    try {
        const data = await vfdRequest('/status?token=' + encodeURIComponent(VFD_TOKEN));

        if (data.success) {
            statusDot.classList.add('online');

            if (data.serial_port_detected) {
                statusText.textContent = 'Connected (' + data.vfd_port + ')';
                vfdStatus.textContent  = 'Bridge online — port ' + data.vfd_port + ' open';
                logMessage('VFD bridge connected. Port: ' + data.vfd_port + ' (' + data.vfd_port_unc + ')', 'success');
            } else {
                statusText.textContent = 'Port ' + data.vfd_port + ' not open';
                vfdStatus.textContent  = 'Port ' + data.vfd_port + ' could not be opened';
                logMessage('Bridge online but port ' + data.vfd_port + ' could not be opened.', 'error');
                if (data.serial_port_error) {
                    logMessage('Windows error: ' + data.serial_port_error, 'error');
                }
            }
        } else {
            throw new Error(data.message || 'Status failed.');
        }
    } catch (error) {
        statusDot.classList.remove('online');
        statusText.textContent = 'Disconnected';
        vfdStatus.textContent  = 'Connection error';
        logMessage('Connection error: ' + error.message, 'error');
    }
}

async function testDisplay() {
    try {
        const data = await vfdRequest('/test?token=' + encodeURIComponent(VFD_TOKEN));
        if (!data.success) throw new Error(data.message || 'Test failed.');
        updatePreview('FEEDTAN STORE', 'VFD TEST OK');
        vfdStatus.textContent = 'Test successful';
        logMessage('VFD test successful.', 'success');
    } catch (error) {
        vfdStatus.textContent = 'Test failed';
        logMessage('Test error: ' + error.message, 'error');
    }
}

async function clearDisplay() {
    try {
        const data = await vfdRequest('/clear', { method: 'POST', body: '{}' });
        if (!data.success) throw new Error(data.message || 'Clear failed.');
        updatePreview('', '');
        vfdStatus.textContent = 'Display cleared';
        logMessage('VFD cleared successfully.', 'success');
    } catch (error) {
        logMessage('Clear error: ' + error.message, 'error');
    }
}

async function welcome() {
    try {
        const data = await vfdRequest('/welcome', { method: 'POST', body: '{}' });
        if (!data.success) throw new Error(data.message || 'Welcome failed.');
        updatePreview('FEEDTAN STORE', 'KARIBU SANA!');
        vfdStatus.textContent = 'Welcome displayed';
        logMessage('Welcome message displayed.', 'success');
    } catch (error) {
        logMessage('Welcome error: ' + error.message, 'error');
    }
}

async function sendDisplay() {
    const line1 = document.getElementById('line1').value;
    const line2 = document.getElementById('line2').value;
    try {
        const data = await vfdRequest('/display', {
            method: 'POST',
            body: JSON.stringify({ line1, line2 })
        });
        if (!data.success) throw new Error(data.message || 'Display failed.');
        updatePreview(line1, line2);
        vfdStatus.textContent = 'Display updated';
        logMessage('Display updated: ' + line1 + ' | ' + line2, 'success');
    } catch (error) {
        logMessage('Display error: ' + error.message, 'error');
    }
}

async function sendTotal() {
    const amount = Number(document.getElementById('totalAmount').value);
    if (isNaN(amount)) { alert('Please enter a valid amount.'); return; }
    try {
        const data = await vfdRequest('/total', {
            method: 'POST',
            body: JSON.stringify({ amount })
        });
        if (!data.success) throw new Error(data.message || 'Total failed.');
        const formatted = 'TZS ' + amount.toLocaleString('en-US', { maximumFractionDigits: 0 });
        updatePreview('TOTAL', formatted);
        vfdStatus.textContent = 'Total displayed';
        logMessage('Total displayed: ' + formatted, 'success');
    } catch (error) {
        logMessage('Total error: ' + error.message, 'error');
    }
}

async function sendChange() {
    const amount = Number(document.getElementById('changeAmount').value);
    if (isNaN(amount)) { alert('Please enter a valid amount.'); return; }
    try {
        const data = await vfdRequest('/change', {
            method: 'POST',
            body: JSON.stringify({ amount })
        });
        if (!data.success) throw new Error(data.message || 'Change failed.');
        const formatted = 'TZS ' + amount.toLocaleString('en-US', { maximumFractionDigits: 0 });
        updatePreview('CHANGE', formatted);
        vfdStatus.textContent = 'Change displayed';
        logMessage('Change displayed: ' + formatted, 'success');
    } catch (error) {
        logMessage('Change error: ' + error.message, 'error');
    }
}

async function sendSale() {
    const item     = document.getElementById('saleItem').value;
    const quantity = Number(document.getElementById('saleQuantity').value);
    const price    = Number(document.getElementById('salePrice').value);

    if (!item) { alert('Please enter item name.'); return; }
    if (isNaN(quantity) || quantity <= 0) { alert('Please enter valid quantity.'); return; }
    if (isNaN(price) || price < 0) { alert('Please enter valid price.'); return; }

    try {
        const data = await vfdRequest('/sale', {
            method: 'POST',
            body: JSON.stringify({ item, quantity, price })
        });
        if (!data.success) throw new Error(data.message || 'Sale display failed.');
        const total = quantity * price;
        const formatted = 'TZS ' + total.toLocaleString('en-US', { maximumFractionDigits: 0 });
        updatePreview(item, formatted);
        vfdStatus.textContent = 'Sale displayed';
        logMessage('Sale: ' + item + ' × ' + quantity + ' = ' + formatted, 'success');
    } catch (error) {
        logMessage('Sale error: ' + error.message, 'error');
    }
}

function showTotalPrompt() {
    const amount = prompt('Enter total amount:');
    if (amount !== null && amount !== '') {
        document.getElementById('totalAmount').value = amount;
        sendTotal();
    }
}

function showChangePrompt() {
    const amount = prompt('Enter change amount:');
    if (amount !== null && amount !== '') {
        document.getElementById('changeAmount').value = amount;
        sendChange();
    }
}

logMessage('FeedTan Store VFD Control Panel loaded.', 'success');
logMessage('Endpoint: ' + window.location.origin + '/vfd');
logMessage('Configured port: {{ $config['port'] }}');
logMessage('Baud rate: {{ $config['baud_rate'] }}');
logMessage('Display: {{ $config['columns'] }} × {{ $config['lines'] }}');

setTimeout(checkStatus, 300);
</script>

</body>
</html>
BLADE;
    }
}

/* ============================================================
   BOOTSTRAP: register the inline view on first request
   ============================================================ */

vfd_register_panel_view();
