<?php

/**
 * Apache authenticator
 *
 * This authentication backend assumes that authentication has been
 * conifgured in apache, rather than within SabreDAV.
 *
 * Make sure apache is properly configured for this to work.
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2011 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Auth_Backend_Apache implements Sabre_DAV_Auth_IBackend {

    /**
     * Current apache user 
     * 
     * @var string 
     */
    protected $remoteUser;
    
    /**
     * Authenticates the user based on the current request.
     *
     * If authentication is succesful, true must be returned.
     * If authentication fails, an exception must be thrown.
     *
     * @return bool 
     */
    public function authenticate(Sabre_DAV_Server $server,$realm) {

        $remoteUser = $server->httpRequest->getRawServerValue('REMOTE_USER');
        if (is_null($remoteUser)) {
            throw new Sabre_DAV_Exception('We did not receive the $_SERVER[REMOTE_USER] property. This means that apache might have been misconfigured');
        }

        $this->remoteUser = $remoteUser;
        return true;

    }

    /**
     * Returns information about the currently logged in user.
     *
     * If nobody is currently logged in, this method should return null.
     * 
     * @return array|null
     */
    public function getCurrentUser() {

        return $this->remoteUser;

    }

}

