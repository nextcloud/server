<?php

/**
 * Dropbox OAuth
 * 
 * @package Dropbox 
 * @copyright Copyright (C) 2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/dropbox-php/wiki/License MIT
 */


/**
 * This class is used to sign all requests to dropbox.
 *
 * This specific class uses the PHP OAuth extension
 */
class Dropbox_OAuth_PHP extends Dropbox_OAuth {

    /**
     * OAuth object
     *
     * @var OAuth
     */
    protected $oAuth;

    /**
     * Constructor
     * 
     * @param string $consumerKey 
     * @param string $consumerSecret 
     */
    public function __construct($consumerKey, $consumerSecret) {

        if (!class_exists('OAuth')) 
            throw new Dropbox_Exception('The OAuth class could not be found! Did you install and enable the oauth extension?');

        $this->OAuth = new OAuth($consumerKey, $consumerSecret,OAUTH_SIG_METHOD_HMACSHA1,OAUTH_AUTH_TYPE_URI);
        $this->OAuth->enableDebug();

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
        $this->OAuth->setToken($this->oauth_token, $this->oauth_token_secret);

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

        try { 
            $this->OAuth->fetch($uri, $arguments, $method, $httpHeaders);
            $result = $this->OAuth->getLastResponse();
            $lastResponseInfo = $this->OAuth->getLastResponseInfo();
            return array(
                'httpStatus' => $lastResponseInfo['http_code'],
                'body'       => $result,
            );
        } catch (OAuthException $e) {

            $lastResponseInfo = $this->OAuth->getLastResponseInfo();
            switch($lastResponseInfo['http_code']) {

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
                default:
                    // rethrowing
                    throw $e;
            }

        }

    }

    /**
     * Requests the OAuth request token.
     *
     * @return void 
     */
    public function getRequestToken() {
        
        try {

            $tokens = $this->OAuth->getRequestToken(self::URI_REQUEST_TOKEN);
            $this->setToken($tokens['oauth_token'], $tokens['oauth_token_secret']);
            return $this->getToken();

        } catch (OAuthException $e) {

            throw new Dropbox_Exception_RequestToken('We were unable to fetch request tokens. This likely means that your consumer key and/or secret are incorrect.',0,$e);

        }

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

        $uri = self::URI_ACCESS_TOKEN;
        $tokens = $this->OAuth->getAccessToken($uri);
        $this->setToken($tokens['oauth_token'], $tokens['oauth_token_secret']);
        return $this->getToken();

    }


}
