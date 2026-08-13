<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bump memory for heavy web requests (e.g. dompdf render 800+ rows).
// .user.ini tidak diterapkan di SAPI LiteSpeed — naikkan di entry point agar seluruh
// request web dapat memory cukup tanpa bergantung konfigurasi host.
if (ini_get('memory_limit') !== '-1') {
    $requested = '512M';
    $cur = (int) preg_replace('/[^0-9]/', '', (string) ini_get('memory_limit')) * (str_contains((string) ini_get('memory_limit'), 'G') ? 1024 : 1);
    $want = (int) preg_replace('/[^0-9]/', '', $requested);
    if ($cur < $want) {
        ini_set('memory_limit', $requested);
    }
}

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
