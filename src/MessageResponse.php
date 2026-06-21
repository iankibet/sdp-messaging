<?php

namespace Iankibet\Messaging;

use Illuminate\Http\Client\Response;

/**
 * Uniform result returned by every transport. For synchronous request/reply it
 * carries the handler's reply; for fire-and-forget publishes it carries the
 * transport ack (e.g. the QStash queue acknowledgement).
 *
 * The accessor surface (successful/status/json/body) intentionally mirrors a
 * subset of Illuminate's HTTP Response so existing call sites need minimal change.
 */
class MessageResponse
{
    public function __construct(
        protected int $statusCode,
        protected array $data = [],
        protected string $raw = '',
    ) {
    }

    public function status(): int
    {
        return $this->statusCode;
    }

    public function successful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    public function body(): string
    {
        return $this->raw;
    }

    /**
     * @return mixed
     */
    public function json(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->data;
        }

        return data_get($this->data, $key, $default);
    }

    public function array(): array
    {
        return $this->data;
    }

    public static function fromHttp(Response $response): self
    {
        $json = $response->json();

        return new self(
            $response->status(),
            is_array($json) ? $json : [],
            $response->body(),
        );
    }

    public static function ok(array $data = []): self
    {
        return new self(200, $data, json_encode($data) ?: '');
    }
}
