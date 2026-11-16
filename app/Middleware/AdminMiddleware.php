<?php

namespace App\Middleware;

use Core\Auth;
use Core\HttpException;
use Core\Middleware;
use Core\Request;
use Core\Response;

/** Blocks everyone except admins (runs after AuthMiddleware). */
class AdminMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (!Auth::hasRole('admin')) {
            throw new HttpException(403, 'Admins only.');
        }
        return $next($request);
    }
}
