<?php

namespace Iankibet\Messaging\Http\Controllers;

use Iankibet\Messaging\Message;
use Iankibet\Messaging\MessageDispatcher;
use Iankibet\Messaging\MessageSigner;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * HTTP ingress for the unified messaging layer. Verifies the signature, builds the
 * envelope and routes it through the dispatcher. The handler's reply is returned as
 * JSON so synchronous request/reply works over HTTP.
 */
class MessagingController
{
    public function receive(Request $request, MessageSigner $signer, MessageDispatcher $dispatcher)
    {
        if (!$signer->verifyRequest($request)) {
            return response(['message' => 'Invalid request signature'], 401);
        }

        $message = Message::fromRequest($request);
        if ($message->type === '') {
            return response(['message' => 'Message type is required'], 422);
        }

        if (!$dispatcher->hasHandler($message->type)) {
            return response(['message' => "No handler for message type [{$message->type}]"], 404);
        }

        $result = $dispatcher->dispatch($message);

        if ($result instanceof SymfonyResponse) {
            return $result;
        }

        if (is_array($result)) {
            return response()->json($result);
        }

        return response()->json(['message' => 'ok']);
    }
}
