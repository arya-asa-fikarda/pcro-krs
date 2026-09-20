<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// 1. Buat direktori sementara di /tmp yang writable
$storagePath = '/tmp/storage';
$bootstrapCachePath = '/tmp/bootstrap/cache';

$dirs = [
    $storagePath,
    $storagePath . '/framework/views',
    $storagePath . '/framework/sessions',
    $storagePath . '/framework/cache',
    $bootstrapCachePath,
    $storagePath . '/logs',
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// 2. Set environment awal
putenv('CACHE_STORE=array');
putenv('SESSION_DRIVER=cookie');
putenv('QUEUE_CONNECTION=sync');
putenv('APP_MAINTENANCE_DRIVER=file');

$_ENV['CACHE_STORE'] = $_SERVER['CACHE_STORE'] = 'array';
$_ENV['SESSION_DRIVER'] = $_SERVER['SESSION_DRIVER'] = 'cookie';
$_ENV['QUEUE_CONNECTION'] = $_SERVER['QUEUE_CONNECTION'] = 'sync';
$_ENV['APP_MAINTENANCE_DRIVER'] = $_SERVER['APP_MAINTENANCE_DRIVER'] = 'file';

require __DIR__ . '/../vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Override path storage
$app->useStoragePath($storagePath);

// 3. Timpa konfigurasi Laravel secara paksa saat aplikasi di-boot
$app->booted(function ($app) {
    $app['config']->set('cache.default', 'array');
    $app['config']->set('session.driver', 'cookie');
    $app['config']->set('queue.default', 'sync');

    $dbUrl = getenv('DATABASE_URL') ?: ($_ENV['DATABASE_URL'] ?? ($_SERVER['DATABASE_URL'] ?? null));
    if ($dbUrl) {
        $dbParts = parse_url($dbUrl);
        if (isset($dbParts['host'])) {
            $app['config']->set('database.default', 'pgsql');
            $app['config']->set('database.connections.pgsql.driver', 'pgsql');
            $app['config']->set('database.connections.pgsql.host', $dbParts['host']);
            $app['config']->set('database.connections.pgsql.port', $dbParts['port'] ?? 5432);
            $app['config']->set('database.connections.pgsql.database', ltrim($dbParts['path'] ?? 'neondb', '/'));
            $app['config']->set('database.connections.pgsql.username', $dbParts['user'] ?? '');
            $app['config']->set('database.connections.pgsql.password', $dbParts['pass'] ?? '');
            $app['config']->set('database.connections.pgsql.sslmode', 'require');
        }
    }
});

// 4. Jalankan HTTP Request
try {
    $request = Request::capture();
    $response = $app->handleRequest($request);
    $response->send();
} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo "<div style='font-family: sans-serif; padding: 20px; background: #fff0f0; color: #900; border: 2px solid #f00;'>";
    echo "<h2>Laravel Serverless Exception</h2>";
    echo "<p><b>Message:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><b>File:</b> " . htmlspecialchars($e->getFile()) . " (line " . $e->getLine() . ")</p>";
    echo "<h3>Trace:</h3><pre style='background: #fff; padding: 10px; overflow: auto; border: 1px solid #ccc;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}
