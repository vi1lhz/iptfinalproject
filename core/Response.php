<?php

namespace Core;

/**
 * Response – everything the app sends back: HTML, JSON, redirects or file downloads.
 * Controllers return a Response; Application::run() calls send().
 */
class Response
{
    private array $headers = [];
    private ?string $filePath = null;

    public function __construct(
        private string $content = '',
        private int $status = 200
    ) {
    }

    public static function html(string $html, int $status = 200): self
    {
        return (new self($html, $status))->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public static function json(mixed $data, int $status = 200): self
    {
        return (new self(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $status))
            ->header('Content-Type', 'application/json');
    }

    public static function redirect(string $url, int $status = 302): self
    {
        return (new self('', $status))->header('Location', $url);
    }

    /** Streams a protected file (used for digital item downloads). */
    public static function download(string $path, string $downloadName): self
    {
        $response = new self('', 200);
        $response->filePath = $path;
        return $response
            ->header('Content-Type', 'application/octet-stream')
            ->header('Content-Disposition', 'attachment; filename="' . basename($downloadName) . '"')
            ->header('Content-Length', (string) filesize($path));
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $name => $value) {
                header("$name: $value");
            }
        }

        if ($this->filePath !== null) {
            readfile($this->filePath);
            return;
        }

        echo $this->content;
    }
}
