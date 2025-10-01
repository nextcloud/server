<?php

declare(strict_types=1);

namespace Sabre\DAV\Auth\Backend;

use Sabre\DAV;
use Sabre\HTTP;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * HTTP Digest authentication backend class.
 *
 * This class can be used by authentication objects wishing to use HTTP Digest
 * Most of the digest logic is handled, implementors just need to worry about
 * the getDigestHash method
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
abstract class AbstractDigest implements BackendInterface
{
    /**
     * Authentication Realm.
     *
     * The realm is often displayed by browser clients when showing the
     * authentication dialog.
     *
     * @var string
     */
    protected $realm = 'SabreDAV';

    /**
     * This is the prefix that will be used to generate principal urls.
     *
     * @var string
     */
    protected $principalPrefix = 'principals/';

    /**
     * Sets the authentication realm for this backend.
     *
     * Be aware that for Digest authentication, the realm influences the digest
     * hash. Choose the realm wisely, because if you change it later, all the
     * existing hashes will break and nobody can authenticate.
     *
     * @param string $realm
     */
    public function setRealm($realm)
    {
        $this->realm = $realm;
    }

    /**
     * Returns a users digest hash based on the username and realm.
     *
     * If the user was not known, null must be returned.
     *
     * @param string $realm
     * @param string $username
     *
     * @return string|null
     */
    abstract public function getDigestHash($realm, $username);

    /**
     * When this method is called, the backend must check if authentication was
     * successful.
     *
     * The returned value must be one of the following
     *
     * [true, "principals/username"]
     * [false, "reason for failure"]
     *
     * If authentication was successful, it's expected that the authentication
     * backend returns a so-called principal url.
     *
     * Examples of a principal url:
     *
     * principals/admin
     * principals/user1
     * principals/users/joe
     * principals/uid/123457
     *
     * If you don't use WebDAV ACL (RFC3744) we recommend that you simply
     * return a string such as:
     *
     * principals/users/[username]
     *
     * @return array
     */
    public function check(RequestInterface $request, ResponseInterface $response)
    {
        $digest = new HTTP\Auth\Digest(
            $this->realm,
            $request,
            $response
        );
        $digest->init();

        $username = $digest->getUsername();

        // No username was given
        if (!$username) {
            return [false, "No 'Authorization: Digest' header found. Either the client didn't send one, or the server is misconfigured"];
        }

        $hash = $this->getDigestHash($this->realm, $username);
        // If this was false, the user account didn't exist
        if (false === $hash || is_null($hash)) {
            return [false, 'Username or password was incorrect'];
        }
        if (!is_string($hash)) {
            throw new DAV\Exception('The returned value from getDigestHash must be a string or null');
        }

        // If this was false, the password or part of the hash was incorrect.
        if (!$digest->validateA1($hash)) {
            return [false, 'Username or password was incorrect'];
        }

        return [true, $this->principalPrefix.$username];
    }

    /**
     * This method is called when a user could not be authenticated, and
     * authentication was required for the current request.
     *
     * This gives you the opportunity to set authentication headers. The 401
     * status code will already be set.
     *
     * In this case of Basic Auth, this would for example mean that the
     * following header needs to be set:
     *
     * $response->addHeader('WWW-Authenticate', 'Basic realm=SabreDAV');
     *
     * Keep in mind that in the case of multiple authentication backends, other
     * WWW-Authenticate headers may already have been set, and you'll want to
     * append your own WWW-Authenticate header instead of overwriting the
     * existing one.
     */
    public function challenge(RequestInterface $request, ResponseInterface $response)
    {
        $auth = new HTTP\Auth\Digest(
            $this->realm,
            $request,
            $response
        );
        $auth->init();

        $oldStatus = $response->getStatus() ?: 200;
        $auth->requireLogin();

        // Preventing the digest utility from modifying the http status code,
        // this should be handled by the main plugin.
        $response->setStatus($oldStatus);
    }
}
