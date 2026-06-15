<?php
declare(strict_types=1);

namespace MateriaisOpme\App;

use MateriaisOpme\App\Middleware\AuthMiddleware;

final class Router
{
    public function dispatch(string $method, string $uri, array $routes): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $auth = new AuthMiddleware();

        foreach ($routes as $route) {
            [$routeMethod, $pattern, $handler, $authRequired] = $route;

            if (strcasecmp($method, $routeMethod) !== 0) {
                continue;
            }

            $regex = preg_replace('#\{[a-zA-Z_][a-zA-Z0-9_]*\}#', '([0-9]+)', $pattern);
            $regex = '#^' . $regex . '$#';

            if (!preg_match($regex, $path, $matches)) {
                continue;
            }

            if ($authRequired) {
                $auth->requireLogin();
            }

            array_shift($matches);
            $this->invokeHandler($handler, $matches);
            return;
        }

        http_response_code(404);
        echo 'Página não encontrada.';
    }

    private function invokeHandler(string $handler, array $params): void
    {
        [$class, $method] = explode('@', $handler, 2);
        $fqcn = 'MateriaisOpme\\App\\Controllers\\' . $class;
        $controller = new $fqcn();
        $reflection = new \ReflectionMethod($controller, $method);
        $reflection->invokeArgs($controller, $params);
    }
}
