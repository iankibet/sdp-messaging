<?php

namespace Iankibet\Messaging\Contracts;

use Iankibet\Messaging\Message;

/**
 * Receiver-side handler for a single message type. Registered in
 * config('messaging.handlers') keyed by Message::$type.
 */
interface MessageHandler
{
    /**
     * Handle an incoming message. Return an array to send back as a synchronous
     * reply body, a Symfony/Illuminate Response to control the HTTP status, or
     * null for fire-and-forget messages.
     *
     * @return mixed
     */
    public function handle(Message $message);
}
