<?php

namespace App\Middleware;

use Core\HttpException;
use Core\Middleware;
use Core\Request;
use Core\Response;
use Core\Session;

/**
 * Cross-Site Request Forgery protection: every POST/DELETE form must send back
 * the secret token stored in the session (added by csrf_field() in the view).
 */
class CsrfMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        if ($request->isPost()) {
            $token = (string) $request->input('_csrf', '');
            if (!hash_equals(Session::csrfToken(), $token)) {
                throw new HttpException(419, 'Your session expired. Please go back, refresh the page and try again.');
            }
        }
        return $next($request);
    }
}
