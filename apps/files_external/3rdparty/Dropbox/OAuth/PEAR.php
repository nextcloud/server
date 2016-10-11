<?php

/**
 * Dropbox OAuth
 * 
 * @package Dropbox 
 * @copyright Copyright (C) 2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/dropbox-php/wiki/License MIT
 */

if (!class_exists('HTTP_OAuth_Consumer')) {

    // We're going to try to load in manually
    include 'HTTP/OAuth/Consumer.php';

}
if (!class_exists('HTTP_OAuth_Consumer')) 
    throw new Dropbox_Exception('The HTTP_OAuth_Consumer class could not be found! Did you install the pear HTTP_OAUTH class?');

/**
 * This class is used to sign all requests to dropbox
 * 
 * This classes use the PEAR HTTP_OAuth package. Make sure this is installed.
 */
class Dropbox_OAuth_PEAR extends Dropbox_OAuth {

    /**
     * OAuth object
     *
     * @var OAuth
     */
    protected $oAuth;

    /**
     * OAuth consumer key
     * 
     * We need to keep this around for later. 
     * 
     * @var string 
     */
    protected $consumerKey;

    /**
     * Constructor
     * 
     * @param string $consumerKey 
     * @param string $consumerSecret 
     */
    public function __construct($consumerKey, $consumerSecret)
    {
        $this->OAuth = new Dropbox_OAuth_Consumer_Dropbox($consumerKey, $consumerSecret);
        $this->consumerKey = $consumerKey;
    }

    /**
     * Sets the request token and secret.
     *
     * The tokens can also be passed as an array into the first argument.
     * The array must have the elements token and token_secret.
     * 
     * @param string|array $token 
     * @param string $token_secret 
     * @return void
     */
    public function setToken($token, $token_secret = null) {

        parent::setToken($token,$token_secret);
        $this->OAuth->setToken($this->oauth_token);
        $this->OAuth->setTokenSecret($this->oauth_token_secret);

    }

    /**
     * Fetches a secured oauth url and returns the response body. 
     * 
     * @param string $uri 
     * @param mixed $arguments 
     * @param string $method 
     * @param array $httpHeaders 
     * @return string 
     */
    public function fetch($uri, $arguments = array(), $method = 'GET', $httpHeaders = array())
    {
        $httpRequest = new HTTP_Request2(null, 
                HTTP_Request2::METHOD_GET, 
                array(
                    'ssl_verify_peer' => false, 
                    'ssl_verify_host' => false
                )
        );

        $consumerRequest = new HTTP_OAuth_Consumer_Request();
        $consumerRequest->accept($httpRequest);
        $consumerRequest->setUrl($uri);
        $consumerRequest->setMethod($method);
        $consumerRequest->setSecrets($this->OAuth->getSecrets());
     
        $parameters = array(
            'oauth_consumer_key'     => $this->consumerKey,
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_token'            => $this->oauth_token,
        );


        if (is_array($arguments)) {
            $parameters = array_merge($parameters,$arguments);
        } elseif (is_string($arguments)) {
            $consumerRequest->setBody($arguments);
        }
        $consumerRequest->setParameters($parameters);


        if (count($httpHeaders)) {
            foreach($httpHeaders as $k=>$v) {
                $consumerRequest->setHeader($k, $v);
            }
        }

        $response = $consumerRequest->send();

        switch($response->getStatus()) {

              // Not modified
            case 304 :
                return array(
                    'httpStatus' => 304,
                    'body'       => null,
                );
                break;
            case 400 :
                throw new Dropbox_Exception_Forbidden('Forbidden. Bad input parameter. Error message should indicate which one and why.');
            case 401 :
                throw new Dropbox_Exception_Forbidden('Forbidden. Bad or expired token. This can happen if the user or Dropbox revoked or expired an access token. To fix, you should re-authenticate the user.');
            case 403 :
                throw new Dropbox_Exception_Forbidden('Forbidden. This could mean a bad OAuth request, or a file or folder already existing at the target location.');
            case 404 : 
                throw new Dropbox_Exception_NotFound('Resource at uri: ' . $uri . ' could not be found');
            case 405 : 
                throw new Dropbox_Exception_Forbidden('Forbidden. Request method not expected (generally should be GET or POST).');
            case 500 :
                throw new Dropbox_Exception_Forbidden('Server error. ' . $e->getMessage());
            case 503 : 
                throw new Dropbox_Exception_Forbidden('Forbidden. Your app is making too many requests and is being rate limited. 503s can trigger on a per-app or per-user basis.');
            case 507 : 
                throw new Dropbox_Exception_OverQuota('This dropbox is full');
                
        }

        return array(
            'httpStatus' => $response->getStatus(),
            'body' => $response->getBody()
        );

    }

    /**
     * Requests the OAuth request token.
     * 
     * @return void
     */
    public function getRequestToken() {
        
        $this->OAuth->getRequestToken(self::URI_REQUEST_TOKEN);
        $this->setToken($this->OAuth->getToken(), $this->OAuth->getTokenSecret());
        return $this->getToken();

    }

    /**
     * Requests the OAuth access tokens.
     *
     * This method requires the 'unauthorized' request tokens
     * and, if successful will set the authorized request tokens.
     * 
     * @return void 
     */
    public function getAccessToken() {

        $this->OAuth->getAccessToken(self::URI_ACCESS_TOKEN);
        $this->setToken($this->OAuth->getToken(), $this->OAuth->getTokenSecret());
        return $this->getToken();

    }


}
