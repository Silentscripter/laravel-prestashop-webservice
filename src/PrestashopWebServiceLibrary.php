<?php

namespace Protechstudio\PrestashopWebService;

use Protechstudio\PrestashopWebService\Exceptions\PrestashopWebServiceException;
use Protechstudio\PrestashopWebService\Exceptions\PrestashopWebServiceRequestException;
use SimpleXMLElement;

/**
 * @package PrestaShopWebService
 */
class PrestashopWebServiceLibrary
{

    /** @var string Shop URL */
    protected $url;

    /** @var string Authentification key */
    protected $key;

    /** @var boolean is debug activated */
    protected $debug;

    /** @var string PS version */
    protected $version;

    /** @var boolean Are we running in a console */
    protected $runningInConsole;

    /** @var array compatible versions of PrestaShop WebService */
    const PS_COMPATIBLE_VERSION_MIN = '1.4.0.0';
    const PS_COMPATIBLE_VERSION_MAX = '1.7.99.99';

    /**
     * PrestaShopWebService constructor. Throw an exception when CURL is not installed/activated
     * <code>
     * <?php
     * require_once('./PrestaShopWebService.php');
     * try
     * {
     *    $ws = new PrestaShopWebService('http://mystore.com/', 'ZQ88PRJX5VWQHCWE4EE7SQ7HPNX00RAJ', false);
     *    // Now we have a WebService object to play with
     * }
     * catch (PrestashopWebServiceException $ex)
     * {
     *    echo 'Error : '.$ex->getMessage();
     * }
     * ?>
     * </code>
     * @param string $url Root URL for the shop
     * @param string $key Authentification key
     * @param mixed $debug Debug mode Activated (true) or deactivated (false)
     * @throws PrestashopWebServiceException
     */
    public function __construct($url, $key, $debug = true)
    {
        if (!extension_loaded('curl')) {
            $exception = 'Please activate the PHP extension \'curl\' to allow use of PrestaShop WebService library';
            throw new PrestashopWebServiceException($exception);
        }

        $this->url = $url;
        $this->key = $key;
        $this->debug = $debug;
        $this->version = 'unknown';
        
        $this->runningInConsole = app()->runningInConsole();
    }

    /**
     * Take the status code and throw an exception if the server didn't return 200 or 201 code
     * @param int $status_code Status code of an HTTP return
     * @return boolean
     * @throws PrestashopWebServiceException
     */
    protected function checkRequest($request)
    {
        if ($request['status_code'] === 200 || $request['status_code'] === 201) {
            return true;
        }

        $messages = array(
            204 => 'No content',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
        );

        if (isset($messages[$request['status_code']])) {
            $xml = null;
            if ($request['response']) {
                $xml = $this->parseXML($request['response'], true);
            }

            throw new PrestashopWebServiceRequestException($messages[$request['status_code']], $request['status_code'], $xml);
        } else {
            $exception = 'This call to PrestaShop Web Services returned an unexpected HTTP status of: ';
            $exception.= $request['status_code'];
            throw new PrestashopWebServiceException($exception);
        }
    }

    /**
     * Throws exception if prestashop version is not supported
     * @param int $version The prestashop version
     * @throws PrestashopWebServiceException
     */
    public function isPrestashopVersionSupported($version)
    {
        if (version_compare($version, self::PS_COMPATIBLE_VERSION_MIN, '>=') === false ||
            version_compare($version, self::PS_COMPATIBLE_VERSION_MAX, '<=') === false
        ) {
            $exception = 'This library is not compatible with this version of PrestaShop. ';
            $exception.= 'Please upgrade/downgrade this library';
            throw new PrestashopWebServiceException($exception);
        }
    }

