<?php

namespace App\Middleware;

use Core\Auth;
use Core\Middleware;
use Core\Request;
use Core\Response;
use Core\Session;

/** Only logged-in users may continue; guests are sent to the login page. */
class AuthMiddleware implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (!Auth::check()) {
            if ($request->wantsJson()) {
                return Response::json(['error' => 'Unauthenticated'], 401);
            }
            Session::set('intended', $request->path());
            Session::flash('message', ['type' => 'info', 'text' => 'Please log in to continue.']);
            return Response::redirect(url('/login'));
        }

        if (!empty(Auth::user()['is_banned'])) {
            Auth::logout();
            Session::flash('message', ['type' => 'error', 'text' => 'Your account has been suspended.']);
            return Response::redirect(url('/login'));
        }

        return $next($request);
    }
}
