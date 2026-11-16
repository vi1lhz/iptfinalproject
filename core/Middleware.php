<?php

namespace Core;

/**
 * Middleware contract. Each middleware receives the request and a $next callable.
 * It can stop the request (return a Response such as a redirect) or pass it on: return $next($request).
 */
interface Middleware
{
    public function handle(Request $request, callable $next): Response;
}
