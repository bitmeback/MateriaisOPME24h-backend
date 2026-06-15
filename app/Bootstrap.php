<?php
declare(strict_types=1);

namespace MateriaisOpme\App;

use MateriaisOpme\App\Support\Config;
use MateriaisOpme\App\Support\Database;

final class Bootstrap
{
    public function handle(): void
    {
        $baseConfig = require __DIR__ . '/../config/app.php';
        $backendConfig = require __DIR__ . '/../config/backend.php';
        $dbConfig = require __DIR__ . '/../config/database.php';
        $routes = require __DIR__ . '/../config/routes.php';

        $merged = array_replace_recursive($baseConfig, $backendConfig, ['db' => $dbConfig]);
        Config::set($merged);

        date_default_timezone_set((string)Config::get('timezone', 'America/Sao_Paulo'));

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_name((string)Config::get('session_name', 'materiais_opme_sess'));
            session_set_cookie_params([
                'httponly' => (bool)Config::get('session_cookie_httponly', true),
                'secure' => (bool)Config::get('session_cookie_secure', true),
                'samesite' => (string)Config::get('session_cookie_samesite', 'Strict'),
                'path' => '/',
            ]);
            session_start();
        }

        if (Config::get('db.name') !== '') {
            try {
                Database::pdo();
            } catch (\Throwable) {
                // Conexão é preparada aqui para falhar cedo em ambiente real;
                // por enquanto, o erro não interrompe a página inicial.
            }
        }

        $router = new Router();
        $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/', $routes);
    }
}
