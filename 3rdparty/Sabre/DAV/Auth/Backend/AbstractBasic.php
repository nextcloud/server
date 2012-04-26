<?php
/**
 * HTTP Basic authentication backend class
 *
 * This class can be used by authentication objects wishing to use HTTP Basic
 * Most of the digest logic is handled, implementors just need to worry about
 * the validateUserPass method.
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author James David Low (http://jameslow.com/)
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
abstract class Sabre_DAV_Auth_Backend_AbstractBasic implements Sabre_DAV_Auth_IBackend {

    /**
     * This variable holds the currently logged in username.
     *
     * @var string|null
     */
    protected $currentUser;

    /**
     * Validates a username and password
     *
     * This method should return true or false depending on if login
     * succeeded.
     *
     * @param string $username
     * @param string $password
     * @return bool
     */
    abstract protected function validateUserPass($username, $password);

    /**
     * Returns information about the currently logged in username.
     *
     * If nobody is currently logged in, this method should return null.
     *
     * @return string|null
     */
    public function getCurrentUser() {
        return $this->currentUser;
    }


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

        $auth = new Sabre_HTTP_BasicAuth();
        $auth->setHTTPRequest($server->httpRequest);
        $auth->setHTTPResponse($server->httpResponse);
        $auth->setRealm($realm);
        $userpass = $auth->getUserPass();
        if (!$userpass) {
            $auth->requireLogin();
            throw new Sabre_DAV_Exception_NotAuthenticated('No basic authentication headers were found');
        }

        // Authenticates the user
        if (!$this->validateUserPass($userpass[0],$userpass[1])) {
            $auth->requireLogin();
            throw new Sabre_DAV_Exception_NotAuthenticated('Username or password does not match');
        }
        $this->currentUser = $userpass[0];
        return true;
    }


}

