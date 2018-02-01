<?php

namespace Protechstudio\PrestashopWebService\Exceptions;

class PrestashopWebServiceRequestException extends PrestashopWebServiceException
{
    static protected $label = 'This call to PrestaShop Web Services failed and returned an HTTP status of %d. That means: %s.';

    protected $response;

    public function __construct($message = null, $code = null, $response = null)
    {
        parent::__construct(sprintf(static::$label, $code, $message), $code);

        $this->response = $response;
    }

    public function hasResponse()
    {
        return isset($this->response) && !empty($this->response);
    }

    public function getResponse()
    {
        return $this->response;
    }
}
