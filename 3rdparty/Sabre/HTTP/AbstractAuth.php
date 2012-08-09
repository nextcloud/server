<?php

/**
 * HTTP Authentication baseclass
 *
 * This class has the common functionality for BasicAuth and DigestAuth
 *
 * @package Sabre
 * @subpackage HTTP
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
abstract class Sabre_HTTP_AbstractAuth {

    /**
     * The realm will be displayed in the dialog boxes
     *
     * This identifier can be changed through setRealm()
     *
     * @var string
     */
    protected $realm = 'SabreDAV';

    /**
     * HTTP response helper
     *
     * @var Sabre_HTTP_Response
     */
    protected $httpResponse;


    /**
     * HTTP request helper
     *
     * @var Sabre_HTTP_Request
     */
    protected $httpRequest;

    /**
     * __construct
     *
     */
    public function __construct() {

        $this->httpResponse = new Sabre_HTTP_Response();
        $this->httpRequest = new Sabre_HTTP_Request();

    }

    /**
     * Sets an alternative HTTP response object
     *
     * @param Sabre_HTTP_Response $response
     * @return void
     */
    public function setHTTPResponse(Sabre_HTTP_Response $response) {

        $this->httpResponse = $response;

    }

    /**
     * Sets an alternative HTTP request object
     *
     * @param Sabre_HTTP_Request $request
     * @return void
     */
    public function setHTTPRequest(Sabre_HTTP_Request $request) {

        $this->httpRequest = $request;

    }


    /**
     * Sets the realm
     *
     * The realm is often displayed in authentication dialog boxes
     * Commonly an application name displayed here
     *
     * @param string $realm
     * @return void
     */
    public function setRealm($realm) {

        $this->realm = $realm;

    }

    /**
     * Returns the realm
     *
     * @return string
     */
    public function getRealm() {

        return $this->realm;

    }

    /**
     * Returns an HTTP 401 header, forcing login
     *
     * This should be called when username and password are incorrect, or not supplied at all
     *
     * @return void
     */
    abstract public function requireLogin();

}
