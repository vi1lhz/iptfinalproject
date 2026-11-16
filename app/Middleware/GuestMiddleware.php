<?php

namespace App\Middleware;

use Core\Auth;
use Core\Middleware;
use Core\Request;
use Core\Response;

/** Login/register pages are only for visitors who are NOT logged in. */
class GuestMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (Auth::check()) {
            return Response::redirect(url('/'));
        }
        return $next($request);
    }
}
