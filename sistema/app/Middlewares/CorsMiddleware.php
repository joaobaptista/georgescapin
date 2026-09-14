<?php

namespace App\Middlewares;

use App\Core\Request;
use App\Core\Response;

class CorsMiddleware
{
    public function handle(Request $request): void
    {
        $httpOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $appUrl = $_ENV['APP_URL'] ?? 'http://127.0.0.1:8000';
        
        $allowedOrigins = [
            $appUrl,
            'http://127.0.0.1:8000',
            'http://localhost:8000',
        ];

        if (in_array($httpOrigin, $allowedOrigins, true)) {
            header("Access-Control-Allow-Origin: {$httpOrigin}");
            header("Access-Control-Allow-Credentials: true");
        } else {
            header("Access-Control-Allow-Origin: " . ($allowedOrigins[0] ?? '*'));
        }

        header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept");
        header("Access-Control-Max-Age: 86400");

        if ($request->getMethod() === 'OPTIONS') {
            Response::noContent();
        }
    }
}
