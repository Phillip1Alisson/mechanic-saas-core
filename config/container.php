<?php

declare(strict_types=1);

use App\Application\Actions\CreateClientAction;
use App\Application\Actions\DeleteClientAction;
use App\Application\Actions\GetClientAction;
use App\Application\Actions\ListClientsAction;
use App\Application\Actions\LoginAction;
use App\Application\Actions\LogoutAction;
use App\Application\Actions\UpdateClientAction;
use App\Application\Middleware\AuthMiddleware;
use App\Application\Request\ClientListCriteriaConfig;
use App\Application\Request\ClientRequestValidator;
use App\Application\Request\ListCriteriaParser;
use App\Application\Request\LoginRequestValidator;
use App\Domain\Repositories\ClientRepositoryInterface;
use App\Domain\Repositories\UserRepositoryInterface;
use App\Domain\Services\AuthService;
use App\Domain\Services\ClientService;
use App\Infrastructure\Persistence\JsonClientRepository;
use App\Infrastructure\Persistence\JsonUserRepository;
use App\Infrastructure\Persistence\MySQLClientRepository;
use App\Infrastructure\Persistence\MySQLUserRepository;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;

$builder = new ContainerBuilder();
$builder->addDefinitions([
    // Configuração
    'settings' => [
        'db' => [
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => (int) ($_ENV['DB_PORT'] ?? 3306),
            'name' => $_ENV['DB_NAME'] ?? 'mechanic_saas',
            'user' => $_ENV['DB_USER'] ?? 'root',
            'pass' => $_ENV['DB_PASS'] ?? '',
        ],
        'repository' => trim((string) ($_ENV['REPOSITORY'] ?? 'mysql')),
        'storage_path' => (function () {
            $baseDir = dirname(__DIR__);
            $path = $_ENV['STORAGE_PATH'] ?? $baseDir . '/storage';
            $path = trim((string) $path);
            return ($path !== '' && $path[0] === '/') ? $path : $baseDir . '/' . $path;
        })(),
        'auth_secret' => $_ENV['AUTH_SECRET'] ?? 'change-me-in-production',
    ],

    PDO::class => function (ContainerInterface $c) {
        $s = $c->get('settings')['db'];
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $s['host'], $s['port'], $s['name']);
        return new PDO($dsn, $s['user'], $s['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    },

    // Repositório de clientes: alternar entre MySQL e JSON
    ClientRepositoryInterface::class => function (ContainerInterface $c) {
        $repo = $c->get('settings')['repository'];
        if ($repo === 'json') {
            return new JsonClientRepository($c->get('settings')['storage_path']);
        }
        return new MySQLClientRepository($c->get(PDO::class));
    },

    // Repositório de usuários: alternar entre MySQL e JSON (mesmo que clientes)
    UserRepositoryInterface::class => function (ContainerInterface $c) {
        $repo = $c->get('settings')['repository'];
        if ($repo === 'json') {
            return new JsonUserRepository($c->get('settings')['storage_path']);
        }
        return new MySQLUserRepository($c->get(PDO::class));
    },

    AuthService::class => function (ContainerInterface $c) {
        return new AuthService(
            $c->get(UserRepositoryInterface::class),
            $c->get('settings')['auth_secret'],
        );
    },

    ClientService::class => function (ContainerInterface $c) {
        return new ClientService($c->get(ClientRepositoryInterface::class));
    },

    ListCriteriaParser::class => \DI\autowire(),
    ClientListCriteriaConfig::class => \DI\autowire(),

    ClientRequestValidator::class => \DI\autowire(),
    LoginRequestValidator::class => \DI\autowire(),

    LoginAction::class => \DI\autowire(),
    LogoutAction::class => \DI\autowire(),
    ListClientsAction::class => \DI\autowire(),
    GetClientAction::class => \DI\autowire(),
    CreateClientAction::class => \DI\autowire(),
    UpdateClientAction::class => \DI\autowire(),
    DeleteClientAction::class => \DI\autowire(),

    AuthMiddleware::class => \DI\autowire(),
]);

return $builder->build();
