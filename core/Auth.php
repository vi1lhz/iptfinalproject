<?php

namespace Core;

/**
 * Auth – session-based login. Only the user id is stored in the session;
 * the user row is loaded once per request and cached.
 */
class Auth
{
    private static ?array $user = null;
    private static bool $loaded = false;

    public static function attempt(string $email, string $password): bool
    {
        $user = Database::getInstance()->fetch('SELECT * FROM users WHERE email = ?', [$email]);

        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }
        if (!empty($user['is_banned'])) {
            return false;
        }

        self::login($user);
        return true;
    }

    public static function login(array $user): void
    {
        Session::regenerate();
        Session::set('user_id', (int) $user['id']);
        self::$user = $user;
        self::$loaded = true;
    }

    public static function logout(): void
    {
        Session::forget('user_id');
        Session::regenerate();
        self::$user = null;
        self::$loaded = true;
    }

    public static function user(): ?array
    {
        if (!self::$loaded) {
            $id = Session::get('user_id');
            self::$user = $id ? Database::getInstance()->fetch('SELECT * FROM users WHERE id = ?', [$id]) : null;
            self::$loaded = true;
        }
        return self::$user;
    }

    public static function id(): ?int
    {
        return isset(self::user()['id']) ? (int) self::user()['id'] : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function hasRole(string ...$roles): bool
    {
        return self::check() && in_array(self::user()['role'], $roles, true);
    }

    /** Clears the per-request cache (used by tests). */
    public static function reset(): void
    {
        self::$user = null;
        self::$loaded = false;
    }
}
