<?php

namespace TPerformant\API\Tests\HTTP;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use TPerformant\API\Api;
use TPerformant\API\Exception\APIException;
use TPerformant\API\HTTP\Advertiser;
use TPerformant\API\HTTP\SavedSession;
use TPerformant\API\Model\AdvertiserCommission;
use TPerformant\API\Model\Program;
use TPerformant\API\Model\Sale;

class AdvertiserTest extends TestCase
{
    private array $requestHistory = [];

    private function initApiWithMockHttp(array $responses): void
    {
        $this->requestHistory = [];
        $mock  = new MockHandler($responses);
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::history($this->requestHistory));
        Api::init('https://api.2performant.com', ['http' => ['handler' => $stack]]);
    }

    private function createAdvertiser(
        string $access = 'tok',
        string $client = 'cli',
        string $uid    = 'uid@test.com'
    ): Advertiser {
        $advertiser = $this->getMockBuilder(Advertiser::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRole'])
            ->getMock();
        $advertiser->method('getRole')->willReturn('advertiser');
        $advertiser->updateAuthTokens(new SavedSession($access, $client, $uid));
        return $advertiser;
    }

    private function commissionBody(int $id = 10): string
    {
        return json_encode([
            'commission' => ['id' => $id, 'amount' => '50.00', 'status' => 'accepted'],
        ]);
    }

    // --- Constructor ---

    public function testConstructorThrowsWhenRoleIsNotAdvertiser(): void
    {
        $this->initApiWithMockHttp([
            new Response(200, [], json_encode([
                'user' => ['id' => 1, 'role' => 'affiliate', 'email' => 'aff@test.com'],
            ])),
        ]);

        $this->expectException(APIException::class);
        $this->expectExceptionMessage('not belong to an advertiser');

        new Advertiser(new SavedSession('tok', 'cli', 'uid@test.com'));
    }

    // --- getPrograms ---

    public function testGetProgramsReturnsArrayOfProgramModels(): void
    {
        $this->initApiWithMockHttp([
            new Response(200, [], json_encode([
                'programs' => [
                    ['id' => 1, 'name' => 'My Program', 'slug' => 'my-prog'],
                    ['id' => 2, 'name' => 'Other Program', 'slug' => 'other-prog'],
                ],
            ])),
        ]);

        $result = $this->createAdvertiser()->getPrograms();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(Program::class, $result[0]);
        $this->assertSame('My Program', $result[0]->getName());
    }

    public function testGetProgramsRefreshesAuthTokensFromResponseHeaders(): void
    {
        $this->initApiWithMockHttp([
            new Response(200, [
                'access-token' => 'new-access',
                'client'       => 'new-client',
                'uid'          => 'new@uid.com',
            ], json_encode(['programs' => [['id' => 1]]])),
        ]);

        $advertiser = $this->createAdvertiser('old-tok', 'old-cli', 'old@uid.com');
        $advertiser->getPrograms();

        $this->assertSame('new-access', $advertiser->getAccessToken());
        $this->assertSame('new-client', $advertiser->getClientToken());
        $this->assertSame('new@uid.com', $advertiser->getUid());
    }

    // --- getProgram ---

    public function testGetProgramReturnsSingleProgramModel(): void
    {
        $this->initApiWithMockHttp([
            new Response(200, [], json_encode([
                'program' => ['id' => 7, 'name' => 'Target Program', 'slug' => 'target'],
            ])),
        ]);

        $result = $this->createAdvertiser()->getProgram(7);

        $this->assertInstanceOf(Program::class, $result);
        $this->assertSame('Target Program', $result->getName());
    }

    // --- getMyProgram ---

    /**
     * getMyProgram() is `return $this->getProgram('default')`.
     * Since getProgram() is already tested, we only need to verify
     * that the literal string 'default' is used as the program ID.
     */
    public function testGetMyProgramPassesDefaultAsId(): void
    {
        $this->initApiWithMockHttp([
            new Response(200, [], json_encode([
                'program' => ['id' => 1, 'slug' => 'default', 'unique_code' => 'uc-abc'],
            ])),
        ]);

        $this->createAdvertiser()->getMyProgram();

        $this->assertCount(1, $this->requestHistory);
        $path = $this->requestHistory[0]['request']->getUri()->getPath();
        $this->assertStringContainsString('default', $path);
    }

    // --- getCommissions ---

    public function testGetCommissionsReturnsArrayOfAdvertiserCommissionModels(): void
    {
        $this->initApiWithMockHttp([
            new Response(200, [], json_encode([
                'commissions' => [
                    ['id' => 10, 'amount' => '50.00', 'status' => 'accepted'],
                    ['id' => 11, 'amount' => '30.00', 'status' => 'pending'],
                ],
            ])),
        ]);

        $result = $this->createAdvertiser()->getCommissions();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(AdvertiserCommission::class, $result[0]);
    }

    // --- getCommission ---

    public function testGetCommissionReturnsSingleAdvertiserCommissionModel(): void
    {
        $this->initApiWithMockHttp([new Response(200, [], $this->commissionBody(10))]);

        $result = $this->createAdvertiser()->getCommission(10);

        $this->assertInstanceOf(AdvertiserCommission::class, $result);
        $this->assertSame(10, $result->getId());
    }

    // --- getTransaction ---

    /**
     * getTransaction() calls getCommissions() with a pre-built
     * AdvertiserCommissionFilter. Since getCommissions() is already tested,
     * we verify only the novel behaviour: that the transactionId filter is
     * applied, which shows up in the query string.
     */
    public function testGetTransactionPassesTransactionIdAsFilter(): void
    {
        $this->initApiWithMockHttp([
            new Response(200, [], json_encode([
                'commissions' => [['id' => 10, 'amount' => '50.00']],
            ])),
        ]);

        $result = $this->createAdvertiser()->getTransaction('TX-001');

        $this->assertIsArray($result);
        $this->assertInstanceOf(AdvertiserCommission::class, $result[0]);

        $this->assertCount(1, $this->requestHistory);
        $query = urldecode($this->requestHistory[0]['request']->getUri()->getQuery());
        $this->assertStringContainsString('transaction_id', $query);
        $this->assertStringContainsString('TX-001', $query);
    }

    // --- editCommission ---

    public function testEditCommissionReturnsSingleAdvertiserCommission(): void
    {
        $this->initApiWithMockHttp([new Response(200, [], $this->commissionBody(5))]);

        $result = $this->createAdvertiser()->editCommission(5, 'Correction', 120.0);

        $this->assertInstanceOf(AdvertiserCommission::class, $result);
        $this->assertSame(5, $result->getId());
    }

    // --- acceptCommission ---

    public function testAcceptCommissionReturnsSingleAdvertiserCommission(): void
    {
        $this->initApiWithMockHttp([new Response(200, [], $this->commissionBody(6))]);

        $result = $this->createAdvertiser()->acceptCommission(6, 'All good');

        $this->assertInstanceOf(AdvertiserCommission::class, $result);
        $this->assertSame(6, $result->getId());
    }

    // --- rejectCommission ---

    public function testRejectCommissionReturnsSingleAdvertiserCommission(): void
    {
        $this->initApiWithMockHttp([new Response(200, [], $this->commissionBody(7))]);

        $result = $this->createAdvertiser()->rejectCommission(7, 'Duplicate order');

        $this->assertInstanceOf(AdvertiserCommission::class, $result);
        $this->assertSame(7, $result->getId());
    }

    // --- updateSaleCommission ---

    public function testUpdateSaleCommissionReturnsSaleModel(): void
    {
        $this->initApiWithMockHttp([
            new Response(200, [], json_encode([
                'sale' => ['id' => 20, 'amount' => '199.99', 'currency_code' => 'EUR'],
            ])),
        ]);

        $result = $this->createAdvertiser()->updateSaleCommission(20, '199.99', 'EUR', 'Price adjustment');

        $this->assertInstanceOf(Sale::class, $result);
        $this->assertSame(20, $result->getId());
    }

    // --- getQuicklink ---

    /**
     * Advertiser::getQuicklink() resolves the own program via getMyProgram()
     * (one HTTP call) and then passes the program's unique_code to
     * Api::getQuicklink(), which performs only string manipulation.
     * We verify that the returned URL encodes both the affiliate code
     * and the program's unique_code.
     */
    public function testGetQuicklinkDelegatesToApiWithOwnProgram(): void
    {
        $this->initApiWithMockHttp([
            new Response(200, [], json_encode([
                'program' => ['id' => 1, 'unique_code' => 'prog-abc', 'slug' => 'default'],
            ])),
        ]);

        $url = $this->createAdvertiser()->getQuicklink('https://shop.example.com/page', 'aff-xyz');

        $this->assertIsString($url);
        $this->assertStringContainsString('ad_type=quicklink', $url);
        $this->assertStringContainsString('aff_code=aff-xyz', $url);
        $this->assertStringContainsString('unique=prog-abc', $url);
        $this->assertStringContainsString('redirect_to=', $url);
    }
}
