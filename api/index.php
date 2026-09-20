<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// 1. Siapkan folder storage di /tmp agar writable
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

// 2. Jalankan HTTP Kernel (Serahkan rendering error ke Laravel Ignition)
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$request = Request::capture();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
