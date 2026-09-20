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

// 2. Alihkan lokasi manifest bootstrap cache internal Laravel ke /tmp
$_ENV['APP_SERVICES_CACHE'] = $bootstrapCachePath . '/services.php';
$_ENV['APP_PACKAGES_CACHE'] = $bootstrapCachePath . '/packages.php';
$_ENV['APP_CONFIG_CACHE']   = $bootstrapCachePath . '/config.php';
$_ENV['APP_ROUTES_CACHE']   = $bootstrapCachePath . '/routes-v7.php';

require __DIR__ . '/../vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Override path storage
$app->useStoragePath($storagePath);

// 3. Jalankan HTTP Kernel
try {
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $request = Request::capture();
    $response = $kernel->handle($request);
    $response->send();
    $kernel->terminate($request, $response);
} catch (\Throwable $e) {
    // Tangkap exception secara mentah agar tidak memicu error 'Target class [view] does not exist'
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo "<div style='font-family: sans-serif; padding: 20px; background: #fff0f0; color: #900; border: 2px solid #f00;'>";
    echo "<h2>Laravel Serverless Exception</h2>";
    echo "<p><b>Message:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><b>File:</b> " . htmlspecialchars($e->getFile()) . " (line " . $e->getLine() . ")</p>";
    echo "<h3>Trace:</h3><pre style='background: #fff; padding: 10px; overflow: auto; border: 1px solid #ccc;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}
