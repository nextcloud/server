<?php

declare(strict_types=1);

namespace Sabre\DAV\Auth;

use Sabre\DAV\Exception\NotAuthenticated;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * This plugin provides Authentication for a WebDAV server.
 *
 * It works by providing a Auth\Backend class. Several examples of these
 * classes can be found in the Backend directory.
 *
 * It's possible to provide more than one backend to this plugin. If more than
 * one backend was provided, each backend will attempt to authenticate. Only if
 * all backends fail, we throw a 401.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Plugin extends ServerPlugin
{
    /**
     * By default this plugin will require that the user is authenticated,
     * and refuse any access if the user is not authenticated.
     *
     * If this setting is set to false, we let the user through, whether they
     * are authenticated or not.
     *
     * This is useful if you want to allow both authenticated and
     * unauthenticated access to your server.
     *
     * @param bool
     */
    public $autoRequireLogin = true;

    /**
     * authentication backends.
     */
    protected $backends;

    /**
     * The currently logged in principal. Will be `null` if nobody is currently
     * logged in.
     *
     * @var string|null
     */
    protected $currentPrincipal;

    /**
     * Creates the authentication plugin.
     *
     * @param Backend\BackendInterface $authBackend
     */
    public function __construct(Backend\BackendInterface $authBackend = null)
    {
        if (!is_null($authBackend)) {
            $this->addBackend($authBackend);
        }
    }

    /**
     * Adds an authentication backend to the plugin.
     */
    public function addBackend(Backend\BackendInterface $authBackend)
    {
        $this->backends[] = $authBackend;
    }

    /**
     * Initializes the plugin. This function is automatically called by the server.
     */
    public function initialize(Server $server)
    {
        $server->on('beforeMethod:*', [$this, 'beforeMethod'], 10);
    }

    /**
     * Returns a plugin name.
     *
     * Using this name other plugins will be able to access other plugins
     * using DAV\Server::getPlugin
     *
     * @return string
     */
    public function getPluginName()
    {
        return 'auth';
    }

    /**
     * Returns the currently logged-in principal.
     *
     * This will return a string such as:
     *
     * principals/username
     * principals/users/username
     *
     * This method will return null if nobody is logged in.
     *
     * @return string|null
     */
    public function getCurrentPrincipal()
    {
        return $this->currentPrincipal;
    }

    /**
     * This method is called before any HTTP method and forces users to be authenticated.
     *
     * @return bool
     */
    public function beforeMethod(RequestInterface $request, ResponseInterface $response)
    {
        if ($this->currentPrincipal) {
            // We already have authentication information. This means that the
            // event has already fired earlier, and is now likely fired for a
            // sub-request.
            //
            // We don't want to authenticate users twice, so we simply don't do
            // anything here. See Issue #700 for additional reasoning.
            //
            // This is not a perfect solution, but will be fixed once the
            // "currently authenticated principal" is information that's not
            // not associated with the plugin, but rather per-request.
            //
            // See issue #580 for more information about that.
            return;
        }

        $authResult = $this->check($request, $response);

        if ($authResult[0]) {
            // Auth was successful
            $this->currentPrincipal = $authResult[1];
            $this->loginFailedReasons = null;

            return;
        }

        // If we got here, it means that no authentication backend was
        // successful in authenticating the user.
        $this->currentPrincipal = null;
        $this->loginFailedReasons = $authResult[1];

        if ($this->autoRequireLogin) {
            $this->challenge($request, $response);
            throw new NotAuthenticated(implode(', ', $authResult[1]));
        }
    }

    /**
     * Checks authentication credentials, and logs the user in if possible.
     *
     * This method returns an array. The first item in the array is a boolean
     * indicating if login was successful.
     *
     * If login was successful, the second item in the array will contain the
     * current principal url/path of the logged in user.
     *
     * If login was not successful, the second item in the array will contain a
     * an array with strings. The strings are a list of reasons why login was
     * unsuccessful. For every auth backend there will be one reason, so usually
     * there's just one.
     *
     * @return array
     */
    public function check(RequestInterface $request, ResponseInterface $response)
    {
        if (!$this->backends) {
            throw new \Sabre\DAV\Exception('No authentication backends were configured on this server.');
        }
        $reasons = [];
        foreach ($this->backends as $backend) {
            $result = $backend->check(
                $request,
                $response
            );

            if (!is_array($result) || 2 !== count($result) || !is_bool($result[0]) || !is_string($result[1])) {
                throw new \Sabre\DAV\Exception('The authentication backend did not return a correct value from the check() method.');
            }

            if ($result[0]) {
                $this->currentPrincipal = $result[1];
                // Exit early
                return [true, $result[1]];
            }
            $reasons[] = $result[1];
        }

        return [false, $reasons];
    }

    /**
     * This method sends authentication challenges to the user.
     *
     * This method will for example cause a HTTP Basic backend to set a
     * WWW-Authorization header, indicating to the client that it should
     * authenticate.
     *
     * @return array
     */
    public function challenge(RequestInterface $request, ResponseInterface $response)
    {
        foreach ($this->backends as $backend) {
            $backend->challenge($request, $response);
        }
    }

    /**
     * List of reasons why login failed for the last login operation.
     *
     * @var string[]|null
     */
    protected $loginFailedReasons;

    /**
     * Returns a list of reasons why login was unsuccessful.
     *
     * This method will return the login failed reasons for the last login
     * operation. One for each auth backend.
     *
     * This method returns null if the last authentication attempt was
     * successful, or if there was no authentication attempt yet.
     *
     * @return string[]|null
     */
    public function getLoginFailedReasons()
    {
        return $this->loginFailedReasons;
    }

    /**
     * Returns a bunch of meta-data about the plugin.
     *
     * Providing this information is optional, and is mainly displayed by the
     * Browser plugin.
     *
     * The description key in the returned array may contain html and will not
     * be sanitized.
     *
     * @return array
     */
    public function getPluginInfo()
    {
        return [
            'name' => $this->getPluginName(),
            'description' => 'Generic authentication plugin',
            'link' => 'http://sabre.io/dav/authentication/',
        ];
    }
}
