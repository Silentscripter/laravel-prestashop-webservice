<?php

namespace Protechstudio\PrestashopWebService;


use Illuminate\Support\Facades\Facade;
use PrestaShopWebservice;

class PrestashopWebServiceFacade extends Facade
{

    protected static function getFacadeAccessor()
    {
        return PrestaShopWebservice::class;
    }

}