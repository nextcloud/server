<?php

/**
 * Dropbox OAuth
 *
 * @package Dropbox
 * @copyright Copyright (C) 2010 Rooftop Solutions. All rights reserved.
 * @author Michael Johansen <michael@taskcamp.com>
 * @license http://code.google.com/p/dropbox-php/wiki/License MIT
 */

/**
 * This class is used to sign all requests to dropbox
 *
 * This classes use the Zend_Oauth package.
 */
class Dropbox_OAuth_Zend extends Dropbox_OAuth {

    /**
     * OAuth object
     *
     * @var Zend_Oauth_Consumer
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
     *
     * @var Zend_Oauth_Token
     */
    protected $zend_oauth_token;

    /**
     * Constructor
     *
     * @param string $consumerKey
     * @param string $consumerSecret
     */
    public function __construct($consumerKey, $consumerSecret) {
        if (!class_exists('Zend_Oauth_Consumer')) {
            // We're going to try to load in manually
            include 'Zend/Oauth/Consumer.php';
        }
        if (!class_exists('Zend_Oauth_Consumer'))
            throw new Dropbox_Exception('The Zend_Oauth_Consumer class could not be found!');
        $this->OAuth = new Zend_Oauth_Consumer(array(
                    "consumerKey" => $consumerKey,
                    "consumerSecret" => $consumerSecret,
                    "requestTokenUrl" => self::URI_REQUEST_TOKEN,
                    "accessTokenUrl" => self::URI_ACCESS_TOKEN,
                    "authorizeUrl" => self::URI_AUTHORIZE,
                    "signatureMethod" => "HMAC-SHA1",
                ));
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
        if (is_a($token, "Zend_Oauth_Token")) {
            if (is_a($token, "Zend_Oauth_Token_Access")) {
                $this->OAuth->setToken($token);
            }
            $this->zend_oauth_token = $token;
            return parent::setToken($token->getToken(), $token->getTokenSecret());
        } elseif (is_string($token) && is_null($token_secret)) {
            return $this->setToken(unserialize($token));
        } elseif (isset($token['zend_oauth_token'])) {
            return $this->setToken(unserialize($token['zend_oauth_token']));
        } else {
            parent::setToken($token, $token_secret);
            return;
        }
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
    public function fetch($uri, $arguments = array(), $method = 'GET', $httpHeaders = array()) {
        $token = $this->OAuth->getToken();
        if (!is_a($token, "Zend_Oauth_Token")) {
            if (is_a($this->zend_oauth_token, "Zend_Oauth_Token_Access")) {
                $token = $this->zend_oauth_token;
            } else {
                $token = new Zend_Oauth_Token_Access();
                $token->setToken($this->oauth_token);
                $token->setTokenSecret($this->oauth_token_secret);
            }
        }
        /* @var $token Zend_Oauth_Token_Access */
        $oauthOptions = array(
            'consumerKey' => $this->consumerKey,
            'signatureMethod' => "HMAC-SHA1",
            'consumerSecret' => $this->OAuth->getConsumerSecret(),
        );
        $config = array("timeout" => 15);

        /* @var $consumerRequest Zend_Oauth_Client */
        $consumerRequest = $token->getHttpClient($oauthOptions);
        $consumerRequest->setMethod($method);
        if (is_array($arguments)) {
            $consumerRequest->setUri($uri);
            if ($method == "GET") {
                foreach ($arguments as $param => $value) {
                    $consumerRequest->setParameterGet($param, $value);
                }
            } else {
                foreach ($arguments as $param => $value) {
                    $consumerRequest->setParameterPost($param, $value);
                }
            }
        } elseif (is_string($arguments)) {
            preg_match("/\?file=(.*)$/i", $uri, $matches);
            if (isset($matches[1])) {
                $uri = str_replace($matches[0], "", $uri);
                $filename = $matches[1];
                $uri = Zend_Uri::factory($uri);
                $uri->addReplaceQueryParameters(array("file" => $filename));
                $consumerRequest->setParameterGet("file", $filename);
            }
            $consumerRequest->setUri($uri);
            $consumerRequest->setRawData($arguments);
        } elseif (is_resource($arguments)) {
            $consumerRequest->setUri($uri);
            /** Placeholder for Oauth streaming support. */
        }
        if (count($httpHeaders)) {
            foreach ($httpHeaders as $k => $v) {
                $consumerRequest->setHeaders($k, $v);
            }
        }
        $response = $consumerRequest->request();
        $body = Zend_Json::decode($response->getBody());
        switch ($response->getStatus()) {
            // Not modified
            case 304 :
                return array(
                    'httpStatus' => 304,
                    'body' => null,
                );
                break;
            case 403 :
                throw new Dropbox_Exception_Forbidden('Forbidden.
                    This could mean a bad OAuth request, or a file or folder already existing at the target location.
                    ' . $body["error"] . "\n");
            case 404 :
                throw new Dropbox_Exception_NotFound('Resource at uri: ' . $uri . ' could not be found. ' .
                        $body["error"] . "\n");
            case 507 :
                throw new Dropbox_Exception_OverQuota('This dropbox is full. ' .
                        $body["error"] . "\n");
        }

        return array(
            'httpStatus' => $response->getStatus(),
            'body' => $response->getBody(),
        );
    }

    /**
     * Requests the OAuth request token.
     *
     * @return void
     */
    public function getRequestToken() {
        $token = $this->OAuth->getRequestToken();
        $this->setToken($token);
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
        if (is_a($this->zend_oauth_token, "Zend_Oauth_Token_Request")) {
            $requestToken = $this->zend_oauth_token;
        } else {
            $requestToken = new Zend_Oauth_Token_Request();
            $requestToken->setToken($this->oauth_token);
            $requestToken->setTokenSecret($this->oauth_token_secret);
        }
        $token = $this->OAuth->getAccessToken($_GET, $requestToken);
        $this->setToken($token);
        return $this->getToken();
    }

    /**
     * Returns the oauth request tokens as an associative array.
     *
     * The array will contain the elements 'token' and 'token_secret' and the serialized
     * Zend_Oauth_Token object.
     *
     * @return array
     */
    public function getToken() {
        //$token = $this->OAuth->getToken();
        //return serialize($token);
        return array(
            'token' => $this->oauth_token,
            'token_secret' => $this->oauth_token_secret,
            'zend_oauth_token' => serialize($this->zend_oauth_token),
        );
    }

    /**
     * Returns the authorization url
     *
     * Overloading Dropbox_OAuth to use the built in functions in Zend_Oauth
     *
     * @param string $callBack Specify a callback url to automatically redirect the user back
     * @return string
     */
    public function getAuthorizeUrl($callBack = null) {
        if ($callBack)
            $this->OAuth->setCallbackUrl($callBack);
        return $this->OAuth->getRedirectUrl();
    }

}
