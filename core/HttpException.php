<?php

namespace Core;

/** Throw from anywhere to stop with an HTTP error page, e.g. throw new HttpException(403, 'Not your item'). */
class HttpException extends \RuntimeException
{
    public function __construct(private int $status, string $message = '')
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }
}
