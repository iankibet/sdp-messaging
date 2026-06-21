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
    /**
     * Message type used to confirm that a previously delivered message finished
     * processing. The producer listens for this to reconcile/track completion.
     */
    public const COMPLETED_TYPE = 'message.completed';

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

    /**
     * Send a pre-built message. Lets the producer control the id and meta (e.g. a
     * reply_to address) so it can track the message itself — the library never
     * stores anything.
     */
    public function sendMessage(string $destination, Message $message, array $options = []): MessageResponse
    {
        return $this->transport->publish($destination, $message, $options);
    }

    /**
     * Optionally called by a handler to confirm it finished processing a message.
     * Sends a completion message back to the producer's reply_to address (carrying
     * the original message id). No-op when the message carries no reply_to.
     */
    public function complete(Message $message, array $result = [], array $options = []): ?MessageResponse
    {
        $replyTo = $message->replyTo();
        if (!$replyTo) {
            return null;
        }

        return $this->send($replyTo, self::COMPLETED_TYPE, [
            'message_id' => $message->id,
            'type' => $message->type,
            'result' => $result,
        ], $options);
    }
}
