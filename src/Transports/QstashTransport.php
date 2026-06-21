<?php

namespace Iankibet\Messaging\Transports;

use Iankibet\Messaging\Contracts\MessageTransport;
use Iankibet\Messaging\Message;
use Iankibet\Messaging\MessageResponse;
use Iankibet\Messaging\MessageSigner;
use Illuminate\Support\Facades\Http;

/**
 * Upstash QStash transport. publish() queues the message for async HTTP delivery
 * to $destination (a full receiver URL). Our signature + message-type headers are
 * forwarded to the receiver via QStash's Upstash-Forward-* mechanism so the
 * receiver can still verify them end-to-end.
 *
 * QStash cannot return a handler reply, so request() falls back to a signed direct
 * HTTP call (same behaviour as the legacy postSignedThemesRequest path).
 */
class QstashTransport implements MessageTransport
{
    public function __construct(
        protected MessageSigner $signer,
        protected DirectHttpTransport $directTransport,
    ) {
    }

    public function publish(string $destination, Message $message, array $options = []): MessageResponse
    {
        $token = (string) config('messaging.qstash.token', '');
        if ($token === '') {
            throw new \RuntimeException('UPSTASH_QSTASH_TOKEN is required when MESSAGING_DRIVER=qstash');
        }

        $baseUrl = rtrim((string) config('messaging.qstash.base_url', 'https://qstash.upstash.io'), '/');
        $publishUrl = $baseUrl . '/v2/publish/' . $destination;

        $body = $message->toJson();

        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Upstash-Method' => 'POST',
            'Upstash-Forward-Content-Type' => 'application/json',
            'Upstash-Forward-X-Sdp-Message-Type' => $message->type,
        ];
        foreach ($this->signer->headers($body) as $name => $value) {
            $headers['Upstash-Forward-' . $name] = $value;
        }

        $retries = config('messaging.qstash.retries');
        if ($retries !== null && $retries !== '') {
            $headers['Upstash-Retries'] = (string) $retries;
        }

        $timeout = isset($options['timeout']) ? (float) $options['timeout'] : 30.0;

        $response = Http::withOptions([
            'timeout' => $timeout,
            'verify' => $options['verify'] ?? true,
        ])
            ->withHeaders($headers)
            ->withBody($body, 'application/json')
            ->post($publishUrl);

        return MessageResponse::fromHttp($response);
    }

    public function request(string $destination, Message $message, array $options = []): MessageResponse
    {
        return $this->directTransport->request($destination, $message, $options);
    }
}
