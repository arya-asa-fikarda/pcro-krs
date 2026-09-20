<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// 1. Paksa driver serverless agar tidak membutuhkan tabel 'cache' atau 'sessions' di DB
$_ENV['CACHE_STORE'] = 'array';
$_ENV['SESSION_DRIVER'] = 'cookie';
$_ENV['QUEUE_CONNECTION'] = 'sync';

// 2. Parse DATABASE_URL secara otomatis ke variabel DB individu
$dbUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');
if ($dbUrl) {
    $dbParts = parse_url($dbUrl);
    if (isset($dbParts['host'])) {
        $_ENV['DB_CONNECTION'] = 'pgsql';
        $_ENV['DB_HOST'] = $dbParts['host'];
        $_ENV['DB_PORT'] = $dbParts['port'] ?? 5432;
        $_ENV['DB_DATABASE'] = ltrim($dbParts['path'] ?? '', '/');
        $_ENV['DB_USERNAME'] = $dbParts['user'] ?? '';
        $_ENV['DB_PASSWORD'] = $dbParts['pass'] ?? '';

        putenv("DB_CONNECTION=pgsql");
        putenv("DB_HOST={$dbParts['host']}");
        putenv("DB_PORT=" . ($dbParts['port'] ?? 5432));
        putenv("DB_DATABASE=" . ltrim($dbParts['path'] ?? '', '/'));
        putenv("DB_USERNAME=" . ($dbParts['user'] ?? ''));
        putenv("DB_PASSWORD=" . ($dbParts['pass'] ?? ''));
    }
}

// 3. Buat direktori /tmp untuk storage
$storagePath = '/tmp/storage';
if (!is_dir($storagePath)) {
    @mkdir($storagePath . '/framework/views', 0755, true);
    @mkdir($storagePath . '/framework/sessions', 0755, true);
    @mkdir($storagePath . '/framework/cache', 0755, true);
    @mkdir($storagePath . '/logs', 0755, true);
}

require __DIR__ . '/../vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

$app->useStoragePath($storagePath);

try {
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    $request = Request::capture();
    $response = $kernel->handle($request);
    $response->send();
    $kernel->terminate($request, $response);
} catch (\Throwable $e) {
    http_response_code(500);
    echo "<div style='font-family: monospace; padding: 20px; background: #fff0f0; color: #900; border: 2px solid red;'>";
    echo "<h2>Laravel Serverless Exception</h2>";
    echo "<p><b>Message:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><b>File:</b> " . htmlspecialchars($e->getFile()) . " (line " . $e->getLine() . ")</p>";
    echo "<h3>Stack Trace:</h3><pre style='background: #fff; padding: 10px; overflow: auto;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}
