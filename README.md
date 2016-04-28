Laravel Prestashop Web Service
========

Laravel 5 wrapper for Prestashop Web Service Library

Installation
------------

Require this package with composer using the following command:

```shell
composer require protechstudio/laravel-prestashop-webservice
```

After updating composer, add the service provider to the `providers` array in `config/app.php`

```php
Protechstudio\PrestashopWebService\PrestashopWebServiceProvider::class,
```

You may also add the Facade in the `aliases` array in `config/app.php`

```php
'Prestashop' => Protechstudio\PrestashopWebService\PrestashopWebServiceFacade::class,
```

Finally publish the configuration file using the artisan command

```shell
php artisan vendor:publish --provider="Protechstudio\PrestashopWebService\PrestashopWebServiceProvider"
```

Configuration
-------------

Open the published configuration file at `config/prestashop-webservice.php`:

```php
return [
    'url' => 'http://domain.com',
    'token' => '',
    'debug' => env('APP_DEBUG', false)
];
```

Then populate the `url` field with the **root url** of the targeted Prestashop installation and `token` field with the API token obtained from Prestashop control panel in Web Service section. If `debug` is `true` Prestashop will return debug information when responding to API requests.

Usage
-----

You may use the Prestashop Web Service wrapper in two ways:
### Using the dependency or method injection

```php
...
use Protechstudio\PrestashopWebService\PrestashopWebService;

class FooController extends Controller
{
    private $prestashop;
    
    public function __construct(PrestashopWebService $prestashop)
    {
        $this->prestashop = $prestashop;
    }
    
    public function bar()
    {
        $opt['resource'] = 'customers';
        $xml=$this->prestashop->get($opt);
    }
}
```
### Using the Facade

```php
...
use Prestashop;

...

public function bar()
{   
    $opt['resource'] = 'customers';
    $xml=Prestashop::get($opt);
}
```

Prestashop Underlying library usage
---------------------------

You may find complete documentation and tutorials regarding Prestashop Web Service Library in the [Prestashop Documentation](http://doc.prestashop.com/display/PS16/Using+the+PrestaShop+Web+Service).

Helper methods
--------------

I've added some helper methods to reduce development time:

### Retrieving resource schema and filling data for posting

You may call `getSchema()` method to retrieve the requested resource schema. You may then fill the schema with an associative array of data with `fillSchema()` method.

```php

$xmlSchema=Prestashop::getSchema('categories'); //returns a SimpleXMLElement instance with the desired schema

$data=[
    'name'=>'Clothes',
    'link_rewrite'=>'clothes',
    'active'=>true
];

$postXml=Prestashop::fillSchema($xmlSchema,$data); 

//The xml is now ready for being sent back to the web service to create a new category

$response=Prestashop::add(['resource'=>'categories','postXml'=>$postXml->asXml()]);

```

#### Note for language value handling

If the node has a language child you may use a simple string for the value if your shop has only one language installed.

```php
/*
    xml node with one language child example
    ...
    <name>
    <language id="1"/>
    </name>
    ...
*/
$data= ['name'=>Clothes'];
```

If your shops has more than one language installed you may pass the node value as an array where the key is the language ID.

```php
/*
    xml node with n language children example 
    ...
    <name>
    <language id="1"/>
    <language id="2"/>
    </name>
    ... 
*/
$data= [
    'name'=>[
        1 => 'Clothes',
        2 => 'Abbigliamento
    ]
];
```
_Please note that if you don't provide an array of values keyed by the language ID all language values will have the same value._