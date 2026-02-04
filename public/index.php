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

$app->add($container->get(\App\Application\Middleware\CorsMiddleware::class));
$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(true, true, true);

(require __DIR__ . '/../config/routes.php')($app);

$app->run();
