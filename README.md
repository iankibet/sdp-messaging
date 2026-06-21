# iankibet/sdp-messaging

A small, transport-agnostic messaging layer for SDP services. Today it ships a
QStash (Upstash) transport and a signed direct-HTTP transport; the same interface
can later back Kafka or Redis pub-sub without touching call sites.

- **Sender** — one `MessageSender` for every outbound message (`send()` async,
  `request()` synchronous request/reply).
- **Receiver** — a `MessageDispatcher` routes an incoming `Message` to a handler
  by type. The HTTP ingress (`POST /api/messaging/receive`) is auto-registered.
- **Signing** — HMAC (`X-Sdp-Timestamp` + `X-Sdp-Signature`) over the raw body,
  shared by both ends.

## Install

Distributed as a Composer `path` repository alongside the consuming apps:

```jsonc
// composer.json
"repositories": [
    { "type": "path", "url": "../sdp-messaging" }
],
"require": {
    "iankibet/sdp-messaging": "^1.0"
}
```

```bash
composer update iankibet/sdp-messaging
```

The service provider is auto-discovered. Publish the config to register handlers:

```bash
php artisan vendor:publish --tag=messaging-config
```

## Configuration

Driven by env (`MESSAGING_DRIVER`, `MESSAGING_SECRET` — defaults to `JWT_SECRET`,
`UPSTASH_QSTASH_*`). Register inbound handlers in `config/messaging.php`:

```php
'handlers' => [
    'reset-page' => \App\Messaging\Handlers\ResetPageHandler::class,
],
```

## Sending

```php
use Iankibet\Messaging\MessageSender;

// async fire-and-forget (honours MESSAGING_DRIVER)
app(MessageSender::class)->send($destinationUrl, 'reset-page', $payload);

// synchronous request/reply (always a signed blocking HTTP round-trip;
// async drivers cannot return a value, so request() falls back to direct HTTP)
$response = app(MessageSender::class)->request($endpoint, 'module.ecommerce.login', $payload);
$token = $response->json('exchange_token');
```

## Receiving

Implement `Iankibet\Messaging\Contracts\MessageHandler` and register it by type:

```php
use Iankibet\Messaging\Contracts\MessageHandler;
use Iankibet\Messaging\Message;

class ResetPageHandler implements MessageHandler
{
    public function handle(Message $message)
    {
        // ... return an array (sync reply body) or null (fire-and-forget)
    }
}
```

## Adding a transport

Implement `Iankibet\Messaging\Contracts\MessageTransport` (`publish` + `request`),
add a `match` arm in `MessagingServiceProvider`, and point `MESSAGING_DRIVER` at it.
For brokers, implement `publish`; `request` should fall back to HTTP or throw.
