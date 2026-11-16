<?php

namespace Core;

/** Logger – appends timestamped lines to storage/logs/app-YYYY-MM-DD.log */
class Logger
{
    private static string $dir = '';

    public static function setDirectory(string $dir): void
    {
        self::$dir = rtrim($dir, '/');
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    private static function write(string $level, string $message, array $context): void
    {
        if (self::$dir === '' || !is_dir(self::$dir)) {
            return;
        }
        $line = sprintf(
            "[%s] %s: %s %s\n",
            date('Y-m-d H:i:s'),
            $level,
            $message,
            $context ? json_encode($context, JSON_UNESCAPED_SLASHES) : ''
        );
        @file_put_contents(self::$dir . '/app-' . date('Y-m-d') . '.log', $line, FILE_APPEND | LOCK_EX);
    }
}
