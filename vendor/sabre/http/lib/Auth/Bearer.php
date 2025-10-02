<?php

declare(strict_types=1);

namespace Sabre\HTTP\Auth;

/**
 * HTTP Bearer authentication utility.
 *
 * This class helps you setup bearer auth. The process is fairly simple:
 *
 * 1. Instantiate the class.
 * 2. Call getToken (this will return null or a token as string)
 * 3. If you didn't get a valid token, call 'requireLogin'
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author François Kooman (fkooman@tuxed.net)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Bearer extends AbstractAuth
{
    /**
     * This method returns a string with an access token.
     *
     * If no token was found, this method returns null.
     *
     * @return string|null
     */
    public function getToken()
    {
        $auth = $this->request->getHeader('Authorization');

        if (!$auth) {
            return null;
        }

        if ('bearer ' !== strtolower(substr($auth, 0, 7))) {
            return null;
        }

        return substr($auth, 7);
    }

    /**
     * This method sends the needed HTTP header and status code (401) to force
     * authentication.
     */
    public function requireLogin()
    {
        $this->response->addHeader('WWW-Authenticate', 'Bearer realm="'.$this->realm.'"');
        $this->response->setStatus(401);
    }
}