    /**
     * Prepares and validate a CURL request to PrestaShop WebService. Can throw exception.
     * @param string $url Resource name
     * @param mixed $curl_params CURL parameters (sent to curl_set_opt)
     * @return array status_code, response
     * @throws PrestashopWebServiceException
     */
    protected function executeRequest($url, $curl_params = array())
    {
        $defaultParams = array(
            CURLOPT_HEADER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $this->key.':',
            CURLOPT_HTTPHEADER => array( 'Expect:' ),
            CURLOPT_SSL_VERIFYPEER => config('app.env') === 'local' ? 0 : 1,
            CURLOPT_SSL_VERIFYHOST => config('app.env') === 'local' ? 0 : 2 // value 1 is not accepted https://curl.haxx.se/libcurl/c/CURLOPT_SSL_VERIFYHOST.html
        );

        $curl_options = array();
        foreach ($defaultParams as $defkey => $defval) {
            if (isset($curl_params[$defkey])) {
                $curl_options[$defkey] = $curl_params[$defkey];
            } else {
                $curl_options[$defkey] = $defaultParams[$defkey];
            }
        }
        foreach ($curl_params as $defkey => $defval) {
            if (!isset($curl_options[$defkey])) {
                $curl_options[$defkey] = $curl_params[$defkey];
            }
        }

        list($response, $info, $error) = $this->executeCurl($url, $curl_options);

        $status_code = $info['http_code'];
        if ($status_code === 0 || $error) {
            throw new PrestashopWebServiceException('CURL Error: '.$error);
        }

        $index = $info['header_size'];
        if ($index === false && $curl_params[CURLOPT_CUSTOMREQUEST] !== 'HEAD') {
            throw new PrestashopWebServiceException('Bad HTTP response');
        }

        $header = substr($response, 0, $index);
        $body = substr($response, $index);

        $headerArray = array();
        foreach (explode("\n", $header) as $headerItem) {
            $tmp = explode(':', $headerItem, 2);
            if (count($tmp) === 2) {
                $tmp = array_map('trim', $tmp);
                $headerArray[$tmp[0]] = $tmp[1];
            }
        }

        if (array_key_exists('PSWS-Version', $headerArray)) {
            $this->isPrestashopVersionSupported($headerArray['PSWS-Version']);
            $this->version = $headerArray['PSWS-Version'];
        }

        $this->printDebug('HTTP REQUEST HEADER', $info['request_header']);
        $this->printDebug('HTTP RESPONSE HEADER', $header);

        if ($curl_params[CURLOPT_CUSTOMREQUEST] == 'PUT' || $curl_params[CURLOPT_CUSTOMREQUEST] == 'POST') {
            $this->printDebug('XML SENT', urldecode($curl_params[CURLOPT_POSTFIELDS]));
        }
        if ($curl_params[CURLOPT_CUSTOMREQUEST] != 'DELETE' && $curl_params[CURLOPT_CUSTOMREQUEST] != 'HEAD') {
            $this->printDebug('RETURN HTTP BODY', $body);
        }

        return array(
            'status_code' => $status_code,
            'response' => $body,
            'header' => $header,
            'headers' => $headerArray
            );
    }

    /**
     * Executes the CURL request to PrestaShop WebService.
     * @param string $url Resource name
     * @param mixed $options CURL parameters (sent to curl_setopt_array)
     * @return array response, info
     */
    protected function executeCurl($url, array $options = array())
    {
        $session = curl_init($url);

        if (count($options)) {
            curl_setopt_array($session, $options);
        }

        $response = curl_exec($session);

        $error = false;
        $info = curl_getinfo($session);
        if ($response === false) {
            $error = curl_error($session);
        }

        curl_close($session);

        return array($response, $info, $error);
    }

    public function printDebug($title, $content)
    {
        if ($this->debug) {
            if ($this->runningInConsole) {
                echo 'START '.$title."\n";
                echo $content . "\n";
                echo 'END '.$title."\n";
                echo "\n";
            } else {
                echo '<div style="display:table;background:#CCC;font-size:8pt;padding:7px">';
                echo '<h6 style="font-size:9pt;margin:0">'.$title.'</h6>';
                echo '<pre>'.htmlentities($content).'</pre>';
                echo '</div>';
            }
        }
    }

    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Load XML from string. Can throw exception
     * @param string $response String from a CURL response
     * @param boolean $suppressExceptions Whether to throw exceptions on errors
     * @return SimpleXMLElement status_code, response
     * @throws PrestashopWebServiceException
     */
    protected function parseXML($response, $suppressExceptions = false)
    {
        if ($response != '') {
            libxml_clear_errors();
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA);
            if (libxml_get_errors()) {
                $msg = var_export(libxml_get_errors(), true);
                libxml_clear_errors();

                if (!$suppressExceptions) {
                    throw new PrestashopWebServiceException('HTTP XML response is not parsable: '.$msg);
                }
            }

            return $xml;
        } elseif (!$suppressExceptions) {
            throw new PrestashopWebServiceException('HTTP response is empty');
        }

