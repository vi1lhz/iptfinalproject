<?php

namespace Core;

/**
 * Request – wraps PHP's superglobals ($_GET, $_POST, $_SERVER, $_FILES)
 * in a single object so controllers never touch them directly.
 */
class Request
{
    private array $params = [];   // route parameters, e.g. {id}
    private string $basePath;

    public function __construct(
        private array $query = [],
        private array $body = [],
        private array $server = [],
        private array $files = []
    ) {
        $this->basePath = $this->detectBasePath();
    }

    public static function capture(): self
    {
        return new self($_GET, $_POST, $_SERVER, $_FILES);
    }

    /** HTTP method. Supports <input name="_method" value="DELETE"> spoofing for HTML forms. */
    public function method(): string
    {
        $method = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
        if ($method === 'POST' && isset($this->body['_method'])) {
            return strtoupper($this->body['_method']);
        }
        return $method;
    }

    /** Path relative to the app, e.g. "/items/5" (works inside an XAMPP sub-folder). */
    public function path(): string
    {
        $uri = parse_url($this->server['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $uri = rawurldecode($uri);

        if ($this->basePath !== '' && str_starts_with($uri, $this->basePath)) {
            $uri = substr($uri, strlen($this->basePath));
        }

        $uri = '/' . trim($uri, '/');
        return $uri === '/index.php' ? '/' : $uri;
    }

    /**
     * Figures out the folder the app lives in, e.g. "/lootvault" when installed at
     * htdocs/lootvault. Handles both "/lootvault/public/..." and the prettier "/lootvault/..."
     * (when the root .htaccess forwards everything to /public).
     */
    private function detectBasePath(): string
    {
        $scriptDir = str_replace('\\', '/', dirname($this->server['SCRIPT_NAME'] ?? '/'));
        $scriptDir = rtrim($scriptDir, '/');
        $uri = parse_url($this->server['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        if ($scriptDir !== '' && (str_starts_with($uri, $scriptDir . '/') || $uri === $scriptDir)) {
            return $scriptDir;
        }
        if (str_ends_with($scriptDir, '/public')) {
            $parent = substr($scriptDir, 0, -strlen('/public'));
            if ($parent === '' || str_starts_with($uri, $parent)) {
                return $parent;
            }
        }
        return '';
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $value = $this->body[$key] ?? $this->query[$key] ?? $default;
        return is_string($value) ? trim($value) : $value;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;
        return ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) ? $file : null;
    }

    public function isPost(): bool
    {
        return $this->method() !== 'GET';
    }

    public function wantsJson(): bool
    {
        return str_contains($this->server['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    public function ip(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }
}
