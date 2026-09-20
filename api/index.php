<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Arahkan storage ke /tmp milik Vercel Serverless
$storagePath = '/tmp/storage';
if (!is_dir($storagePath)) {
    mkdir($storagePath . '/framework/views', 0755, true);
    mkdir($storagePath . '/framework/sessions', 0755, true);
    mkdir('/tmp/storage/framework/cache', 0755, true);
    mkdir('/tmp/storage/bootstrap/cache', 0755, true);
    mkdir('/tmp/storage/logs', 0755, true);
}

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

// Set path storage & bootstrap cache ke /tmp
$app->useStoragePath($storagePath);

// Inisialisasi Kernel HTTP khas Laravel 11/12/13
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
