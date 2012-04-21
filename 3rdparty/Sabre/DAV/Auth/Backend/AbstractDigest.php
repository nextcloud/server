<?php

/**
 * HTTP Digest authentication backend class
 *
 * This class can be used by authentication objects wishing to use HTTP Digest
 * Most of the digest logic is handled, implementors just need to worry about
 * the getDigestHash method
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
abstract class Sabre_DAV_Auth_Backend_AbstractDigest implements Sabre_DAV_Auth_IBackend {

    /**
     * This variable holds the currently logged in username.
     *
     * @var array|null
     */
    protected $currentUser;

    /**
     * Returns a users digest hash based on the username and realm.
     *
     * If the user was not known, null must be returned.
     *
     * @param string $realm
     * @param string $username
     * @return string|null
     */
    abstract public function getDigestHash($realm, $username);

    /**
     * Authenticates the user based on the current request.
     *
     * If authentication is successful, true must be returned.
     * If authentication fails, an exception must be thrown.
     *
     * @param Sabre_DAV_Server $server
     * @param string $realm
     * @throws Sabre_DAV_Exception_NotAuthenticated
     * @return bool
     */
    public function authenticate(Sabre_DAV_Server $server, $realm) {

        $digest = new Sabre_HTTP_DigestAuth();

        // Hooking up request and response objects
        $digest->setHTTPRequest($server->httpRequest);
        $digest->setHTTPResponse($server->httpResponse);

        $digest->setRealm($realm);
        $digest->init();

        $username = $digest->getUsername();

        // No username was given
        if (!$username) {
            $digest->requireLogin();
            throw new Sabre_DAV_Exception_NotAuthenticated('No digest authentication headers were found');
        }

        $hash = $this->getDigestHash($realm, $username);
        // If this was false, the user account didn't exist
        if ($hash===false || is_null($hash)) {
            $digest->requireLogin();
            throw new Sabre_DAV_Exception_NotAuthenticated('The supplied username was not on file');
        }
        if (!is_string($hash)) {
            throw new Sabre_DAV_Exception('The returned value from getDigestHash must be a string or null');
        }

        // If this was false, the password or part of the hash was incorrect.
        if (!$digest->validateA1($hash)) {
            $digest->requireLogin();
            throw new Sabre_DAV_Exception_NotAuthenticated('Incorrect username');
        }

        $this->currentUser = $username;
        return true;

    }

    /**
     * Returns the currently logged in username.
     *
     * @return string|null
     */
    public function getCurrentUser() {

        return $this->currentUser;

    }

}
