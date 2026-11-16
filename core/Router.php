<?php

namespace Core;

/**
 * Router – maps an HTTP method + URL pattern to a controller action.
 *
 *   $router->get('/items/{id}', [ItemController::class, 'show'])->middleware('auth');
 *
 * Patterns are compiled into regular expressions; {name} becomes a named capture group.
 */
class Router
{
    private array $routes = [];
    private array $groupMiddleware = [];
    private string $groupPrefix = '';
    private ?int $lastIndex = null;

    /** Short names used in routes/web.php → middleware classes. */
    private array $aliases = [];

    public function aliasMiddleware(string $name, string $class): void
    {
        $this->aliases[$name] = $class;
    }

    public function get(string $path, callable|array $action): self
    {
        return $this->add('GET', $path, $action);
    }

    public function post(string $path, callable|array $action): self
    {
        return $this->add('POST', $path, $action);
    }

    public function delete(string $path, callable|array $action): self
    {
        return $this->add('DELETE', $path, $action);
    }

    /** Groups routes under a shared URL prefix and/or middleware. */
    public function group(array $options, callable $callback): void
    {
        $previousPrefix = $this->groupPrefix;
        $previousMiddleware = $this->groupMiddleware;

        $this->groupPrefix .= $options['prefix'] ?? '';
        $this->groupMiddleware = array_merge($this->groupMiddleware, (array) ($options['middleware'] ?? []));

        $callback($this);

        $this->groupPrefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    private function add(string $method, string $path, callable|array $action): self
    {
        $path = '/' . trim($this->groupPrefix . $path, '/');

        $this->routes[] = [
            'method'     => $method,
            'path'       => $path,
            'pattern'    => $this->compile($path),
            'action'     => $action,
            'middleware' => $this->groupMiddleware,
        ];
        $this->lastIndex = array_key_last($this->routes);

        return $this;
    }

    /** Attach middleware to the route that was just registered. */
    public function middleware(string ...$names): self
    {
        if ($this->lastIndex !== null) {
            array_push($this->routes[$this->lastIndex]['middleware'], ...$names);
        }
        return $this;
    }

    /** "/items/{id}" → "#^/items/(?P<id>[^/]+)$#" */
    private function compile(string $path): string
    {
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $path);
        return '#^' . $regex . '$#';
    }

    /** Finds the matching route. Returns [route, params] or null. Also reports 405s. */
    public function match(string $method, string $path): ?array
    {
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['pattern'], $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                return [$route, $params];
            }
        }
        return null;
    }

    public function dispatch(Request $request): Response
    {
        $result = $this->match($request->method(), $request->path());

        if ($result === null) {
            return $this->notFound($request);
        }

        [$route, $params] = $result;
        $request->setParams($params);

        // Final step of the pipeline: call the controller action.
        $core = function (Request $request) use ($route, $params): Response {
            $action = $route['action'];

            if (is_array($action)) {
                [$class, $method] = $action;
                $controller = new $class();
                $output = $controller->$method($request, ...array_values($params));
            } else {
                $output = $action($request, ...array_values($params));
            }

            return $output instanceof Response ? $output : Response::html((string) $output);
        };

        // Wrap the controller in middleware layers, last-registered innermost (an "onion").
        $pipeline = array_reduce(
            array_reverse($route['middleware']),
            function (callable $next, string $name) {
                $class = $this->aliases[$name] ?? $name;
                return fn (Request $request) => (new $class())->handle($request, $next);
            },
            $core
        );

        return $pipeline($request);
    }

    private function notFound(Request $request): Response
    {
        if ($request->wantsJson()) {
            return Response::json(['error' => 'Not Found'], 404);
        }
        return Response::html(View::render('errors/404', ['title' => 'Page not found']), 404);
    }

    public function routes(): array
    {
        return $this->routes;
    }
}
