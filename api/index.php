<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// 1. Injeksi Environment Variables Serverless SEBELUM Laravel dimuat
putenv('CACHE_STORE=array');
putenv('SESSION_DRIVER=cookie');
putenv('QUEUE_CONNECTION=sync');
putenv('APP_MAINTENANCE_DRIVER=file');

$_ENV['CACHE_STORE'] = $_SERVER['CACHE_STORE'] = 'array';
$_ENV['SESSION_DRIVER'] = $_SERVER['SESSION_DRIVER'] = 'cookie';
$_ENV['QUEUE_CONNECTION'] = $_SERVER['QUEUE_CONNECTION'] = 'sync';
$_ENV['APP_MAINTENANCE_DRIVER'] = $_SERVER['APP_MAINTENANCE_DRIVER'] = 'file';

// 2. Parse DATABASE_URL dari Neon jika tersedia
$dbUrl = getenv('DATABASE_URL') ?: ($_ENV['DATABASE_URL'] ?? ($_SERVER['DATABASE_URL'] ?? null));
if ($dbUrl) {
    $dbParts = parse_url($dbUrl);
    if (isset($dbParts['host'])) {
        $host = $dbParts['host'];
        $port = $dbParts['port'] ?? 5432;
        $db   = ltrim($dbParts['path'] ?? 'neondb', '/');
        $user = $dbParts['user'] ?? '';
        $pass = $dbParts['pass'] ?? '';

        putenv("DB_CONNECTION=pgsql");
        putenv("DB_HOST={$host}");
        putenv("DB_PORT={$port}");
        putenv("DB_DATABASE={$db}");
        putenv("DB_USERNAME={$user}");
        putenv("DB_PASSWORD={$pass}");

        $_ENV['DB_CONNECTION'] = $_SERVER['DB_CONNECTION'] = 'pgsql';
        $_ENV['DB_HOST']       = $_SERVER['DB_HOST']       = $host;
        $_ENV['DB_PORT']       = $_SERVER['DB_PORT']       = $port;
        $_ENV['DB_DATABASE']   = $_SERVER['DB_DATABASE']   = $db;
        $_ENV['DB_USERNAME']   = $_SERVER['DB_USERNAME']   = $user;
        $_ENV['DB_PASSWORD']   = $_SERVER['DB_PASSWORD']   = $pass;
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

// 4. Jalankan HTTP Kernel
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
