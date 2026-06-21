<?php

namespace Iankibet\Messaging;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Transport-agnostic message envelope exchanged between the CMS backend and the
 * themes runtime. The same shape is understood by every transport (QStash today,
 * Kafka / Redis pub-sub later), so callers never depend on the wire mechanism.
 */
class Message
{
    public string $id;
    public int $timestamp;

    public function __construct(
        public string $type,
        public array $payload = [],
        ?string $id = null,
        ?int $timestamp = null,
        public array $meta = [],
    ) {
        $this->id = $id ?: (string) Str::uuid();
        $this->timestamp = $timestamp ?: now()->timestamp;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'payload' => $this->payload,
            'timestamp' => $this->timestamp,
            'meta' => $this->meta,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray()) ?: '{}';
    }

    public static function fromArray(array $data): self
    {
        return new self(
            type: (string) ($data['type'] ?? ''),
            payload: (array) ($data['payload'] ?? []),
            id: isset($data['id']) ? (string) $data['id'] : null,
            timestamp: isset($data['timestamp']) ? (int) $data['timestamp'] : null,
            meta: (array) ($data['meta'] ?? []),
        );
    }

    public static function fromRequest(Request $request): self
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            $data = $request->all();
        }

        return self::fromArray($data);
    }
}
