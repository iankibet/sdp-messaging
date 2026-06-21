<?php

namespace Iankibet\Messaging\Transports;

use Iankibet\Messaging\Contracts\MessageTransport;
use Iankibet\Messaging\Message;
use Iankibet\Messaging\MessageResponse;
use Iankibet\Messaging\MessageSigner;
use Illuminate\Support\Facades\Http;

/**
 * Synchronous, signed HTTP transport. Used directly when MESSAGING_DRIVER=direct
 * (local/dev parity) and as the request/reply fallback for async transports.
 */
class DirectHttpTransport implements MessageTransport
{
    public function __construct(protected MessageSigner $signer)
    {
    }

    public function publish(string $destination, Message $message, array $options = []): MessageResponse
    {
        return MessageResponse::fromHttp($this->post($destination, $message, $options));
    }

    public function request(string $destination, Message $message, array $options = []): MessageResponse
    {
        return MessageResponse::fromHttp($this->post($destination, $message, $options));
    }

    protected function post(string $destination, Message $message, array $options)
    {
        $body = $message->toJson();
        $headers = array_merge(
            ['X-Sdp-Message-Type' => $message->type],
            $this->signer->headers($body),
        );

        return Http::withOptions($this->httpOptions($options))
            ->withHeaders($headers)
            ->withBody($body, 'application/json')
            ->post($destination);
    }

    protected function httpOptions(array $options): array
    {
        return array_merge([
            'verify' => false,
            'timeout' => 300,
        ], $options);
    }
}
