<?php

namespace App\Services;

use Core\Application;
use Core\Session;
use Core\ValidationException;

/**
 * UploadService – safely stores uploaded files.
 *  - Cover images → public/uploads (anyone can view them)
 *  - Digital goods → storage/files (NOT web-accessible; served only via the protected download route)
 * Files get random names so users can't guess or overwrite them.
 */
class UploadService
{
    private const IMAGE_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    private const BLOCKED_EXTENSIONS = ['php', 'phtml', 'phar', 'exe', 'sh', 'bat', 'js', 'html', 'htm'];

    public function image(?array $file): ?string
    {
        if (!$file) {
            return null;
        }
        $this->checkSize($file, 'image', 5);

        $info = @getimagesize($file['tmp_name']);
        $mime = $info['mime'] ?? '';
        if (!isset(self::IMAGE_TYPES[$mime])) {
            $this->fail('image', 'Cover image must be a JPG, PNG, WEBP or GIF.');
        }

        $name = bin2hex(random_bytes(12)) . '.' . self::IMAGE_TYPES[$mime];
        $this->move($file, Application::$root . '/public/uploads/' . $name);
        return $name;
    }

    /** @return array{0: string, 1: string} [stored name, original name] */
    public function protectedFile(?array $file): array
    {
        if (!$file) {
            $this->fail('digital_file', 'Upload the file the buyer will download.');
        }
        $this->checkSize($file, 'digital_file', (int) config('app.max_upload_mb', 20));

        $original = basename((string) $file['name']);
        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if (in_array($extension, self::BLOCKED_EXTENSIONS, true)) {
            $this->fail('digital_file', 'That file type is not allowed. Zip it first.');
        }

        $stored = bin2hex(random_bytes(16)) . '.bin';
        $this->move($file, Application::$root . '/storage/files/' . $stored);
        return [$stored, $original];
    }

    private function checkSize(array $file, string $field, int $maxMb): void
    {
        if ($file['size'] > $maxMb * 1024 * 1024) {
            $this->fail($field, "File is too large (max {$maxMb} MB).");
        }
    }

    private function move(array $file, string $destination): void
    {
        $ok = is_uploaded_file($file['tmp_name'])
            ? move_uploaded_file($file['tmp_name'], $destination)
            : rename($file['tmp_name'], $destination); // CLI/tests

        if (!$ok) {
            throw new \RuntimeException('Could not save the uploaded file. Check folder permissions.');
        }
    }

    private function fail(string $field, string $message): never
    {
        Session::flash('errors', [$field => $message]);
        Session::flash('old', $_POST);
        throw new ValidationException([$field => $message]);
    }
}
