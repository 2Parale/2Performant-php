<?php

namespace TPerformant\API\Tests\HTTP;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use TPerformant\API\Api;
use TPerformant\API\HTTP\ApiResponse;
use TPerformant\API\HTTP\AuthInterface;
use TPerformant\API\HTTP\SavedSession;
use TPerformant\API\Model\Affiliate as ModelAffiliate;

/**
 * Concrete User subclass used for tests that need a real (no-op) constructor.
 * Exposes the protected updateAuthTokensFromResponse() method for inspection.
 */
class TestableUser extends \TPerformant\API\HTTP\User {
    public function __construct($email = '', $password = '') {}

    public function callUpdateAuthTokensFromResponse(\Psr\Http\Message\ResponseInterface $response): void
    {
        $this->updateAuthTokensFromResponse($response);
    }
}

/**
 * Concrete User subclass that inherits the real User constructor verbatim,
 * without the role assertion added by HTTP\Affiliate / HTTP\Advertiser.
 * Used to exercise the SavedSession construction path in isolation.
 */
class PlainUser extends \TPerformant\API\HTTP\User {}

class UserTest extends TestCase {

    private function initApiWithMockHttp(array $responses): void
    {
        $mock = new MockHandler($responses);
        Api::init('https://api.2performant.com', [
            'http' => ['handler' => HandlerStack::create($mock)],
        ]);
    }

    // --- updateAuthTokens() ---

    public function testUpdateAuthTokensSetsAllThreeTokensFromSavedSession(): void
    {
        $user = $this->getMockBuilder(\TPerformant\API\HTTP\Affiliate::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $user->updateAuthTokens(new SavedSession('acc-111', 'cli-222', 'uid-333'));

        $this->assertSame('acc-111', $user->getAccessToken());
        $this->assertSame('cli-222', $user->getClientToken());
        $this->assertSame('uid-333', $user->getUid());
    }

    public function testUpdateAuthTokensAcceptsAnyAuthInterface(): void
    {
        $user = $this->getMockBuilder(\TPerformant\API\HTTP\Affiliate::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $source = $this->createMock(AuthInterface::class);
        $source->method('getAccessToken')->willReturn('dyn-access');
        $source->method('getClientToken')->willReturn('dyn-client');
        $source->method('getUid')->willReturn('dyn-uid');

        $user->updateAuthTokens($source);

        $this->assertSame('dyn-access', $user->getAccessToken());
        $this->assertSame('dyn-client', $user->getClientToken());
        $this->assertSame('dyn-uid',    $user->getUid());
    }

    // --- updateAuthTokensAndReturn() ---

    public function testUpdateAuthTokensAndReturnUpdatesTokensAndReturnsBody(): void
    {
        $user = $this->getMockBuilder(\TPerformant\API\HTTP\Affiliate::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $expectedBody = (object)['id' => 42, 'name' => 'Test Resource'];

        $apiResponse = $this->getMockBuilder(ApiResponse::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAccessToken', 'getClientToken', 'getUid', 'getBody'])
            ->getMock();
        $apiResponse->method('getAccessToken')->willReturn('resp-access');
        $apiResponse->method('getClientToken')->willReturn('resp-client');
        $apiResponse->method('getUid')->willReturn('resp-uid');
        $apiResponse->method('getBody')->willReturn($expectedBody);

        $result = $user->updateAuthTokensAndReturn($apiResponse);

        $this->assertSame('resp-access', $user->getAccessToken());
        $this->assertSame('resp-client', $user->getClientToken());
        $this->assertSame('resp-uid',    $user->getUid());
        $this->assertSame($expectedBody, $result);
    }

    // --- updateAuthTokensFromResponse() ---

    public function testUpdateAuthTokensFromResponseExtractsAllThreeHeadersFromPsr7Response(): void
    {
        $user = new TestableUser();
        $user->updateAuthTokens(new SavedSession('old-a', 'old-c', 'old-u'));

        $psr7Response = new Response(200, [
            'access-token' => 'new-access',
            'client'       => 'new-client',
            'uid'          => 'new@uid.com',
        ], '');

        $user->callUpdateAuthTokensFromResponse($psr7Response);

        $this->assertSame('new-access', $user->getAccessToken());
        $this->assertSame('new-client', $user->getClientToken());
        $this->assertSame('new@uid.com', $user->getUid());
    }

    public function testUpdateAuthTokensFromResponseFallsBackToExistingTokensWhenHeadersAbsent(): void
    {
        $user = new TestableUser();
        $user->updateAuthTokens(new SavedSession('keep-a', 'keep-c', 'keep-u'));

        $psr7Response = new Response(200, [], ''); // no auth headers

        $user->callUpdateAuthTokensFromResponse($psr7Response);

        $this->assertSame('keep-a', $user->getAccessToken());
        $this->assertSame('keep-c', $user->getClientToken());
        $this->assertSame('keep-u', $user->getUid());
    }

    public function testUpdateAuthTokensFromResponseOverridesOnlyPresentHeaders(): void
    {
        $user = new TestableUser();
        $user->updateAuthTokens(new SavedSession('orig-a', 'orig-c', 'orig-u'));

        // Only client header is refreshed; access-token and uid are absent.
        $psr7Response = new Response(200, [
            'client' => 'refreshed-client',
        ], '');

        $user->callUpdateAuthTokensFromResponse($psr7Response);

        $this->assertSame('orig-a',           $user->getAccessToken());
        $this->assertSame('refreshed-client', $user->getClientToken());
        $this->assertSame('orig-u',           $user->getUid());
    }

    // --- getRole() ---

    public function testGetRoleDelegatesToInternalUserDataModel(): void
    {
        $user = $this->getMockBuilder(\TPerformant\API\HTTP\Affiliate::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $modelUser = new \TPerformant\API\Model\Advertiser(
            (object)['role' => 'advertiser', 'id' => 5, 'email' => 'adv@example.com']
        );

        $reflection = new \ReflectionProperty(\TPerformant\API\HTTP\User::class, 'userData');
        $reflection->setValue($user, $modelUser);

        $this->assertSame('advertiser', $user->getRole());
    }

    // --- Constructor with SavedSession ---

    public function testConstructorWithSavedSessionCallsValidateTokenAndPopulatesUserData(): void
    {
        $this->initApiWithMockHttp([
            new Response(200, [], json_encode([
                'user' => [
                    'id'    => 7,
                    'role'  => 'affiliate',
                    'email' => 'aff@example.com',
                ],
            ])),
        ]);

        $session = new SavedSession('tok-aaa', 'cli-bbb', 'uid@example.com');
        $user    = new PlainUser($session);

        // Tokens come from the SavedSession, not the response headers
        $this->assertSame('tok-aaa',          $user->getAccessToken());
        $this->assertSame('cli-bbb',          $user->getClientToken());
        $this->assertSame('uid@example.com',  $user->getUid());

        // userData is the model object hydrated from the validateToken response body
        $this->assertInstanceOf(ModelAffiliate::class, $user->getUserData());
        $this->assertSame('affiliate', $user->getRole());
    }
}
