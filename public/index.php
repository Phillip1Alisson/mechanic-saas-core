<?php

declare(strict_types=1);

use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

(function () {
    $baseDir = dirname(__DIR__);
    if (is_file($baseDir . '/.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable($baseDir);
        $dotenv->load();
    }
})();

$container = require __DIR__ . '/../config/container.php';
AppFactory::setContainer($container);

$app = AppFactory::create();

// CORS middleware – permitir frontend em desenvolvimento
$app->add(function ($request, $handler) {
    $origin = $request->getHeaderLine('Origin') ?: '*';
    $allowedOrigins = ['http://localhost:3000', 'http://localhost:3001', 'http://127.0.0.1:3000', 'http://127.0.0.1:3001'];
    if (in_array($origin, $allowedOrigins, true)) {
        $responseOrigin = $origin;
    } else {
        $responseOrigin = '*';
    }

    if ($request->getMethod() === 'OPTIONS') {
        $response = new \Slim\Psr7\Response();
        $response = $response
            ->withHeader('Access-Control-Allow-Origin', $responseOrigin)
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept')
            ->withHeader('Access-Control-Max-Age', '86400');
        return $response;
    }

    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', $responseOrigin)
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept');
});

$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(true, true, true);

(require __DIR__ . '/../config/routes.php')($app);

$app->run();
