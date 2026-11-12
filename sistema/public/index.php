<?php

declare(strict_types=1);

use App\Http\ClientIp;
use App\Http\Kernel;
use App\Http\Request;

// Errors never reach the client; App\Http\ErrorResponder logs them.
ini_set('display_errors', '0');
ini_set('log_errors', '1');

$container = require dirname(__DIR__) . '/bootstrap/app.php';

$clientIp = $container->get(ClientIp::class);
$request = Request::fromGlobals();
$request->clientIp = $clientIp->resolve($_SERVER);
$request->https = $clientIp->isHttps($_SERVER);

$container->get(Kernel::class)
    ->handle($request, $request->https)
    ->send($request->method() !== 'HEAD');
