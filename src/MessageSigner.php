<?php

namespace Iankibet\Messaging;

use Illuminate\Http\Request;

/**
 * Signs and verifies messages using the shared HMAC scheme already understood by
 * both projects: X-Sdp-Timestamp + X-Sdp-Signature where the signature is
 * hash_hmac('sha256', "{timestamp}.{rawBody}", secret). Kept wire-compatible with
 * the existing ModuleBridgeController validation so flows can migrate gradually.
 */
class MessageSigner
{
    public const HEADER_TIMESTAMP = 'X-Sdp-Timestamp';
    public const HEADER_SIGNATURE = 'X-Sdp-Signature';

    public function secret(): string
    {
        return (string) config('messaging.secret', '');
    }

    /**
     * Build signature headers for an exact raw body string.
     *
     * @return array<string,string>
     */
    public function headers(string $body): array
    {
        $secret = $this->secret();
        if ($secret === '') {
            throw new \RuntimeException('Messaging secret is not configured.');
        }

        $timestamp = (string) now()->timestamp;

        return [
            self::HEADER_TIMESTAMP => $timestamp,
            self::HEADER_SIGNATURE => hash_hmac('sha256', $timestamp . '.' . $body, $secret),
        ];
    }

    public function verify(string $timestamp, string $signature, string $body): bool
    {
        if ($timestamp === '' || $signature === '') {
            return false;
        }

        $ttl = (int) config('messaging.signature_ttl', 300);
        if ($ttl > 0 && abs(now()->timestamp - (int) $timestamp) > $ttl) {
            return false;
        }

        $secret = $this->secret();
        if ($secret === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $body, $secret);

        return hash_equals($expected, $signature);
    }

    public function verifyRequest(Request $request): bool
    {
        return $this->verify(
            (string) $request->header(self::HEADER_TIMESTAMP, ''),
            (string) $request->header(self::HEADER_SIGNATURE, ''),
            $request->getContent(),
        );
    }
}
