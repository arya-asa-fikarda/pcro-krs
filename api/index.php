<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// 1. Siapkan direktori /tmp yang writable
$storagePath = '/tmp/storage';
foreach ([$storagePath, "$storagePath/framework/views", "$storagePath/framework/sessions", "$storagePath/framework/cache", "$storagePath/logs"] as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

require __DIR__ . '/../vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

$app->useStoragePath($storagePath);

// 2. Registrasi Service Provider View & Event secara paksa agar Exception Handler tidak crash
$app->register(\Illuminate\Events\EventServiceProvider::class);
$app->register(\Illuminate\View\ViewServiceProvider::class);

// 3. Paksa Konfigurasi Serverless
config([
    'cache.default' => 'array',
    'session.driver' => 'cookie',
    'queue.default' => 'sync',
    'app.maintenance.driver' => 'file',
]);

// 4. Injeksi Database Neon
$dbUrl = getenv('DATABASE_URL') ?: ($_ENV['DATABASE_URL'] ?? ($_SERVER['DATABASE_URL'] ?? null));
if ($dbUrl) {
    $dbParts = parse_url($dbUrl);
    if (isset($dbParts['host'])) {
        config([
            'database.default' => 'pgsql',
            'database.connections.pgsql.driver' => 'pgsql',
            'database.connections.pgsql.host' => $dbParts['host'],
            'database.connections.pgsql.port' => $dbParts['port'] ?? 5432,
            'database.connections.pgsql.database' => ltrim($dbParts['path'] ?? 'neondb', '/'),
            'database.connections.pgsql.username' => $dbParts['user'] ?? '',
            'database.connections.pgsql.password' => $dbParts['pass'] ?? '',
            'database.connections.pgsql.sslmode' => 'require',
        ]);
    }
}

// 5. Eksekusi Request
try {
    $request = Request::capture();
    $response = $app->handleRequest($request);
    $response->send();
} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "=== ACTUAL LARAVEL ERROR ===\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " (Line " . $e->getLine() . ")\n\n";
    echo "Trace:\n" . $e->getTraceAsString();
}
