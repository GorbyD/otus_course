<?php

namespace Http;

final class Router
{
    private array $routes = [];
    private array $allowedMethods = [];

    public function get(string $path, callable $handler): void
    {
        $this->map('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->map('POST', $path, $handler);
    }

    public function delete(string $path, callable $handler): void
    {
        $this->map('DELETE', $path, $handler);
    }

    private function map(string $method, string $path, callable $handler): void
    {
        $this->routes[$method . ' ' . $path] = $handler;
        $this->allowedMethods[$path][] = $method;
    }

    public function dispatch(Request $request): Response
    {
        $handler = $this->routes[$request->method . ' ' . $request->path] ?? null;
        if ($handler !== null) {
            return $handler($request);
        }

        $allowed = $this->allowedMethods[$request->path] ?? null;
        if ($allowed === null) {
            return Response::text("Not Found\n", 404);
        }

        return Response::text(
            'Method Not Allowed. Допустимо: ' . implode(', ', $allowed) . ".\n",
            405,
        );
    }
}
