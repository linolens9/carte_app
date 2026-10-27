<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Protection contre les attaques CSRF (Cross-Site Request Forgery)
 */
class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function generateToken(): string
    {
        Session::start();
        $token = Session::get(self::SESSION_KEY);
        if (!$token || !is_string($token) || strlen($token) !== 64) {
            $token = bin2hex(random_bytes(32));
            Session::set(self::SESSION_KEY, $token);
        }
        return $token;
    }

    public static function getToken(): string
    {
        return self::generateToken();
    }

    public static function validateToken(?string $token): bool
    {
        if (empty($token)) {
            return false;
        }
        $sessionToken = Session::get(self::SESSION_KEY);
        if (!$sessionToken || !is_string($sessionToken)) {
            return false;
        }
        return hash_equals($sessionToken, $token);
    }

    public static function inputField(): string
    {
        $token = htmlspecialchars(self::getToken(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="_csrf_token" value="' . $token . '">';
    }

    public static function metaTag(): string
    {
        $token = htmlspecialchars(self::getToken(), ENT_QUOTES, 'UTF-8');
        return '<meta name="csrf-token" content="' . $token . '">';
    }
}
