<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Siapkan folder storage di /tmp agar writable
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
