<?php

/**
 * HTTP Digest Authentication handler
 *
 * Use this class for easy http digest authentication.
 * Instructions:
 *
 *  1. Create the object
 *  2. Call the setRealm() method with the realm you plan to use
 *  3. Call the init method function.
 *  4. Call the getUserName() function. This function may return false if no
 *     authentication information was supplied. Based on the username you
 *     should check your internal database for either the associated password,
 *     or the so-called A1 hash of the digest.
 *  5. Call either validatePassword() or validateA1(). This will return true
 *     or false.
 *  6. To make sure an authentication prompt is displayed, call the
 *     requireLogin() method.
 *
 *
 * @package Sabre
 * @subpackage HTTP
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_HTTP_DigestAuth extends Sabre_HTTP_AbstractAuth {

    /**
     * These constants are used in setQOP();
     */
    const QOP_AUTH = 1;
    const QOP_AUTHINT = 2;

    protected $nonce;
    protected $opaque;
    protected $digestParts;
    protected $A1;
    protected $qop = self::QOP_AUTH;

    /**
     * Initializes the object
     */
    public function __construct() {

        $this->nonce = uniqid();
        $this->opaque = md5($this->realm);
        parent::__construct();

    }

    /**
     * Gathers all information from the headers
     *
     * This method needs to be called prior to anything else.
     *
     * @return void
     */
    public function init() {

        $digest = $this->getDigest();
        $this->digestParts = $this->parseDigest($digest);

    }

    /**
     * Sets the quality of protection value.
     *
     * Possible values are:
     *   Sabre_HTTP_DigestAuth::QOP_AUTH
     *   Sabre_HTTP_DigestAuth::QOP_AUTHINT
     *
     * Multiple values can be specified using logical OR.
     *
     * QOP_AUTHINT ensures integrity of the request body, but this is not
     * supported by most HTTP clients. QOP_AUTHINT also requires the entire
     * request body to be md5'ed, which can put strains on CPU and memory.
     *
     * @param int $qop
     * @return void
     */
    public function setQOP($qop) {

        $this->qop = $qop;

    }

    /**
     * Validates the user.
     *
     * The A1 parameter should be md5($username . ':' . $realm . ':' . $password);
     *
     * @param string $A1
     * @return bool
     */
    public function validateA1($A1) {

        $this->A1 = $A1;
        return $this->validate();

    }

    /**
     * Validates authentication through a password. The actual password must be provided here.
     * It is strongly recommended not store the password in plain-text and use validateA1 instead.
     *
     * @param string $password
     * @return bool
     */
    public function validatePassword($password) {

        $this->A1 = md5($this->digestParts['username'] . ':' . $this->realm . ':' . $password);
        return $this->validate();

    }

    /**
     * Returns the username for the request
     *
     * @return string
     */
    public function getUsername() {

        return $this->digestParts['username'];

    }

    /**
     * Validates the digest challenge
     *
     * @return bool
     */
    protected function validate() {

        $A2 = $this->httpRequest->getMethod() . ':' . $this->digestParts['uri'];

        if ($this->digestParts['qop']=='auth-int') {
            // Making sure we support this qop value
            if (!($this->qop & self::QOP_AUTHINT)) return false;
            // We need to add an md5 of the entire request body to the A2 part of the hash
            $body = $this->httpRequest->getBody(true);
            $this->httpRequest->setBody($body,true);
            $A2 .= ':' . md5($body);
        } else {

            // We need to make sure we support this qop value
            if (!($this->qop & self::QOP_AUTH)) return false;
        }

        $A2 = md5($A2);

        $validResponse = md5("{$this->A1}:{$this->digestParts['nonce']}:{$this->digestParts['nc']}:{$this->digestParts['cnonce']}:{$this->digestParts['qop']}:{$A2}");

        return $this->digestParts['response']==$validResponse;


    }

    /**
     * Returns an HTTP 401 header, forcing login
     *
     * This should be called when username and password are incorrect, or not supplied at all
     *
     * @return void
     */
    public function requireLogin() {

        $qop = '';
        switch($this->qop) {
            case self::QOP_AUTH    : $qop = 'auth'; break;
            case self::QOP_AUTHINT : $qop = 'auth-int'; break;
            case self::QOP_AUTH | self::QOP_AUTHINT : $qop = 'auth,auth-int'; break;
        }

        $this->httpResponse->setHeader('WWW-Authenticate','Digest realm="' . $this->realm . '",qop="'.$qop.'",nonce="' . $this->nonce . '",opaque="' . $this->opaque . '"');
        $this->httpResponse->sendStatus(401);

    }


    /**
     * This method returns the full digest string.
     *
     * It should be compatibile with mod_php format and other webservers.
     *
     * If the header could not be found, null will be returned
     *
     * @return mixed
     */
    public function getDigest() {

        // mod_php
        $digest = $this->httpRequest->getRawServerValue('PHP_AUTH_DIGEST');
        if ($digest) return $digest;

        // most other servers
        $digest = $this->httpRequest->getHeader('Authorization');

        // Apache could prefix environment variables with REDIRECT_ when urls
        // are passed through mod_rewrite
        if (!$digest) {
            $digest = $this->httpRequest->getRawServerValue('REDIRECT_HTTP_AUTHORIZATION');
        }

        if ($digest && strpos(strtolower($digest),'digest')===0) {
            return substr($digest,7);
        } else {
            return null;
        }

    }


    /**
     * Parses the different pieces of the digest string into an array.
     *
     * This method returns false if an incomplete digest was supplied
     *
     * @param string $digest
     * @return mixed
     */
    protected function parseDigest($digest) {

        // protect against missing data
        $needed_parts = array('nonce'=>1, 'nc'=>1, 'cnonce'=>1, 'qop'=>1, 'username'=>1, 'uri'=>1, 'response'=>1);
        $data = array();

        preg_match_all('@(\w+)=(?:(?:")([^"]+)"|([^\s,$]+))@', $digest, $matches, PREG_SET_ORDER);

        foreach ($matches as $m) {
            $data[$m[1]] = $m[2] ? $m[2] : $m[3];
            unset($needed_parts[$m[1]]);
        }

        return $needed_parts ? false : $data;

    }

}
