<?php

declare(strict_types=1);

use App\Application\Actions\CreateClientAction;
use App\Application\Actions\DeleteClientAction;
use App\Application\Actions\GetClientAction;
use App\Application\Actions\ListClientsAction;
use App\Application\Actions\LoginAction;
use App\Application\Actions\UpdateClientAction;
use App\Application\Middleware\AuthMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    $app->post('/login', LoginAction::class);

    $app->group('/clients', function (RouteCollectorProxy $group) {
        $group->get('', ListClientsAction::class);
        $group->get('/{id}', GetClientAction::class);
        $group->post('', CreateClientAction::class);
        $group->put('/{id}', UpdateClientAction::class);
        $group->delete('/{id}', DeleteClientAction::class);
    })->add(AuthMiddleware::class);
};
