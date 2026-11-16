<?php

namespace App\Middleware;

use Core\Auth;
use Core\Logger;
use Core\Middleware;
use Core\Request;
use Core\Response;

/** Writes one line per request to storage/logs: method, path, status, time taken, user. */
class LoggerMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        $start = microtime(true);
        $response = $next($request);

        Logger::info(sprintf(
            '%s %s → %d (%.1f ms)',
            $request->method(),
            $request->path(),
            $response->status(),
            (microtime(true) - $start) * 1000
        ), ['user' => Auth::id(), 'ip' => $request->ip()]);

        return $response;
    }
}
