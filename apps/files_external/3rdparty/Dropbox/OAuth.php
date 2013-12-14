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
 * This class is an abstract OAuth class.
 *
 * It must be extended by classes who wish to provide OAuth functionality
 * using different libraries.
 */
abstract class Dropbox_OAuth {

    /**
     * After a user has authorized access, dropbox can redirect the user back
     * to this url.
     * 
     * @var string
     */
    public $authorizeCallbackUrl = null; 
   
    /**
     * Uri used to fetch request tokens 
     * 
     * @var string
     */
    const URI_REQUEST_TOKEN = 'https://api.dropbox.com/1/oauth/request_token';

    /**
     * Uri used to redirect the user to for authorization.
     * 
     * @var string
     */
    const URI_AUTHORIZE = 'https://www.dropbox.com/1/oauth/authorize';

    /**
     * Uri used to 
     * 
     * @var string
     */
    const URI_ACCESS_TOKEN = 'https://api.dropbox.com/1/oauth/access_token';

    /**
     * An OAuth request token. 
     * 
     * @var string 
     */
    protected $oauth_token = null;

    /**
     * OAuth token secret 
     * 
     * @var string 
     */
    protected $oauth_token_secret = null;


    /**
     * Constructor
     * 
     * @param string $consumerKey 
     * @param string $consumerSecret 
     */
    abstract public function __construct($consumerKey, $consumerSecret);

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

        if (is_array($token)) {
            $this->oauth_token = $token['token'];
            $this->oauth_token_secret = $token['token_secret'];
        } else {
            $this->oauth_token = $token;
            $this->oauth_token_secret = $token_secret;
        }

    }

    /**
     * Returns the oauth request tokens as an associative array.
     *
     * The array will contain the elements 'token' and 'token_secret'.
     * 
     * @return array 
     */
    public function getToken() {

        return array(
            'token' => $this->oauth_token,
            'token_secret' => $this->oauth_token_secret,
        );

    }

    /**
     * Returns the authorization url
     * 
     * @param string $callBack Specify a callback url to automatically redirect the user back 
     * @return string 
     */
    public function getAuthorizeUrl($callBack = null) {
        
        // Building the redirect uri
        $token = $this->getToken();
        $uri = self::URI_AUTHORIZE . '?oauth_token=' . $token['token'];
        if ($callBack) $uri.='&oauth_callback=' . $callBack;
        return $uri;
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
    public abstract function fetch($uri, $arguments = array(), $method = 'GET', $httpHeaders = array()); 

    /**
     * Requests the OAuth request token.
     * 
     * @return array 
     */
    abstract public function getRequestToken(); 

    /**
     * Requests the OAuth access tokens.
     *
     * @return array
     */
    abstract public function getAccessToken(); 

}
