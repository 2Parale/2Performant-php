<?php

namespace TPerformant\API\Tests\Api;

use PHPUnit\Framework\TestCase;
use TPerformant\API\Api;
use TPerformant\API\Model\Affiliate as AffiliateModel;
use TPerformant\API\Model\Program as ProgramModel;

class GetQuicklinkTest extends TestCase
{
    // --- Host replacement logic ---

    public function testReplacesApiSubdomainWithEventOnDefaultHost(): void
    {
        $api = new Api('https://api.2performant.com');

        $url = $api->getQuicklink('https://example.com', 'aff1', 'prog1');

        $this->assertStringStartsWith('https://event.2performant.com/', $url);
    }

    public function testReplacesApiSubdomainWithEventOnStagingHost(): void
    {
        $api = new Api('https://api.staging.2performant.com');

        $url = $api->getQuicklink('https://example.com', 'aff1', 'prog1');

        $this->assertStringStartsWith('https://event.staging.2performant.com/', $url);
    }

    public function testDoesNotAlterNonApiSubdomains(): void
    {
        $api = new Api('https://api.2performant.com');

        $url = $api->getQuicklink('https://example.com', 'aff1', 'prog1');

        $this->assertStringNotContainsString('api.2performant.com', $url);
    }

    // --- URL assembly ---

    public function testGeneratesCorrectClickEventPath(): void
    {
        $api = new Api('https://api.2performant.com');

        $url = $api->getQuicklink('https://example.com', 'aff1', 'prog1');

        $this->assertStringContainsString('/events/click', $url);
    }

    public function testIncludesAdTypeQuicklink(): void
    {
        $api = new Api('https://api.2performant.com');

        $url = $api->getQuicklink('https://example.com', 'aff1', 'prog1');

        $this->assertStringContainsString('ad_type=quicklink', $url);
    }

    public function testIncludesAffCodeParam(): void
    {
        $api = new Api('https://api.2performant.com');

        $url = $api->getQuicklink('https://example.com', 'my-affiliate', 'prog1');

        $this->assertStringContainsString('aff_code=my-affiliate', $url);
    }

    public function testIncludesUniqueParam(): void
    {
        $api = new Api('https://api.2performant.com');

        $url = $api->getQuicklink('https://example.com', 'aff1', 'my-program');

        $this->assertStringContainsString('unique=my-program', $url);
    }

    public function testIncludesUrlEncodedRedirectTo(): void
    {
        $api = new Api('https://api.2performant.com');
        $destination = 'https://example.com/path?foo=bar&baz=qux';

        $url = $api->getQuicklink($destination, 'aff1', 'prog1');

        $this->assertStringContainsString('redirect_to=' . urlencode($destination), $url);
    }

    public function testUrlEncodesAffiliateCodeWithSpecialChars(): void
    {
        $api = new Api('https://api.2performant.com');

        $url = $api->getQuicklink('https://example.com', 'aff code+1', 'prog1');

        $this->assertStringContainsString('aff_code=' . urlencode('aff code+1'), $url);
    }

    // --- Affiliate: object vs string ---

    public function testAcceptsStringAffiliate(): void
    {
        $api = new Api('https://api.2performant.com');

        $url = $api->getQuicklink('https://example.com', 'string-aff-code', 'prog1');

        $this->assertStringContainsString('aff_code=string-aff-code', $url);
    }

    public function testExtractsUniqueCodeFromAffiliateModelObject(): void
    {
        $api = new Api('https://api.2performant.com');

        $data = new \stdClass();
        $data->unique_code = 'model-aff-unique';
        $affiliate = new AffiliateModel($data);

        $url = $api->getQuicklink('https://example.com', $affiliate, 'prog1');

        $this->assertStringContainsString('aff_code=model-aff-unique', $url);
    }

    // --- Program: object vs string ---

    public function testAcceptsStringProgram(): void
    {
        $api = new Api('https://api.2performant.com');

        $url = $api->getQuicklink('https://example.com', 'aff1', 'string-prog-code');

        $this->assertStringContainsString('unique=string-prog-code', $url);
    }

    public function testExtractsUniqueCodeFromProgramModelObject(): void
    {
        $api = new Api('https://api.2performant.com');

        $data = new \stdClass();
        $data->unique_code = 'model-prog-unique';
        $program = new ProgramModel($data);

        $url = $api->getQuicklink('https://example.com', 'aff1', $program);

        $this->assertStringContainsString('unique=model-prog-unique', $url);
    }

    public function testAcceptsBothAffiliateAndProgramAsObjects(): void
    {
        $api = new Api('https://api.2performant.com');

        $affData = new \stdClass();
        $affData->unique_code = 'aff-obj-code';
        $affiliate = new AffiliateModel($affData);

        $progData = new \stdClass();
        $progData->unique_code = 'prog-obj-code';
        $program = new ProgramModel($progData);

        $url = $api->getQuicklink('https://example.com', $affiliate, $program);

        $this->assertStringContainsString('aff_code=aff-obj-code', $url);
        $this->assertStringContainsString('unique=prog-obj-code', $url);
    }
}
