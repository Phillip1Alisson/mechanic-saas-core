<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

final class CorsMiddleware implements MiddlewareInterface
{
    private const DEFAULT_ALLOWED_ORIGINS = [
        'http://localhost:3000',
        'http://localhost:3001',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:3001',
    ];

    private const DEFAULT_ALLOWED_METHODS = 'GET, POST, PUT, DELETE, PATCH, OPTIONS';

    private const DEFAULT_ALLOWED_HEADERS = 'Content-Type, Authorization, X-Requested-With, Accept';

    private const MAX_AGE = 86400;

    /**
     * @param list<string> $allowedOrigins
     * @param list<string> $extraAllowedHeaders Headers extras em Access-Control-Allow-Headers
     */
    public function __construct(
        private array $allowedOrigins = self::DEFAULT_ALLOWED_ORIGINS,
        private string $allowedMethods = self::DEFAULT_ALLOWED_METHODS,
        private string $allowedHeaders = self::DEFAULT_ALLOWED_HEADERS,
        private array $extraAllowedHeaders = [],
        private int $maxAge = self::MAX_AGE,
    ) {
    }

    public function process(Request $request, RequestHandler $handler): ResponseInterface
    {
        $origin = $request->getHeaderLine('Origin') ?: '*';
        $responseOrigin = $this->resolveOrigin($origin);

        if ($request->getMethod() === 'OPTIONS') {
            $response = new Response();
            return $this->withCorsHeaders($response, $responseOrigin, true);
        }

        $response = $handler->handle($request);
        return $this->withCorsHeaders($response, $responseOrigin, false);
    }

    private function resolveOrigin(string $origin): string
    {
        if ($origin === '' || $origin === '*') {
            return '*';
        }
        return in_array($origin, $this->allowedOrigins, true) ? $origin : '*';
    }

    private function withCorsHeaders(ResponseInterface $response, string $origin, bool $isPreflight): ResponseInterface
    {
        $response = $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Methods', $this->allowedMethods)
            ->withHeader('Access-Control-Allow-Headers', $this->mergeAllowedHeaders());

        if ($isPreflight) {
            $response = $response->withHeader('Access-Control-Max-Age', (string) $this->maxAge);
        }

        return $response;
    }

    private function mergeAllowedHeaders(): string
    {
        $headers = array_filter(array_map('trim', explode(',', $this->allowedHeaders)));
        $headers = array_unique(array_merge($headers, $this->extraAllowedHeaders));
        return implode(', ', $headers);
    }
}
