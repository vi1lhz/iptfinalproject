<?php

namespace Tests;

use Core\Middleware;
use Core\Request;
use Core\Response;
use Core\Router;

class RouterTest extends TestCase
{
    private function request(string $method, string $uri): Request
    {
        return new Request([], [], ['REQUEST_METHOD' => $method, 'REQUEST_URI' => $uri, 'SCRIPT_NAME' => '/index.php']);
    }

    public function testMatchesStaticRoute(): void
    {
        $router = new Router();
        $router->get('/browse', fn () => 'browse page');
        $this->assertSame('browse page', $router->dispatch($this->request('GET', '/browse'))->content());
    }

    public function testExtractsRouteParameters(): void
    {
        $router = new Router();
        $router->get('/orders/{number}/items/{id}', fn (Request $r, string $number, string $id) => "$number:$id");
        $response = $router->dispatch($this->request('GET', '/orders/LV-1/items/7'));
        $this->assertSame('LV-1:7', $response->content());
    }

    public function testDistinguishesHttpMethods(): void
    {
        $router = new Router();
        $router->get('/cart', fn () => 'show');
        $router->post('/cart', fn () => 'save');
        $this->assertSame('save', $router->dispatch($this->request('POST', '/cart'))->content());
        $this->assertNull($router->match('DELETE', '/cart'));
    }

    public function testSupportsMethodSpoofingForHtmlForms(): void
    {
        $router = new Router();
        $router->delete('/items/{id}', fn ($r, $id) => "deleted $id");
        $request = new Request([], ['_method' => 'DELETE'], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/items/3', 'SCRIPT_NAME' => '/index.php']);
        $this->assertSame('deleted 3', $router->dispatch($request)->content());
    }

    public function testGroupAddsPrefix(): void
    {
        $router = new Router();
        $router->group(['prefix' => '/admin'], function (Router $r) {
            $r->get('/users', fn () => 'users');
            $r->get('', fn () => 'dashboard');
        });
        $this->assertSame('users', $router->dispatch($this->request('GET', '/admin/users'))->content());
        $this->assertSame('dashboard', $router->dispatch($this->request('GET', '/admin'))->content());
    }

    public function testParametersDoNotMatchSlashes(): void
    {
        $router = new Router();
        $router->get('/items/{slug}', fn () => 'item');
        $this->assertNull($router->match('GET', '/items/a/b'));
    }

    public function testMiddlewareRunsInOrderAndWrapsController(): void
    {
        TraceMiddlewareA::$log = [];
        $router = new Router();
        $router->aliasMiddleware('a', TraceMiddlewareA::class);
        $router->aliasMiddleware('b', TraceMiddlewareB::class);
        $router->group(['middleware' => ['a']], function (Router $r) {
            $r->get('/x', function () {
                TraceMiddlewareA::$log[] = 'controller';
                return 'ok';
            })->middleware('b');
        });
        $router->dispatch($this->request('GET', '/x'));
        $this->assertSame(['a:before', 'b:before', 'controller', 'b:after', 'a:after'], TraceMiddlewareA::$log);
    }

    public function testMiddlewareCanStopTheRequest(): void
    {
        $router = new Router();
        $router->get('/secret', fn () => 'secret data')->middleware(BlockingMiddleware::class);
        $response = $router->dispatch($this->request('GET', '/secret'));
        $this->assertSame(403, $response->status());
        $this->assertSame('blocked', $response->content());
    }
}

class TraceMiddlewareA implements Middleware
{
    public static array $log = [];
    public function handle(Request $request, callable $next): Response
    {
        self::$log[] = 'a:before';
        $response = $next($request);
        self::$log[] = 'a:after';
        return $response;
    }
}

class TraceMiddlewareB implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        TraceMiddlewareA::$log[] = 'b:before';
        $response = $next($request);
        TraceMiddlewareA::$log[] = 'b:after';
        return $response;
    }
}

class BlockingMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        return new Response('blocked', 403);
    }
}
