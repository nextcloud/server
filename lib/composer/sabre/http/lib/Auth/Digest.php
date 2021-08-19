<?php

declare(strict_types=1);

namespace Sabre\HTTP\Auth;

use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * HTTP Digest Authentication handler.
 *
 * Use this class for easy http digest authentication.
 * Instructions:
 *
 *  1. Create the object
 *  2. Call the setRealm() method with the realm you plan to use
 *  3. Call the init method function.
 *  4. Call the getUserName() function. This function may return null if no
 *     authentication information was supplied. Based on the username you
 *     should check your internal database for either the associated password,
 *     or the so-called A1 hash of the digest.
 *  5. Call either validatePassword() or validateA1(). This will return true
 *     or false.
 *  6. To make sure an authentication prompt is displayed, call the
 *     requireLogin() method.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Digest extends AbstractAuth
{
    /**
     * These constants are used in setQOP();.
     */
    const QOP_AUTH = 1;
    const QOP_AUTHINT = 2;

    protected $nonce;
    protected $opaque;
    protected $digestParts;
    protected $A1;
    protected $qop = self::QOP_AUTH;

    /**
     * Initializes the object.
     */
    public function __construct(string $realm, RequestInterface $request, ResponseInterface $response)
    {
        $this->nonce = uniqid();
        $this->opaque = md5($realm);
        parent::__construct($realm, $request, $response);
    }

    /**
     * Gathers all information from the headers.
     *
     * This method needs to be called prior to anything else.
     */
    public function init()
    {
        $digest = $this->getDigest();
        $this->digestParts = $this->parseDigest((string) $digest);
    }

    /**
     * Sets the quality of protection value.
     *
     * Possible values are:
     *   Sabre\HTTP\DigestAuth::QOP_AUTH
     *   Sabre\HTTP\DigestAuth::QOP_AUTHINT
     *
     * Multiple values can be specified using logical OR.
     *
     * QOP_AUTHINT ensures integrity of the request body, but this is not
     * supported by most HTTP clients. QOP_AUTHINT also requires the entire
     * request body to be md5'ed, which can put strains on CPU and memory.
     */
    public function setQOP(int $qop)
    {
        $this->qop = $qop;
    }

    /**
     * Validates the user.
     *
     * The A1 parameter should be md5($username . ':' . $realm . ':' . $password);
     */
    public function validateA1(string $A1): bool
    {
        $this->A1 = $A1;

        return $this->validate();
    }

    /**
     * Validates authentication through a password. The actual password must be provided here.
     * It is strongly recommended not store the password in plain-text and use validateA1 instead.
     */
    public function validatePassword(string $password): bool
    {
        $this->A1 = md5($this->digestParts['username'].':'.$this->realm.':'.$password);

        return $this->validate();
    }

    /**
     * Returns the username for the request.
     * Returns null if there were none.
     *
     * @return string|null
     */
    public function getUsername()
    {
        return $this->digestParts['username'] ?? null;
    }

    /**
     * Validates the digest challenge.
     */
    protected function validate(): bool
    {
        if (!is_array($this->digestParts)) {
            return false;
        }

        $A2 = $this->request->getMethod().':'.$this->digestParts['uri'];

        if ('auth-int' === $this->digestParts['qop']) {
            // Making sure we support this qop value
            if (!($this->qop & self::QOP_AUTHINT)) {
                return false;
            }
            // We need to add an md5 of the entire request body to the A2 part of the hash
            $body = $this->request->getBody($asString = true);
            $this->request->setBody($body);
            $A2 .= ':'.md5($body);
        } elseif (!($this->qop & self::QOP_AUTH)) {
            return false;
        }

        $A2 = md5($A2);

        $validResponse = md5("{$this->A1}:{$this->digestParts['nonce']}:{$this->digestParts['nc']}:{$this->digestParts['cnonce']}:{$this->digestParts['qop']}:{$A2}");

        return $this->digestParts['response'] === $validResponse;
    }

    /**
     * Returns an HTTP 401 header, forcing login.
     *
     * This should be called when username and password are incorrect, or not supplied at all
     */
    public function requireLogin()
    {
        $qop = '';
        switch ($this->qop) {
            case self::QOP_AUTH:
                $qop = 'auth';
                break;
            case self::QOP_AUTHINT:
                $qop = 'auth-int';
                break;
            case self::QOP_AUTH | self::QOP_AUTHINT:
                $qop = 'auth,auth-int';
                break;
        }

        $this->response->addHeader('WWW-Authenticate', 'Digest realm="'.$this->realm.'",qop="'.$qop.'",nonce="'.$this->nonce.'",opaque="'.$this->opaque.'"');
        $this->response->setStatus(401);
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
    public function getDigest()
    {
        return $this->request->getHeader('Authorization');
    }

    /**
     * Parses the different pieces of the digest string into an array.
     *
     * This method returns false if an incomplete digest was supplied
     *
     * @return bool|array
     */
    protected function parseDigest(string $digest)
    {
        // protect against missing data
        $needed_parts = ['nonce' => 1, 'nc' => 1, 'cnonce' => 1, 'qop' => 1, 'username' => 1, 'uri' => 1, 'response' => 1];
        $data = [];

        preg_match_all('@(\w+)=(?:(?:")([^"]+)"|([^\s,$]+))@', $digest, $matches, PREG_SET_ORDER);

        foreach ($matches as $m) {
            $data[$m[1]] = $m[2] ?: $m[3];
            unset($needed_parts[$m[1]]);
        }

        return $needed_parts ? false : $data;
    }
}
