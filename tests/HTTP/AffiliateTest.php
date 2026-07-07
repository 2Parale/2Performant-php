<?php

namespace TPerformant\API\Tests\HTTP;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use TPerformant\API\Api;
use TPerformant\API\Exception\APIException;
use TPerformant\API\HTTP\Affiliate;
use TPerformant\API\HTTP\SavedSession;
use TPerformant\API\Model\AffiliateAdvertiserPromotion;
use TPerformant\API\Model\AffiliateProgram;
use TPerformant\API\Model\Affrequest;
use TPerformant\API\Model\Banner;
use TPerformant\API\Model\Commission;
use TPerformant\API\Model\Product;
use TPerformant\API\Model\ProductFeed;

class AffiliateTest extends TestCase
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

    private function createAffiliate(
        string $access = 'tok',
        string $client = 'cli',
        string $uid    = 'uid@test.com'
    ): Affiliate {
        $affiliate = $this->getMockBuilder(Affiliate::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getRole'])
            ->getMock();
        $affiliate->method('getRole')->willReturn('affiliate');
        $affiliate->updateAuthTokens(new SavedSession($access, $client, $uid));
        return $affiliate;
    }

    // --- Constructor ---

    public function testConstructorThrowsWhenRoleIsNotAffiliate(): void
    {
        $this->initApiWithMockHttp([
            new Response(200, [], json_encode([
                'user' => ['id' => 1, 'role' => 'advertiser', 'email' => 'adv@test.com'],
            ])),
        ]);

        $this->expectException(APIException::class);
        $this->expectExceptionMessage('not an affiliate');

        new Affiliate(new SavedSession('tok', 'cli', 'uid@test.com'));
    }

    // --- getQuicklink ---

    public function testGetQuicklinkDelegatesToApiWithUserDataAndProgram(): void
    {
        $this->initApiWithMockHttp([]);

        $affiliate = $this->createAffiliate();

        $modelAffiliate = new \TPerformant\API\Model\Affiliate(
            (object)['id' => 1, 'role' => 'affiliate', 'unique_code' => 'aff-123']
        );
        $reflection = new \ReflectionProperty(\TPerformant\API\HTTP\User::class, 'userData');
        $reflection->setValue($affiliate, $modelAffiliate);

        $url = $affiliate->getQuicklink('https://shop.example.com/page', 'prog-xyz');

        $this->assertIsString($url);
        $this->assertStringContainsString('ad_type=quicklink', $url);
        $this->assertStringContainsString('aff_code=aff-123', $url);
        $this->assertStringContainsString('unique=prog-xyz', $url);
        $this->assertStringContainsString('redirect_to=', $url);
    }

    // --- getPrograms ---

    public function testGetProgramsReturnsArrayOfAffiliateProgramModels(): void
    {
        $this->initApiWithMockHttp([
            new Response(200, [], json_encode([
                'programs' => [
                    ['id' => 1, 'name' => 'Program One', 'slug' => 'prog-one'],
                ],
            ])),
        ]);

        $result = $this->createAffiliate()->getPrograms();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(AffiliateProgram::class, $result[0]);
        $this->assertSame('Program One', $result[0]->getName());
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

        $affiliate = $this->createAffiliate('old-tok', 'old-cli', 'old@uid.com');
        $affiliate->getPrograms();

        $this->assertSame('new-access', $affiliate->getAccessToken());
        $this->assertSame('new-client', $affiliate->getClientToken());
        $this->assertSame('new@uid.com', $affiliate->getUid());
    }

    // --- getProgram ---

    public function testGetProgramReturnsSingleAffiliateProgramModel(): void
    {
        $this->initApiWithMockHttp([
            new Response(200, [], json_encode([
                'program' => ['id' => 5, 'name' => 'My Program', 'slug' => 'my-prog'],
            ])),
        ]);

        $result = $this->createAffiliate()->getProgram(5);

        $this->assertInstanceOf(AffiliateProgram::class, $result);
        $this->assertSame('My Program', $result->getName());
    }

    // --- getRequest ---

    public function testGetRequestReturnsSingleAffrequestModel(): void
    {
        $this->initApiWithMockHttp([
            new Response(200, [], json_encode([
                'affrequest' => ['id' => 7, 'status' => 'accepted'],
            ])),
        ]);

        $result = $this->createAffiliate()->getRequest(5);

        $this->assertInstanceOf(Affrequest::class, $result);
        $this->assertSame('accepted', $result->getStatus());
    }

    // --- getCommissions ---

    public function testGetCommissionsReturnsArrayOfCommissionModels(): void
    {
        $this->initApiWithMockHttp([
            new Response(200, [], json_encode([
                'commissions' => [
                    ['id' => 10, 'amount' => '50.00', 'status' => 'accepted'],
                    ['id' => 11, 'amount' => '25.00', 'status' => 'pending'],
                ],
            ])),
        ]);

        $result = $this->createAffiliate()->getCommissions();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(Commission::class, $result[0]);
    }

    // --- getProductFeeds ---

    public function testGetProductFeedsReturnsArrayOfProductFeedModels(): void
    {
        $this->initApiWithMockHttp([
            new Response(200, [], json_encode([
                'product_feeds' => [
                    ['id' => 3, 'name' => 'Feed One'],
                ],
            ])),
        ]);

        $result = $this->createAffiliate()->getProductFeeds();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(ProductFeed::class, $result[0]);
    }

    // --- getProducts ---

    public function testGetProductsReturnsArrayOfProductModels(): void
    {
        $this->initApiWithMockHttp([
            new Response(200, [], json_encode([
                'products' => [
                    ['id' => 20, 'title' => 'Widget A'],
                ],
            ])),
        ]);

        $result = $this->createAffiliate()->getProducts(3);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Product::class, $result[0]);
    }

    // --- getBanners ---

    public function testGetBannersReturnsArrayOfBannerModels(): void
    {
        $this->initApiWithMockHttp([
            new Response(200, [], json_encode([
                'banners' => [
                    ['id' => 2, 'unique_code' => 'bn-001'],
                ],
            ])),
        ]);

        $result = $this->createAffiliate()->getBanners();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Banner::class, $result[0]);
    }

    // --- getPromotions ---

    public function testGetPromotionsReturnsArrayOfAffiliateAdvertiserPromotionModels(): void
    {
        $this->initApiWithMockHttp([
            new Response(200, [], json_encode([
                'advertiser_promotions' => [
                    ['id' => 4, 'name' => 'Winter Promo'],
                ],
            ])),
        ]);

        $result = $this->createAffiliate()->getPromotions();

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(AffiliateAdvertiserPromotion::class, $result[0]);
    }
}
