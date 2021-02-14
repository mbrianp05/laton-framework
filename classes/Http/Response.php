<?php

namespace Mbrianp\FuncCollection\Http;

use InvalidArgumentException;

class Response
{
    public const AVAILABLE_CONTENT_TYPES = [
        'html' => 'text/html',
        'json' => 'application/json',
    ];

    public function __construct(
        public string $response = '',
        public int $status = 200,
        public string $contentType = self::AVAILABLE_CONTENT_TYPES['html'],
    )
    {
        if (!in_array($this->contentType, self::AVAILABLE_CONTENT_TYPES)) {
            throw new InvalidArgumentException(sprintf('Invalid content type: %s, expected one of these: %s', $this->contentType, implode(', ', self::AVAILABLE_CONTENT_TYPES)));
        }
    }

    public function send(): void
    {
        if (!headers_sent()) {
            header('HTTP/1.0 ' . $this->status);
            header('Content-Type: ' . $this->contentType);
        }

        echo $this->response;
    }
}