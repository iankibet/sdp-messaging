<?php

namespace Iankibet\Messaging;

use Iankibet\Messaging\Contracts\MessageHandler;

/**
 * Receiver-side router. Maps a Message type to its handler (from
 * config('messaging.handlers')) and invokes it. Shared by every ingress point —
 * the HTTP receive endpoint today, a broker consumer command later.
 */
class MessageDispatcher
{
    /**
     * @return mixed The handler's return value (array reply, Response, or null).
     */
    public function dispatch(Message $message)
    {
        $handlerClass = $this->handlers()[$message->type] ?? null;
        if (!$handlerClass) {
            throw new \RuntimeException("No message handler registered for type [{$message->type}]");
        }

        $handler = app($handlerClass);
        if (!$handler instanceof MessageHandler) {
            throw new \RuntimeException("Handler [{$handlerClass}] must implement " . MessageHandler::class);
        }

        return $handler->handle($message);
    }

    public function hasHandler(string $type): bool
    {
        return isset($this->handlers()[$type]);
    }

    /**
     * @return array<string,class-string>
     */
    protected function handlers(): array
    {
        return (array) config('messaging.handlers', []);
    }
}
