<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Autoloader PSR-4
 */
class Autoloader
{
    private static string $prefix = 'App\\';
    private static string $baseDir = '';

    public static function register(): void
    {
        self::$baseDir = dirname(__DIR__) . DIRECTORY_SEPARATOR;

        spl_autoload_register(function (string $class) {
            $len = strlen(self::$prefix);
            if (strncmp(self::$prefix, $class, $len) !== 0) {
                return;
            }

            $relativeClass = substr($class, $len);
            $file = self::$baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

            if (file_exists($file)) {
                require $file;
            }
        });
    }
}
