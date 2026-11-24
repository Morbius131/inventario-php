<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var list<array{0:string,1:string,2:array{0:class-string,1:string}}> */
    private array $routes = [];

    /** @param array{0:class-string,1:string} $handler */
    public function get(string $pattern, array $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    /** @param array{0:class-string,1:string} $handler */
    public function post(string $pattern, array $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    /** @param array{0:class-string,1:string} $handler */
    private function add(string $method, string $pattern, array $handler): void
    {
        // {id} solo acepta dígitos; cualquier otro placeholder acepta cualquier segmento.
        $regex = preg_replace_callback(
            '#\{(\w+)\}#',
            static fn (array $m): string => '(?P<' . $m[1] . '>' . ($m[1] === 'id' ? '\d+' : '[^/]+') . ')',
            $pattern
        );
        $this->routes[] = [$method, '#^' . $regex . '$#', $handler];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = rtrim(parse_url($uri, PHP_URL_PATH) ?: '/', '/') ?: '/';

        foreach ($this->routes as [$routeMethod, $regex, $handler]) {
            if ($routeMethod !== $method || !preg_match($regex, $path, $matches)) {
                continue;
            }

            $params = array_values(array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));
            [$class, $action] = $handler;
            (new $class())->$action(...$params);

            return;
        }

        http_response_code(404);
        View::render('errors/404', ['title' => 'No encontrado']);
    }
}
