<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// 1. Buat direktori /tmp yang writable untuk storage & views
$storagePath = '/tmp/storage';
$viewsPath   = $storagePath . '/framework/views';
$sessionsPath = $storagePath . '/framework/sessions';
$cachePath   = $storagePath . '/framework/cache';
$logsPath    = $storagePath . '/logs';

foreach ([$storagePath, $viewsPath, $sessionsPath, $cachePath, $logsPath] as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// 2. Set lokasi kompilasi Blade View ke /tmp
putenv('VIEW_COMPILED_PATH=' . $viewsPath);
$_ENV['VIEW_COMPILED_PATH'] = $viewsPath;

require __DIR__ . '/../vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Override path storage
$app->useStoragePath($storagePath);

// 3. Eksekusi request menggunakan metode native handleRequest
try {
    $request = Request::capture();
    $response = $app->handleRequest($request);
    $response->send();
} catch (\Throwable $e) {
    http_response_code(500);
    echo "<div style='font-family: monospace; padding: 20px; background: #fff0f0; color: #900; border: 2px solid red;'>";
    echo "<h2>Laravel Serverless Exception</h2>";
    echo "<p><b>Message:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><b>File:</b> " . htmlspecialchars($e->getFile()) . " (line " . $e->getLine() . ")</p>";
    echo "<h3>Stack Trace:</h3><pre style='background: #fff; padding: 10px; overflow: auto;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}
