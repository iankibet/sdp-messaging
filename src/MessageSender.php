<?php

namespace Iankibet\Messaging;

use Iankibet\Messaging\Contracts\MessageTransport;

/**
 * The single entry point application code uses to talk to the other service.
 * Resolves the configured transport and builds the envelope; callers never touch
 * QStash / signing / transport details directly.
 */
class MessageSender
{
    public function __construct(protected MessageTransport $transport)
    {
    }

    /**
     * Fire-and-forget. Returns the transport ack (queued = successful under QStash,
     * the real receiver response under the direct driver).
     */
    public function send(string $destination, string $type, array $payload, array $options = [], array $meta = []): MessageResponse
    {
        return $this->transport->publish($destination, new Message($type, $payload, meta: $meta), $options);
    }

    /**
     * Synchronous request/reply. Returns the receiver handler's reply.
     */
    public function request(string $destination, string $type, array $payload, array $options = [], array $meta = []): MessageResponse
    {
        return $this->transport->request($destination, new Message($type, $payload, meta: $meta), $options);
    }
}
