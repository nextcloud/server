<?php

declare(strict_types=1);

namespace Sabre\DAV\Locks;

use Sabre\DAV;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * Locking plugin.
 *
 * This plugin provides locking support to a WebDAV server.
 * The easiest way to get started, is by hooking it up as such:
 *
 * $lockBackend = new Sabre\DAV\Locks\Backend\File('./mylockdb');
 * $lockPlugin = new Sabre\DAV\Locks\Plugin($lockBackend);
 * $server->addPlugin($lockPlugin);
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Plugin extends DAV\ServerPlugin
{
    /**
     * locksBackend.
     *
     * @var Backend\BackendInterface
     */
    protected $locksBackend;

    /**
     * server.
     *
     * @var DAV\Server
     */
    protected $server;

    /**
     * __construct.
     */
    public function __construct(Backend\BackendInterface $locksBackend)
    {
        $this->locksBackend = $locksBackend;
    }

    /**
     * Initializes the plugin.
     *
     * This method is automatically called by the Server class after addPlugin.
     */
    public function initialize(DAV\Server $server)
    {
        $this->server = $server;

        $this->server->xml->elementMap['{DAV:}lockinfo'] = 'Sabre\\DAV\\Xml\\Request\\Lock';

        $server->on('method:LOCK', [$this, 'httpLock']);
        $server->on('method:UNLOCK', [$this, 'httpUnlock']);
        $server->on('validateTokens', [$this, 'validateTokens']);
        $server->on('propFind', [$this, 'propFind']);
        $server->on('afterUnbind', [$this, 'afterUnbind']);
    }

    /**
     * Returns a plugin name.
     *
     * Using this name other plugins will be able to access other plugins
     * using Sabre\DAV\Server::getPlugin
     *
     * @return string
     */
    public function getPluginName()
    {
        return 'locks';
    }

    /**
     * This method is called after most properties have been found
     * it allows us to add in any Lock-related properties.
     */
    public function propFind(DAV\PropFind $propFind, DAV\INode $node)
    {
        $propFind->handle('{DAV:}supportedlock', function () {
            return new DAV\Xml\Property\SupportedLock();
        });
        $propFind->handle('{DAV:}lockdiscovery', function () use ($propFind) {
            return new DAV\Xml\Property\LockDiscovery(
                $this->getLocks($propFind->getPath())
            );
        });
    }

    /**
     * Use this method to tell the server this plugin defines additional
     * HTTP methods.
     *
     * This method is passed a uri. It should only return HTTP methods that are
     * available for the specified uri.
     *
     * @param string $uri
     *
     * @return array
     */
    public function getHTTPMethods($uri)
    {
        return ['LOCK', 'UNLOCK'];
    }

    /**
     * Returns a list of features for the HTTP OPTIONS Dav: header.
     *
     * In this case this is only the number 2. The 2 in the Dav: header
     * indicates the server supports locks.
     *
     * @return array
     */
    public function getFeatures()
    {
        return [2];
    }

    /**
     * Returns all lock information on a particular uri.
     *
     * This function should return an array with Sabre\DAV\Locks\LockInfo objects. If there are no locks on a file, return an empty array.
     *
     * Additionally there is also the possibility of locks on parent nodes, so we'll need to traverse every part of the tree
     * If the $returnChildLocks argument is set to true, we'll also traverse all the children of the object
     * for any possible locks and return those as well.
     *
     * @param string $uri
     * @param bool   $returnChildLocks
     *
     * @return array
     */
    public function getLocks($uri, $returnChildLocks = false)
    {
        return $this->locksBackend->getLocks($uri, $returnChildLocks);
    }

    /**
     * Locks an uri.
     *
     * The WebDAV lock request can be operated to either create a new lock on a file, or to refresh an existing lock
     * If a new lock is created, a full XML body should be supplied, containing information about the lock such as the type
     * of lock (shared or exclusive) and the owner of the lock
     *
     * If a lock is to be refreshed, no body should be supplied and there should be a valid If header containing the lock
     *
     * Additionally, a lock can be requested for a non-existent file. In these case we're obligated to create an empty file as per RFC4918:S7.3
     *
     * @return bool
     */
    public function httpLock(RequestInterface $request, ResponseInterface $response)
    {
        $uri = $request->getPath();

        $existingLocks = $this->getLocks($uri);

        if ($body = $request->getBodyAsString()) {
            // This is a new lock request

            $existingLock = null;
            // Checking if there's already non-shared locks on the uri.
            foreach ($existingLocks as $existingLock) {
                if (LockInfo::EXCLUSIVE === $existingLock->scope) {
                    throw new DAV\Exception\ConflictingLock($existingLock);
                }
            }

            $lockInfo = $this->parseLockRequest($body);
            $lockInfo->depth = $this->server->getHTTPDepth();
            $lockInfo->uri = $uri;
            if ($existingLock && LockInfo::SHARED != $lockInfo->scope) {
                throw new DAV\Exception\ConflictingLock($existingLock);
            }
        } else {
            // Gonna check if this was a lock refresh.
            $existingLocks = $this->getLocks($uri);
            $conditions = $this->server->getIfConditions($request);
            $found = null;

            foreach ($existingLocks as $existingLock) {
                foreach ($conditions as $condition) {
                    foreach ($condition['tokens'] as $token) {
                        if ($token['token'] === 'opaquelocktoken:'.$existingLock->token) {
                            $found = $existingLock;
                            break 3;
                        }
                    }
                }
            }

            // If none were found, this request is in error.
            if (is_null($found)) {
                if ($existingLocks) {
                    throw new DAV\Exception\Locked(reset($existingLocks));
                } else {
                    throw new DAV\Exception\BadRequest('An xml body is required for lock requests');
                }
            }

            // This must have been a lock refresh
            $lockInfo = $found;

            // The resource could have been locked through another uri.
            if ($uri != $lockInfo->uri) {
                $uri = $lockInfo->uri;
            }
        }

        if ($timeout = $this->getTimeoutHeader()) {
            $lockInfo->timeout = $timeout;
        }

        $newFile = false;

        // If we got this far.. we should go check if this node actually exists. If this is not the case, we need to create it first
        try {
            $this->server->tree->getNodeForPath($uri);

            // We need to call the beforeWriteContent event for RFC3744
            // Edit: looks like this is not used, and causing problems now.
            //
            // See Issue 222
            // $this->server->emit('beforeWriteContent',array($uri));
        } catch (DAV\Exception\NotFound $e) {
            // It didn't, lets create it
            $this->server->createFile($uri, fopen('php://memory', 'r'));
            $newFile = true;
        }

        $this->lockNode($uri, $lockInfo);

        $response->setHeader('Content-Type', 'application/xml; charset=utf-8');
        $response->setHeader('Lock-Token', '<opaquelocktoken:'.$lockInfo->token.'>');
        $response->setStatus($newFile ? 201 : 200);
        $response->setBody($this->generateLockResponse($lockInfo));

        // Returning false will interrupt the event chain and mark this method
        // as 'handled'.
        return false;
    }

    /**
     * Unlocks a uri.
     *
     * This WebDAV method allows you to remove a lock from a node. The client should provide a valid locktoken through the Lock-token http header
     * The server should return 204 (No content) on success
     */
    public function httpUnlock(RequestInterface $request, ResponseInterface $response)
    {
        $lockToken = $request->getHeader('Lock-Token');

        // If the locktoken header is not supplied, we need to throw a bad request exception
        if (!$lockToken) {
            throw new DAV\Exception\BadRequest('No lock token was supplied');
        }
        $path = $request->getPath();
        $locks = $this->getLocks($path);

        // Windows sometimes forgets to include < and > in the Lock-Token
        // header
        if ('<' !== $lockToken[0]) {
            $lockToken = '<'.$lockToken.'>';
        }

        foreach ($locks as $lock) {
            if ('<opaquelocktoken:'.$lock->token.'>' == $lockToken) {
                $this->unlockNode($path, $lock);
                $response->setHeader('Content-Length', '0');
                $response->setStatus(204);

                // Returning false will break the method chain, and mark the
                // method as 'handled'.
                return false;
            }
        }

        // If we got here, it means the locktoken was invalid
        throw new DAV\Exception\LockTokenMatchesRequestUri();
    }

    /**
     * This method is called after a node is deleted.
     *
     * We use this event to clean up any locks that still exist on the node.
     *
     * @param string $path
     */
    public function afterUnbind($path)
    {
        $locks = $this->getLocks($path, $includeChildren = true);
        foreach ($locks as $lock) {
            $this->unlockNode($path, $lock);
        }
    }

    /**
     * Locks a uri.
     *
     * All the locking information is supplied in the lockInfo object. The object has a suggested timeout, but this can be safely ignored
     * It is important that if the existing timeout is ignored, the property is overwritten, as this needs to be sent back to the client
     *
     * @param string $uri
     *
     * @return bool
     */
    public function lockNode($uri, LockInfo $lockInfo)
    {
        if (!$this->server->emit('beforeLock', [$uri, $lockInfo])) {
            return;
        }

        return $this->locksBackend->lock($uri, $lockInfo);
    }

    /**
     * Unlocks a uri.
     *
     * This method removes a lock from a uri. It is assumed all the supplied information is correct and verified
     *
     * @param string $uri
     *
     * @return bool
     */
    public function unlockNode($uri, LockInfo $lockInfo)
    {
        if (!$this->server->emit('beforeUnlock', [$uri, $lockInfo])) {
            return;
        }

        return $this->locksBackend->unlock($uri, $lockInfo);
    }

    /**
     * Returns the contents of the HTTP Timeout header.
     *
     * The method formats the header into an integer.
     *
     * @return int
     */
    public function getTimeoutHeader()
    {
        $header = $this->server->httpRequest->getHeader('Timeout');

        if ($header) {
            if (0 === stripos($header, 'second-')) {
                $header = (int) (substr($header, 7));
            } elseif (0 === stripos($header, 'infinite')) {
                $header = LockInfo::TIMEOUT_INFINITE;
            } else {
                throw new DAV\Exception\BadRequest('Invalid HTTP timeout header');
            }
        } else {
            $header = 0;
        }

        return $header;
    }

    /**
     * Generates the response for successful LOCK requests.
     *
     * @return string
     */
    protected function generateLockResponse(LockInfo $lockInfo)
    {
        return $this->server->xml->write('{DAV:}prop', [
            '{DAV:}lockdiscovery' => new DAV\Xml\Property\LockDiscovery([$lockInfo]),
        ], $this->server->getBaseUri());
    }

    /**
     * The validateTokens event is triggered before every request.
     *
     * It's a moment where this plugin can check all the supplied lock tokens
     * in the If: header, and check if they are valid.
     *
     * In addition, it will also ensure that it checks any missing lokens that
     * must be present in the request, and reject requests without the proper
     * tokens.
     *
     * @param mixed $conditions
     */
    public function validateTokens(RequestInterface $request, &$conditions)
    {
        // First we need to gather a list of locks that must be satisfied.
        $mustLocks = [];
        $method = $request->getMethod();

        // Methods not in that list are operations that doesn't alter any
        // resources, and we don't need to check the lock-states for.
        switch ($method) {
            case 'DELETE':
                $mustLocks = array_merge($mustLocks, $this->getLocks(
                    $request->getPath(),
                    true
                ));
                break;
            case 'MKCOL':
            case 'MKCALENDAR':
            case 'PROPPATCH':
            case 'PUT':
            case 'PATCH':
                $mustLocks = array_merge($mustLocks, $this->getLocks(
                    $request->getPath(),
                    false
                ));
                break;
            case 'MOVE':
                $mustLocks = array_merge($mustLocks, $this->getLocks(
                    $request->getPath(),
                    true
                ));
                $mustLocks = array_merge($mustLocks, $this->getLocks(
                    $this->server->calculateUri($request->getHeader('Destination')),
                    false
                ));
                break;
            case 'COPY':
                $mustLocks = array_merge($mustLocks, $this->getLocks(
                    $this->server->calculateUri($request->getHeader('Destination')),
                    false
                ));
                break;
            case 'LOCK':
                //Temporary measure.. figure out later why this is needed
                // Here we basically ignore all incoming tokens...
                foreach ($conditions as $ii => $condition) {
                    foreach ($condition['tokens'] as $jj => $token) {
                        $conditions[$ii]['tokens'][$jj]['validToken'] = true;
                    }
                }

                return;
        }

        // It's possible that there's identical locks, because of shared
        // parents. We're removing the duplicates here.
        $tmp = [];
        foreach ($mustLocks as $lock) {
            $tmp[$lock->token] = $lock;
        }
        $mustLocks = array_values($tmp);

        foreach ($conditions as $kk => $condition) {
            foreach ($condition['tokens'] as $ii => $token) {
                // Lock tokens always start with opaquelocktoken:
                if ('opaquelocktoken:' !== substr($token['token'], 0, 16)) {
                    continue;
                }

                $checkToken = substr($token['token'], 16);
                // Looping through our list with locks.
                foreach ($mustLocks as $jj => $mustLock) {
                    if ($mustLock->token == $checkToken) {
                        // We have a match!
                        // Removing this one from mustlocks
                        unset($mustLocks[$jj]);

                        // Marking the condition as valid.
                        $conditions[$kk]['tokens'][$ii]['validToken'] = true;

                        // Advancing to the next token
                        continue 2;
                    }
                }

                // If we got here, it means that there was a
                // lock-token, but it was not in 'mustLocks'.
                //
                // This is an edge-case, as it could mean that token
                // was specified with a url that was not 'required' to
                // check. So we're doing one extra lookup to make sure
                // we really don't know this token.
                //
                // This also gets triggered when the user specified a
                // lock-token that was expired.
                $oddLocks = $this->getLocks($condition['uri']);
                foreach ($oddLocks as $oddLock) {
                    if ($oddLock->token === $checkToken) {
                        // We have a hit!
                        $conditions[$kk]['tokens'][$ii]['validToken'] = true;
                        continue 2;
                    }
                }

                // If we get all the way here, the lock-token was
                // really unknown.
            }
        }

        // If there's any locks left in the 'mustLocks' array, it means that
        // the resource was locked and we must block it.
        if ($mustLocks) {
            throw new DAV\Exception\Locked(reset($mustLocks));
        }
    }

    /**
     * Parses a webdav lock xml body, and returns a new Sabre\DAV\Locks\LockInfo object.
     *
     * @param string $body
     *
     * @return LockInfo
     */
    protected function parseLockRequest($body)
    {
        $result = $this->server->xml->expect(
            '{DAV:}lockinfo',
            $body
        );

        $lockInfo = new LockInfo();

        $lockInfo->owner = $result->owner;
        $lockInfo->token = DAV\UUIDUtil::getUUID();
        $lockInfo->scope = $result->scope;

        return $lockInfo;
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
            'description' => 'The locks plugin turns this server into a class-2 WebDAV server and adds support for LOCK and UNLOCK',
            'link' => 'http://sabre.io/dav/locks/',
        ];
    }
}
