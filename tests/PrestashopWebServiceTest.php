<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PrestashopWebServiceTest extends TestCase
{

    /**
     * @test
     */
    public function it_is_correctly_installed()
    {
        $this->assertInstanceOf(PrestaShopWebservice::class, Prestashop::getFacadeRoot());
    }
}
