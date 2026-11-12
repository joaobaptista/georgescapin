<?php

declare(strict_types=1);

namespace App\Http;

use App\Infrastructure\Config;

/** Front controller: API to the router, every other GET to the static app shell. */
final class Kernel
{
    /** Same policy as public/.htaccess. Scripts only from this origin; no inline code, no CDN. */
    private const CSP = "default-src 'self'; base-uri 'self'; connect-src 'self'; font-src 'self' data: https://fonts.gstatic.com https://unpkg.com; form-action 'self'; frame-ancestors 'self'; img-src 'self' data:; object-src 'none'; script-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://unpkg.com";

    public function __construct(
        private readonly Router $router,
        private readonly ErrorResponder $errors,
        private readonly Config $config,
        private readonly string $shellPath,
    ) {
    }

    public function handle(Request $request, bool $https): Response
    {
        try {
            $response = $request->isApi() ? $this->api($request) : $this->page($request);
        } catch (\Throwable $error) {
            $response = $this->errors->render($error);
        }
        if ($request->isApi()) {
            $response = $this->withCors($request, $response);
        }

        return $this->withSecurityHeaders($response, $https);
    }

    private function api(Request $request): Response
    {
        return $request->method() === 'OPTIONS' ? Response::noContent() : $this->router->dispatch($request);
    }

    private function page(Request $request): Response
    {
        if (!in_array($request->method(), ['GET', 'HEAD'], true)) {
            return (new Response(405))->withHeader('Allow', 'GET, HEAD');
        }

        return Response::html((string) file_get_contents($this->shellPath));
    }

    private function withCors(Request $request, Response $response): Response
    {
        $allowed = [$this->config->appUrl()];
        if (!$this->config->isProduction()) {
            $allowed = [...$allowed, 'http://127.0.0.1:8000', 'http://localhost:8000'];
        }
        $origin = (string) $request->header('origin');

        $response = in_array($origin, $allowed, true)
            ? $response->withHeader('Access-Control-Allow-Origin', $origin)->withHeader('Vary', 'Origin')
            : $response->withHeader('Access-Control-Allow-Origin', $allowed[0]);

        return $response
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept')
            ->withHeader('Access-Control-Max-Age', '86400');
    }

    private function withSecurityHeaders(Response $response, bool $https): Response
    {
        $response = $response
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'SAMEORIGIN')
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->withHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()')
            ->withHeader('Content-Security-Policy', self::CSP);

        return $https ? $response->withHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains') : $response;
    }
}
