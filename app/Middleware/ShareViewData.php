<?php

namespace App\Middleware;

use App\Models\CartItem;
use App\Models\Category;
use Core\Auth;
use Core\Middleware;
use Core\Request;
use Core\Response;
use Core\View;

/** Makes data every page needs (user, cart badge, categories) available to all views. */
class ShareViewData implements Middleware
{
    public function handle(Request $request, callable $next): Response
    {
        View::share('authUser', Auth::user());
        View::share('cartCount', Auth::check() ? CartItem::countFor(Auth::id()) : 0);
        View::share('navCategories', Category::all('id ASC'));
        View::share('currentPath', $request->path());

        return $next($request);
    }
}
