<?php

namespace TPerformant\API\Tests\HTTP;

use PHPUnit\Framework\TestCase;
use TPerformant\API\HTTP\AuthInterface;
use TPerformant\API\HTTP\SavedSession;

class SavedSessionTest extends TestCase {

    // --- Constructor stores tokens ---

    public function testConstructorStoresAllThreeTokens(): void
    {
        $session = new SavedSession('access-tok', 'client-tok', 'user@example.com');

        $this->assertSame('access-tok',      $session->getAccessToken());
        $this->assertSame('client-tok',      $session->getClientToken());
        $this->assertSame('user@example.com', $session->getUid());
    }

    // --- Getters return exact values ---

    public function testGetAccessTokenReturnsExactValue(): void
    {
        $expected = 'abc123-access-token-value';
        $session  = new SavedSession($expected, 'c', 'u');

        $this->assertSame($expected, $session->getAccessToken());
    }

    public function testGetClientTokenReturnsExactValue(): void
    {
        $expected = 'xyz789-client-token-value';
        $session  = new SavedSession('a', $expected, 'u');

        $this->assertSame($expected, $session->getClientToken());
    }

    public function testGetUidReturnsExactValue(): void
    {
        $expected = 'unique@user.id.com';
        $session  = new SavedSession('a', 'c', $expected);

        $this->assertSame($expected, $session->getUid());
    }

    // --- Contract ---

    public function testImplementsAuthInterface(): void
    {
        $session = new SavedSession('a', 'b', 'c');

        $this->assertInstanceOf(AuthInterface::class, $session);
    }

    public function testTwoInstancesDoNotShareState(): void
    {
        $s1 = new SavedSession('tok-1', 'cli-1', 'uid-1');
        $s2 = new SavedSession('tok-2', 'cli-2', 'uid-2');

        $this->assertSame('tok-1', $s1->getAccessToken());
        $this->assertSame('tok-2', $s2->getAccessToken());
        $this->assertNotSame($s1->getAccessToken(), $s2->getAccessToken());
    }
}
