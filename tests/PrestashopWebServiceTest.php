<?php

namespace Protechstudio\PrestashopWebService\Tests;

use Protechstudio\PrestashopWebService\PrestashopWebService;
use Protechstudio\PrestashopWebService\Exceptions\PrestashopWebServiceException;
use Protechstudio\PrestashopWebService\Exceptions\PrestashopWebServiceRequestException;
use Protechstudio\PrestashopWebService\PrestashopWebServiceLibrary;
use Prestashop;

class PrestashopWebServiceTest extends TestCase
{
    /** @test */
    public function it_is_correctly_installed()
    {
        $this->assertInstanceOf(PrestashopWebService::class, Prestashop::getFacadeRoot());
    }
    
    /** @test */
    public function test_request_is_correct()
    {
        $requestResponseStub = require(__DIR__.'/requests/category-schema.php');

         list($header, $body) = explode("\n\n", $requestResponseStub[0], 2);
         $header_size = strlen($header) + 2;

         $this->assertEquals($header_size, $requestResponseStub[1]['header_size']);
    }

    /** @test */
    public function it_can_perform_a_get_request()
    {
        $requestResponseStub = require(__DIR__.'/requests/category-schema.php');
        $ps = $this->getMockedLibrary('executeCurl', $requestResponseStub);

        $xml = $ps->get(['resource' => 'categories']);
        
        $this->assertEquals('prestashop', $xml->getName());
        $this->assertEquals('category', $xml->children()[0]->getName());
    }

    /** @test */
    public function it_throws_exception_on_404()
    {
        $ps = $this->getMockedLibrary('executeRequest', [
                'status_code' => 404,
                'response' => '<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
<errors>
<error>
<code><![CDATA[1]]></code>
<message><![CDATA[Invalid ID]]></message>
</error>
</errors>
</prestashop>',
                'header' => ''
            ]);

        try {
            $xml = $ps->get(['resource' => 'categories']);
        } catch (PrestashopWebServiceRequestException $exception) {
            $this->assertEquals(404, $exception->getCode());
            $this->assertTrue($exception->hasResponse());
            $this->assertEquals('Invalid ID', (string)$exception->getResponse()->errors->error->message);
        }
    }

    /** @test */
    public function it_throws_exception_on_unknown_http_status()
    {
        $ps = $this->getMockedLibrary('executeRequest', [
                'status_code' => 999,
                'response' => '<?xml version="1.0" encoding="UTF-8"?>
<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
</prestashop>',
                'header' => ''
            ]);

        $this->expectExceptionMessage('unexpected HTTP status of: 999', PrestashopWebServiceException::class);
        $xml = $ps->get(['resource' => 'categories']);
    }

    /** @test */
    public function it_throws_exception_on_empty_response()
    {
        $ps = $this->getMockedLibrary('executeRequest', [
                'status_code' => 200,
                'response' => '',
                'header' => ''
            ]);

        $this->expectExceptionMessage('HTTP response is empty', PrestashopWebServiceException::class);
        $xml = $ps->get(['resource' => 'categories']);
    }

    /** @test */
    public function it_throws_exception_on_malformed_xml()
    {
        $ps = $this->getMockedLibrary('executeRequest', [
                'status_code' => 200,
                'response' => '<malformed>',
                'header' => ''
            ]);

        $this->expectExceptionMessage('HTTP XML response is not parsable', PrestashopWebServiceException::class);
        $xml = $ps->get(['resource' => 'categories']);
    }

    /** @test */
    public function it_throws_exception_on_unsupported_version()
    {
        $this->expectExceptionMessage('This library is not compatible with this version of PrestaShop', PrestashopWebServiceException::class);
        Prestashop::isPrestashopVersionSupported('0.0.0.0');
        Prestashop::isPrestashopVersionSupported('99.99.99.9999');
    }

    /** @test */
    public function it_throws_exception_on_unsupported_version_from_request()
    {
        $requestResponseStub = require(__DIR__.'/requests/category-schema.php');
        $requestResponseStub[0] = preg_replace('/^PSWS-Version:(.+?)$/im', 'PSWS-Version: 99.99.99.9999', $requestResponseStub[0]);
        $ps = $this->getMockedLibrary('executeCurl', $requestResponseStub);

        $this->expectExceptionMessage('This library is not compatible with this version of PrestaShop', PrestashopWebServiceException::class);
        $xml = $ps->get(['resource' => 'categories']);
    }

    protected function getMockedLibrary($method = null, $returns = null)
    {
        $ps = $this->getMockBuilder(PrestashopWebServiceLibrary::class)
            ->setConstructorArgs([
                env('prestashop-webservice.url'),
                env('prestashop-webservice.key'),
                env('prestashop-webservice.debug'),
            ]);

        if (!$method) {
            return $ps->getMock();
        } else {
            $ps = $ps->setMethods([$method])->getMock();

            $ps->expects($this->once())
                ->method($method)
                ->willReturn($returns);
            return $ps;
        }
    }
}

