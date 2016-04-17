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

PS Underlying library usage
---------------------------

You may find complete documentation and tutorials regarding Prestashop Web Service Library in the [Prestashop Documentation](http://doc.prestashop.com/display/PS16/Using+the+PrestaShop+Web+Service).