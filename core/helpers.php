<?php

/**
 * Global helper functions available in controllers and views.
 */

use Core\Application;
use Core\Auth;
use Core\Session;

/** Escape output for HTML – use on EVERY user-supplied value in views (prevents XSS). */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Builds an app URL that works inside an XAMPP sub-folder: url('/items/3') → /lootvault/items/3 */
function url(string $path = '/'): string
{
    return Application::$basePath . '/' . ltrim($path, '/');
}

/** URL to a file inside /public, e.g. asset('assets/css/app.css'). */
function asset(string $path): string
{
    // The root .htaccess forwards /lootvault/assets/... to /lootvault/public/assets/...
    return Application::$basePath . '/' . ltrim($path, '/');
}

function config(string $key, mixed $default = null): mixed
{
    return Application::config($key, $default);
}

function auth(): ?array
{
    return Auth::user();
}

function csrf_token(): string
{
    return Session::csrfToken();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function method_field(string $method): string
{
    return '<input type="hidden" name="_method" value="' . e(strtoupper($method)) . '">';
}

/** Re-fill a form field after a failed validation. */
function old(string $key, mixed $default = ''): mixed
{
    return Session::getFlash('old', [])[$key] ?? $default;
}

/** Validation error message for one field (or null). */
function error(string $key): ?string
{
    return Session::getFlash('errors', [])[$key] ?? null;
}

/** Formats a peso amount: money(1999) → "₱1,999.00" */
function money(float|string|null $amount): string
{
    return config('app.currency_symbol', '₱') . number_format((float) $amount, 2);
}

function str_limit(string $text, int $limit = 100): string
{
    return mb_strlen($text) > $limit ? rtrim(mb_substr($text, 0, $limit)) . '…' : $text;
}

function time_ago(string $datetime): string
{
    $seconds = time() - strtotime($datetime);
    return match (true) {
        $seconds < 60     => 'just now',
        $seconds < 3600   => floor($seconds / 60) . ' min ago',
        $seconds < 86400  => floor($seconds / 3600) . ' hr ago',
        $seconds < 604800 => floor($seconds / 86400) . ' days ago',
        default           => date('M j, Y', strtotime($datetime)),
    };
}

/** URL for an item's cover image (falls back to a generated placeholder). */
function item_image(?string $image): string
{
    return $image ? asset('uploads/' . $image) : asset('assets/img/placeholder.svg');
}