        return null;
    }

    /**
     * Add (POST) a resource
     * <p>Unique parameter must take : <br><br>
     * 'resource' => Resource name<br>
     * 'postXml' => Full XML string to add resource<br><br>
     * Examples are given in the tutorial</p>
     * @param array $options
     * @return SimpleXMLElement status_code, response
     * @throws PrestashopWebServiceException
     */
    public function add($options)
    {
        $xml = '';

        if (isset($options['resource'], $options['postXml']) || isset($options['url'], $options['postXml'])) {
            $url = (isset($options['resource']) ? $this->url.'/api/'.$options['resource'] : $options['url']);
            $xml = $options['postXml'];
            if (isset($options['id_shop'])) {
                $url .= '&id_shop='.$options['id_shop'];
            }
            if (isset($options['id_group_shop'])) {
                $url .= '&id_group_shop='.$options['id_group_shop'];
            }
        } else {
            throw new PrestashopWebServiceException('Bad parameters given');
        }
        $request = $this->executeRequest($url, array(CURLOPT_CUSTOMREQUEST => 'POST', CURLOPT_POSTFIELDS => $xml));

        $this->checkRequest($request);
        return $this->parseXML($request['response']);
    }

    /**
     * Retrieve (GET) a resource
     * <p>Unique parameter must take : <br><br>
     * 'url' => Full URL for a GET request of WebService (ex: http://mystore.com/api/customers/1/)<br>
     * OR<br>
     * 'resource' => Resource name,<br>
     * 'id' => ID of a resource you want to get<br><br>
     * </p>
     * <code>
     * <?php
     * require_once('./PrestaShopWebService.php');
     * try
     * {
     * $ws = new PrestaShopWebService('http://mystore.com/', 'ZQ88PRJX5VWQHCWE4EE7SQ7HPNX00RAJ', false);
     * $xml = $ws->get(array('resource' => 'orders', 'id' => 1));
     *    // Here in $xml, a SimpleXMLElement object you can parse
     * foreach ($xml->children()->children() as $attName => $attValue)
     *    echo $attName.' = '.$attValue.'<br />';
     * }
     * catch (PrestashopWebServiceException $ex)
     * {
     *    echo 'Error : '.$ex->getMessage();
     * }
     * ?>
     * </code>
     * @param array $options Array representing resource to get.
     * @return SimpleXMLElement status_code, response
     * @throws PrestashopWebServiceException
     */
    public function get($options)
    {
        if (isset($options['url'])) {
            $url = $options['url'];
        } elseif (isset($options['resource'])) {
            $url = $this->url.'/api/'.$options['resource'];
            $url_params = array();
            if (isset($options['id'])) {
                $url .= '/'.$options['id'];
            }

            $params = array('filter', 'display', 'sort', 'limit', 'id_shop', 'id_group_shop','date', 'price');
            foreach ($params as $p) {
                foreach ($options as $k => $o) {
                    if (strpos($k, $p) !== false) {
                        $url_params[$k] = $options[$k];
                    }
                }
            }
            if (count($url_params) > 0) {
                $url .= '?'.http_build_query($url_params);
            }
        } else {
            throw new PrestashopWebServiceException('Bad parameters given');
        }

        $request = $this->executeRequest($url, array(CURLOPT_CUSTOMREQUEST => 'GET'));

        $this->checkRequest($request);// check the response validity
        return $this->parseXML($request['response']);
    }

    /**
     * Head method (HEAD) a resource
     *
     * @param array $options Array representing resource for head request.
     * @return SimpleXMLElement status_code, response
     * @throws PrestashopWebServiceException
     */
    public function head($options)
    {
        if (isset($options['url'])) {
            $url = $options['url'];
        } elseif (isset($options['resource'])) {
            $url = $this->url.'/api/'.$options['resource'];
            $url_params = array();
            if (isset($options['id'])) {
                $url .= '/'.$options['id'];
            }

            $params = array('filter', 'display', 'sort', 'limit');
            foreach ($params as $p) {
                foreach ($options as $k => $o) {
                    if (strpos($k, $p) !== false) {
                        $url_params[$k] = $options[$k];
                    }
                }
            }
            if (count($url_params) > 0) {
                $url .= '?'.http_build_query($url_params);
            }
        } else {
            throw new PrestashopWebServiceException('Bad parameters given');
        }
        $request = $this->executeRequest($url, array(CURLOPT_CUSTOMREQUEST => 'HEAD', CURLOPT_NOBODY => true));
        $this->checkRequest($request);// check the response validity
        return $request['header'];
    }

    /**
     * Edit (PUT) a resource
     * <p>Unique parameter must take : <br><br>
     * 'resource' => Resource name ,<br>
     * 'id' => ID of a resource you want to edit,<br>
     * 'putXml' => Modified XML string of a resource<br><br>
     * Examples are given in the tutorial</p>
     * @param array $options Array representing resource to edit.
     * @return SimpleXMLElement
     * @throws PrestashopWebServiceException
     */
    public function edit($options)
    {
        $xml = '';
        if (isset($options['url'])) {
            $url = $options['url'];
        } elseif ((isset($options['resource'], $options['id']) || isset($options['url'])) && $options['putXml']) {
            if (isset($options['url'])) {
                $url = $options['url'];
            } else {
                $url = $this->url.'/api/'.$options['resource'].'/'.$options['id'];
            }
            $xml = $options['putXml'];
            if (isset($options['id_shop'])) {
                $url .= '&id_shop='.$options['id_shop'];
            }
            if (isset($options['id_group_shop'])) {
                $url .= '&id_group_shop='.$options['id_group_shop'];
            }
        } else {
            throw new PrestashopWebServiceException('Bad parameters given');
        }

        $request = $this->executeRequest($url, array(CURLOPT_CUSTOMREQUEST => 'PUT', CURLOPT_POSTFIELDS => $xml));
        $this->checkRequest($request);// check the response validity
        return $this->parseXML($request['response']);
    }

    /**
     * Delete (DELETE) a resource.
     * Unique parameter must take : <br><br>
     * 'resource' => Resource name<br>
     * 'id' => ID or array which contains IDs of a resource(s) you want to delete<br><br>
     * <code>
     * <?php
     * require_once('./PrestaShopWebService.php');
     * try
     * {
     * $ws = new PrestaShopWebService('http://mystore.com/', 'ZQ88PRJX5VWQHCWE4EE7SQ7HPNX00RAJ', false);
     * $xml = $ws->delete(array('resource' => 'orders', 'id' => 1));
     *    // Following code will not be executed if an exception is thrown.
     *    echo 'Successfully deleted.';
     * }
     * catch (PrestashopWebServiceException $ex)
     * {
     *    echo 'Error : '.$ex->getMessage();
     * }
     * ?>
     * </code>
     * @param array $options Array representing resource to delete.
     * @return bool
     */
    public function delete($options)
    {
        if (isset($options['url'])) {
            $url = $options['url'];
        } elseif (isset($options['resource']) && isset($options['id'])) {
            if (is_array($options['id'])) {
                $url = $this->url.'/api/'.$options['resource'].'/?id=['.implode(',', $options['id']).']';
            } else {
                $url = $this->url.'/api/'.$options['resource'].'/'.$options['id'];
            }
        }
        if (isset($options['id_shop'])) {
            $url .= '&id_shop='.$options['id_shop'];
        }
        if (isset($options['id_group_shop'])) {
            $url .= '&id_group_shop='.$options['id_group_shop'];
        }
        $request = $this->executeRequest($url, array(CURLOPT_CUSTOMREQUEST => 'DELETE'));
        $this->checkRequest($request);// check the response validity
        return true;
    }
}
