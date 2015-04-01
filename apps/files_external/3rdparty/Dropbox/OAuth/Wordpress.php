<?php

/**
 * Dropbox OAuth
 * 
 * @package Dropbox 
 * @copyright Copyright (C) 2010 Stefan Motz
 * @author Stefan Motz (http://www.multimediamotz.de/)
 * @license MIT
 */

/**
 * This class is used to sign all requests to dropbox.
 *
 * This specific class uses WordPress WP_Http to authenticate.
 */
class Dropbox_OAuth_Wordpress extends Dropbox_OAuth {

    /**
     *
     * @var string ConsumerKey
     */
    protected $consumerKey = null;
    /**
     *
     * @var string ConsumerSecret
     */
    protected $consumerSecret = null;

    /**
     * Constructor
     * 
     * @param string $consumerKey 
     * @param string $consumerSecret 
     */
    public function __construct($consumerKey, $consumerSecret) {
        if (!(defined('ABSPATH') && defined('WPINC')))
            throw new Dropbox_Exception('The Wordpress OAuth class is available within a wordpress context only!');
        if (!class_exists('WP_Http')) {
            include_once( ABSPATH . WPINC . '/class-http.php' );
        }

        $this->consumerKey = $consumerKey;
        $this->consumerSecret = $consumerSecret;
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

        $requestParams = array();

        $requestParams['method'] = $method;
        $oAuthHeader = $this->getOAuthHeader($uri, $arguments, $method);
        $requestParams['headers'] = array_merge($httpHeaders, $oAuthHeader);

        // arguments will be passed to uri for GET, to body for POST etc.
        if ($method == 'GET') {
            $uri .= '?' . http_build_query($arguments);
        } else {
            if (count($arguments)) {
                $requestParams['body'] = $arguments;
            }
        }

        $request = new WP_Http;

        //$uri = str_replace('api.dropbox.com', 'localhost:12346', $uri);

        $result = $request->request($uri, $requestParams);

        return array(
            'httpStatus' => $result['response']['code'],
            'body' => $result['body'],
        );
    }

    /**
     * Returns named array with oauth parameters for further use
     * @return array Array with oauth_ parameters
     */
    private function getOAuthBaseParams() {
        $params['oauth_version'] = '1.0';
        $params['oauth_signature_method'] = 'HMAC-SHA1';

        $params['oauth_consumer_key'] = $this->consumerKey;
        $tokens = $this->getToken();
        if (isset($tokens['token']) && $tokens['token']) {
            $params['oauth_token'] = $tokens['token'];
        }
        $params['oauth_timestamp'] = time();
        $params['oauth_nonce'] = md5(microtime() . mt_rand());
        return $params;
    }

    /**
     * Creates valid Authorization header for OAuth, based on URI and Params
     *
     * @param string $uri
     * @param array $params
     * @param string $method GET or POST, standard is GET
     * @param array $oAuthParams optional, pass your own oauth_params here
     * @return array Array for request's headers section like
     * array('Authorization' => 'OAuth ...');
     */
    private function getOAuthHeader($uri, $params, $method = 'GET', $oAuthParams = null) {
        $oAuthParams = $oAuthParams ? $oAuthParams : $this->getOAuthBaseParams();

        // create baseString to encode for the sent parameters
        $baseString = $method . '&';
        $baseString .= $this->oauth_urlencode($uri) . "&";

        // OAuth header does not include GET-Parameters
        $signatureParams = array_merge($params, $oAuthParams);

        // sorting the parameters
        ksort($signatureParams);

        $encodedParams = array();
        foreach ($signatureParams as $key => $value) {
            $encodedParams[] = $this->oauth_urlencode($key) . '=' . $this->oauth_urlencode($value);
        }

        $baseString .= $this->oauth_urlencode(implode('&', $encodedParams));

        // encode the signature
        $tokens = $this->getToken();
        $hash = $this->hash_hmac_sha1($this->consumerSecret.'&'.$tokens['token_secret'], $baseString);
        $signature = base64_encode($hash);

        // add signature to oAuthParams
        $oAuthParams['oauth_signature'] = $signature;

        $oAuthEncoded = array();
        foreach ($oAuthParams as $key => $value) {
            $oAuthEncoded[] = $key . '="' . $this->oauth_urlencode($value) . '"';
        }

        return array('Authorization' => 'OAuth ' . implode(', ', $oAuthEncoded));
    }

    /**
     * Requests the OAuth request token.
     *
     * @return void 
     */
    public function getRequestToken() {
        $result = $this->fetch(self::URI_REQUEST_TOKEN, array(), 'POST');
        if ($result['httpStatus'] == "200") {
            $tokens = array();
            parse_str($result['body'], $tokens);
            $this->setToken($tokens['oauth_token'], $tokens['oauth_token_secret']);
            return $this->getToken();
        } else {
            throw new Dropbox_Exception_RequestToken('We were unable to fetch request tokens. This likely means that your consumer key and/or secret are incorrect.');
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
        $result = $this->fetch(self::URI_ACCESS_TOKEN, array(), 'POST');
        if ($result['httpStatus'] == "200") {
            $tokens = array();
            parse_str($result['body'], $tokens);
            $this->setToken($tokens['oauth_token'], $tokens['oauth_token_secret']);
            return $this->getToken();
        } else {
            throw new Dropbox_Exception_RequestToken('We were unable to fetch request tokens. This likely means that your consumer key and/or secret are incorrect.');
        }
    }

    /**
     * Helper function to properly urlencode parameters.
     * See http://php.net/manual/en/function.oauth-urlencode.php
     *
     * @param string $string
     * @return string
     */
    private function oauth_urlencode($string) {
        return str_replace('%E7', '~', rawurlencode($string));
    }

    /**
     * Hash function for hmac_sha1; uses native function if available.
     *
     * @param string $key
     * @param string $data
     * @return string
     */
    private function hash_hmac_sha1($key, $data) {
        if (function_exists('hash_hmac') && in_array('sha1', hash_algos())) {
            return hash_hmac('sha1', $data, $key, true);
        } else {
            $blocksize = 64;
            $hashfunc = 'sha1';
            if (strlen($key) > $blocksize) {
                $key = pack('H*', $hashfunc($key));
            }

            $key = str_pad($key, $blocksize, chr(0x00));
            $ipad = str_repeat(chr(0x36), $blocksize);
            $opad = str_repeat(chr(0x5c), $blocksize);
            $hash = pack('H*', $hashfunc(( $key ^ $opad ) . pack('H*', $hashfunc(($key ^ $ipad) . $data))));

            return $hash;
        }
    }

}