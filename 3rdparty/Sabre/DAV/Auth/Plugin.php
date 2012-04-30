<?php

/**
 * This plugin provides Authentication for a WebDAV server.
 *
 * It relies on a Backend object, which provides user information.
 *
 * Additionally, it provides support for:
 *  * {DAV:}current-user-principal property from RFC5397
 *  * {DAV:}principal-collection-set property from RFC3744
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Auth_Plugin extends Sabre_DAV_ServerPlugin {

    /**
     * Reference to main server object
     *
     * @var Sabre_DAV_Server
     */
    private $server;

    /**
     * Authentication backend
     *
     * @var Sabre_DAV_Auth_IBackend
     */
    private $authBackend;

    /**
     * The authentication realm.
     *
     * @var string
     */
    private $realm;

    /**
     * __construct
     *
     * @param Sabre_DAV_Auth_IBackend $authBackend
     * @param string $realm
     */
    public function __construct(Sabre_DAV_Auth_IBackend $authBackend, $realm) {

        $this->authBackend = $authBackend;
        $this->realm = $realm;

    }

    /**
     * Initializes the plugin. This function is automatically called by the server
     *
     * @param Sabre_DAV_Server $server
     * @return void
     */
    public function initialize(Sabre_DAV_Server $server) {

        $this->server = $server;
        $this->server->subscribeEvent('beforeMethod',array($this,'beforeMethod'),10);

    }

    /**
     * Returns a plugin name.
     *
     * Using this name other plugins will be able to access other plugins
     * using Sabre_DAV_Server::getPlugin
     *
     * @return string
     */
    public function getPluginName() {

        return 'auth';

    }

    /**
     * Returns the current users' principal uri.
     *
     * If nobody is logged in, this will return null.
     *
     * @return string|null
     */
    public function getCurrentUser() {

        $userInfo = $this->authBackend->getCurrentUser();
        if (!$userInfo) return null;

        return $userInfo;

    }

    /**
     * This method is called before any HTTP method and forces users to be authenticated
     *
     * @param string $method
     * @param string $uri
     * @throws Sabre_DAV_Exception_NotAuthenticated
     * @return bool
     */
    public function beforeMethod($method, $uri) {

        $this->authBackend->authenticate($this->server,$this->realm);

    }

}
