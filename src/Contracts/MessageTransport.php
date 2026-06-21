<?php

namespace Iankibet\Messaging\Contracts;

use Iankibet\Messaging\Message;
use Iankibet\Messaging\MessageResponse;

/**
 * A transport moves a Message from sender to receiver. Swapping QStash for Kafka
 * or Redis pub-sub is a matter of providing another implementation and pointing
 * config('messaging.driver') at it.
 *
 * $destination is interpreted per transport: a full URL for HTTP-based transports
 * (direct / QStash), a topic/channel name for brokers.
 */
interface MessageTransport
{
    /**
     * Fire-and-forget delivery. Returns the transport ack (not the handler reply).
     */
    public function publish(string $destination, Message $message, array $options = []): MessageResponse;

    /**
     * Synchronous request/reply. Async transports fall back to signed direct HTTP.
     */
    public function request(string $destination, Message $message, array $options = []): MessageResponse;
}
