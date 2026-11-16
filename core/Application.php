<?php

namespace Core;

/**
 * Application – the framework's entry point. It boots configuration, the database,
 * the session and the router, then turns a Request into a Response.
 */
class Application
{
    public static string $root;
    public static string $basePath = '';
    private static array $config = [];

    public Router $router;

    public function __construct(string $root)
    {
        self::$root = rtrim($root, '/');

        Env::load(self::$root . '/.env');
        self::$config = [
            'app'      => require self::$root . '/config/app.php',
            'database' => require self::$root . '/config/database.php',
        ];

        date_default_timezone_set(self::config('app.timezone', 'UTC'));
        View::setPath(self::$root . '/app/Views');
        Logger::setDirectory(self::$root . '/storage/logs');

        $this->router = new Router();
    }

    /** config('app.name') – dot notation lookup into config/*.php */
    public static function config(string $key, mixed $default = null): mixed
    {
        $value = self::$config;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }

    public function connectDatabase(): void
    {
        Database::connect(self::config('database'));
    }

    public function loadRoutes(string $file): void
    {
        $router = $this->router;
        require $file;
    }

    public function handle(Request $request): Response
    {
        self::$basePath = $request->basePath();

        try {
            return $this->router->dispatch($request);
        } catch (ValidationException) {
            // Errors + old input are already flashed by Controller::validate().
            return Response::redirect($_SERVER['HTTP_REFERER'] ?? url('/'));
        } catch (HttpException $e) {
            return Response::html(
                View::render('errors/error', ['title' => 'Error ' . $e->status(), 'status' => $e->status(), 'message' => $e->getMessage()]),
                $e->status()
            );
        } catch (\Throwable $e) {
            Logger::error($e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
            $debug = (bool) self::config('app.debug', false);
            return Response::html(
                View::render('errors/error', [
                    'title'   => 'Server error',
                    'status'  => 500,
                    'message' => $debug ? $e->getMessage() . ' in ' . basename($e->getFile()) . ':' . $e->getLine() : 'Something went wrong. Please try again.',
                    'trace'   => $debug ? $e->getTraceAsString() : null,
                ]),
                500
            );
        }
    }

    public function run(): void
    {
        Session::start();
        $this->handle(Request::capture())->send();
    }
}
