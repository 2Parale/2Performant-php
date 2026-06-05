<?php

namespace TPerformant\API\Tests\Api;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use TPerformant\API\Api;

class SignInTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Returns an Api instance and populates &$capturedOptions (by reference).
     * The spy handler captures the *effective* per-request Guzzle options
     * (after client-defaults are merged) so we can assert on debug suppression.
     */
    private function createApiWithSpyHandler(array $clientHttpOptions, string $responseBody, &$capturedOptions): Api
    {
        $capturedOptions = null;

        $spyHandler = function (\Psr\Http\Message\RequestInterface $request, array $options) use (&$capturedOptions, $responseBody) {
            $capturedOptions = $options;
            return new FulfilledPromise(new Response(200, [], $responseBody));
        };

        $stack = HandlerStack::create($spyHandler);

        return new Api('https://api.2performant.com', [
            'http' => array_merge(['handler' => $stack], $clientHttpOptions),
        ]);
    }

    private function signInResponse(): string
    {
        return json_encode(['user' => ['id' => 1, 'email' => 'user@example.com', 'role' => 'affiliate']]);
    }

    // -----------------------------------------------------------------------
    // Debug suppression
    // -----------------------------------------------------------------------

    public function testSignInSuppressesDebugLoggingWhenClientHasDebugEnabled(): void
    {
        $debugStream = fopen('php://temp', 'r+');

        try {
            $api = $this->createApiWithSpyHandler(
                ['debug' => $debugStream],
                $this->signInResponse(),
                $capturedOptions
            );

            $api->signIn('user@example.com', 'secret_password');

            // The per-request debug option must be explicitly false so Guzzle
            // does not stream the request body (containing the password) to
            // whatever debug sink the consumer configured.
            $this->assertArrayHasKey('debug', $capturedOptions, 'debug key must be present in merged options');
            $this->assertFalse($capturedOptions['debug'], 'signIn must suppress debug logging to protect the password');
        } finally {
            fclose($debugStream);
        }
    }

    public function testSignInDoesNotWritePasswordToDebugStream(): void
    {
        // End-to-end check: even if guzzle somehow processes the debug option
        // before our spy sees it, nothing containing the password must appear
        // in the debug stream.
        $debugStream = fopen('php://temp', 'r+');

        try {
            $handler = function (\Psr\Http\Message\RequestInterface $request, array $options) {
                 $debug = $options['debug'] ?? false;
                 if ($debug !== false && is_resource($debug)) {
                     fwrite($debug, (string) $request->getBody());
                 }
                 return new FulfilledPromise(new Response(200, [], $this->signInResponse()));
             };
             $stack = HandlerStack::create($handler);

            $api = new Api('https://api.2performant.com', [
                'http' => ['handler' => $stack, 'debug' => $debugStream],
            ]);

            $api->signIn('user@example.com', 'ultra_secret_password');

            rewind($debugStream);
            $debugOutput = stream_get_contents($debugStream);

            $this->assertStringNotContainsString(
                'ultra_secret_password',
                $debugOutput,
                'The plaintext password must never appear in the debug log'
            );
        } finally {
            fclose($debugStream);
        }
    }

    public function testDebugIsNotSuppressedForNonSensitiveRequests(): void
    {
        // Verify that only signIn suppresses debug; other POST calls should
        // leave the client's debug setting untouched (i.e. not force false).
        $debugStream = fopen('php://temp', 'r+');

        try {
            // createAdvertiserCommission is an ordinary POST — debug should
            // NOT be forced to false.
            $api = $this->createApiWithSpyHandler(
                ['debug' => $debugStream],
                json_encode(['commission' => ['id' => 1]]),
                $capturedOptions
            );

            $auth = $this->createMock(\TPerformant\API\HTTP\Advertiser::class);
            $auth->method('getAccessToken')->willReturn('tok');
            $auth->method('getClientToken')->willReturn('cli');
            $auth->method('getUid')->willReturn('uid');

            $api->createAdvertiserCommission($auth, 99, 10.00, 'Bonus');

            // debug should still be the stream (not false) for ordinary calls
            $this->assertNotFalse(
                $capturedOptions['debug'] ?? null,
                'Non-sensitive requests must not have debug suppressed'
            );
        } finally {
            fclose($debugStream);
        }
    }

    // -----------------------------------------------------------------------
    // Basic request shape
    // -----------------------------------------------------------------------

    public function testSignInSendsPostToCorrectEndpoint(): void
    {
        $history = [];

        $mock = new \GuzzleHttp\Handler\MockHandler([
            new Response(200, [], $this->signInResponse()),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push(\GuzzleHttp\Middleware::history($history));

        $api = new Api('https://api.2performant.com', ['http' => ['handler' => $stack]]);
        $api->signIn('user@example.com', 'password123');

        $request = $history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('/users/sign_in.json', $request->getUri()->getPath());
    }

    public function testSignInSendsEmailAndPasswordInBody(): void
    {
        $history = [];

        $mock = new \GuzzleHttp\Handler\MockHandler([
            new Response(200, [], $this->signInResponse()),
        ]);
        $stack = HandlerStack::create($mock);
        $stack->push(\GuzzleHttp\Middleware::history($history));

        $api = new Api('https://api.2performant.com', ['http' => ['handler' => $stack]]);
        $api->signIn('user@example.com', 'password123');

        $body = json_decode($history[0]['request']->getBody()->getContents(), true);
        $this->assertSame('user@example.com', $body['user']['email']);
        $this->assertSame('password123', $body['user']['password']);
    }
}
