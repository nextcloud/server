<?php

/**
 * Locking plugin
 *
 * This plugin provides locking support to a WebDAV server.
 * The easiest way to get started, is by hooking it up as such:
 *
 * $lockBackend = new Sabre_DAV_Locks_Backend_File('./mylockdb');
 * $lockPlugin = new Sabre_DAV_Locks_Plugin($lockBackend);
 * $server->addPlugin($lockPlugin);
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Locks_Plugin extends Sabre_DAV_ServerPlugin {

    /**
     * locksBackend
     *
     * @var Sabre_DAV_Locks_Backend_Abstract
     */
    private $locksBackend;

    /**
     * server
     *
     * @var Sabre_DAV_Server
     */
    private $server;

    /**
     * __construct
     *
     * @param Sabre_DAV_Locks_Backend_Abstract $locksBackend
     */
    public function __construct(Sabre_DAV_Locks_Backend_Abstract $locksBackend = null) {

        $this->locksBackend = $locksBackend;

    }

    /**
     * Initializes the plugin
     *
     * This method is automatically called by the Server class after addPlugin.
     *
     * @param Sabre_DAV_Server $server
     * @return void
     */
    public function initialize(Sabre_DAV_Server $server) {

        $this->server = $server;
        $server->subscribeEvent('unknownMethod',array($this,'unknownMethod'));
        $server->subscribeEvent('beforeMethod',array($this,'beforeMethod'),50);
        $server->subscribeEvent('afterGetProperties',array($this,'afterGetProperties'));

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

        return 'locks';

    }

    /**
     * This method is called by the Server if the user used an HTTP method
     * the server didn't recognize.
     *
     * This plugin intercepts the LOCK and UNLOCK methods.
     *
     * @param string $method
     * @param string $uri
     * @return bool
     */
    public function unknownMethod($method, $uri) {

        switch($method) {

            case 'LOCK'   : $this->httpLock($uri); return false;
            case 'UNLOCK' : $this->httpUnlock($uri); return false;

        }

    }

    /**
     * This method is called after most properties have been found
     * it allows us to add in any Lock-related properties
     *
     * @param string $path
     * @param array $newProperties
     * @return bool
     */
    public function afterGetProperties($path, &$newProperties) {

        foreach($newProperties[404] as $propName=>$discard) {

            switch($propName) {

                case '{DAV:}supportedlock' :
                    $val = false;
                    if ($this->locksBackend) $val = true;
                    $newProperties[200][$propName] = new Sabre_DAV_Property_SupportedLock($val);
                    unset($newProperties[404][$propName]);
                    break;

                case '{DAV:}lockdiscovery' :
                    $newProperties[200][$propName] = new Sabre_DAV_Property_LockDiscovery($this->getLocks($path));
                    unset($newProperties[404][$propName]);
                    break;

            }


        }
        return true;

    }


    /**
     * This method is called before the logic for any HTTP method is
     * handled.
     *
     * This plugin uses that feature to intercept access to locked resources.
     *
     * @param string $method
     * @param string $uri
     * @return bool
     */
    public function beforeMethod($method, $uri) {

        switch($method) {

            case 'DELETE' :
                $lastLock = null;
                if (!$this->validateLock($uri,$lastLock, true))
                    throw new Sabre_DAV_Exception_Locked($lastLock);
                break;
            case 'MKCOL' :
            case 'PROPPATCH' :
            case 'PUT' :
                $lastLock = null;
                if (!$this->validateLock($uri,$lastLock))
                    throw new Sabre_DAV_Exception_Locked($lastLock);
                break;
            case 'MOVE' :
                $lastLock = null;
                if (!$this->validateLock(array(
                      $uri,
                      $this->server->calculateUri($this->server->httpRequest->getHeader('Destination')),
                    ),$lastLock, true))
                        throw new Sabre_DAV_Exception_Locked($lastLock);
                break;
            case 'COPY' :
                $lastLock = null;
                if (!$this->validateLock(
                      $this->server->calculateUri($this->server->httpRequest->getHeader('Destination')),
                      $lastLock, true))
                        throw new Sabre_DAV_Exception_Locked($lastLock);
                break;
        }

        return true;

    }

    /**
     * Use this method to tell the server this plugin defines additional
     * HTTP methods.
     *
     * This method is passed a uri. It should only return HTTP methods that are
     * available for the specified uri.
     *
     * @param string $uri
     * @return array
     */
    public function getHTTPMethods($uri) {

        if ($this->locksBackend)
            return array('LOCK','UNLOCK');

        return array();

    }

    /**
     * Returns a list of features for the HTTP OPTIONS Dav: header.
     *
     * In this case this is only the number 2. The 2 in the Dav: header
     * indicates the server supports locks.
     *
     * @return array
     */
    public function getFeatures() {

        return array(2);

    }

    /**
     * Returns all lock information on a particular uri
     *
     * This function should return an array with Sabre_DAV_Locks_LockInfo objects. If there are no locks on a file, return an empty array.
     *
     * Additionally there is also the possibility of locks on parent nodes, so we'll need to traverse every part of the tree
     * If the $returnChildLocks argument is set to true, we'll also traverse all the children of the object
     * for any possible locks and return those as well.
     *
     * @param string $uri
     * @param bool $returnChildLocks
     * @return array
     */
    public function getLocks($uri, $returnChildLocks = false) {

        $lockList = array();

        if ($this->locksBackend)
            $lockList = array_merge($lockList,$this->locksBackend->getLocks($uri, $returnChildLocks));

        return $lockList;

    }

    /**
     * Locks an uri
     *
     * The WebDAV lock request can be operated to either create a new lock on a file, or to refresh an existing lock
     * If a new lock is created, a full XML body should be supplied, containing information about the lock such as the type
     * of lock (shared or exclusive) and the owner of the lock
     *
     * If a lock is to be refreshed, no body should be supplied and there should be a valid If header containing the lock
     *
     * Additionally, a lock can be requested for a non-existent file. In these case we're obligated to create an empty file as per RFC4918:S7.3
     *
     * @param string $uri
     * @return void
     */
    protected function httpLock($uri) {

        $lastLock = null;
        if (!$this->validateLock($uri,$lastLock)) {

            // If the existing lock was an exclusive lock, we need to fail
            if (!$lastLock || $lastLock->scope == Sabre_DAV_Locks_LockInfo::EXCLUSIVE) {
                //var_dump($lastLock);
                throw new Sabre_DAV_Exception_ConflictingLock($lastLock);
            }

        }

        if ($body = $this->server->httpRequest->getBody(true)) {
            // This is a new lock request
            $lockInfo = $this->parseLockRequest($body);
            $lockInfo->depth = $this->server->getHTTPDepth();
            $lockInfo->uri = $uri;
            if($lastLock && $lockInfo->scope != Sabre_DAV_Locks_LockInfo::SHARED) throw new Sabre_DAV_Exception_ConflictingLock($lastLock);

        } elseif ($lastLock) {

            // This must have been a lock refresh
            $lockInfo = $lastLock;

            // The resource could have been locked through another uri.
            if ($uri!=$lockInfo->uri) $uri = $lockInfo->uri;

        } else {

            // There was neither a lock refresh nor a new lock request
            throw new Sabre_DAV_Exception_BadRequest('An xml body is required for lock requests');

        }

        if ($timeout = $this->getTimeoutHeader()) $lockInfo->timeout = $timeout;

        $newFile = false;

        // If we got this far.. we should go check if this node actually exists. If this is not the case, we need to create it first
        try {
            $this->server->tree->getNodeForPath($uri);

            // We need to call the beforeWriteContent event for RFC3744
            $this->server->broadcastEvent('beforeWriteContent',array($uri));

        } catch (Sabre_DAV_Exception_NotFound $e) {

            // It didn't, lets create it
            $this->server->createFile($uri,fopen('php://memory','r'));
            $newFile = true;

        }

        $this->lockNode($uri,$lockInfo);

        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->setHeader('Lock-Token','<opaquelocktoken:' . $lockInfo->token . '>');
        $this->server->httpResponse->sendStatus($newFile?201:200);
        $this->server->httpResponse->sendBody($this->generateLockResponse($lockInfo));

    }

    /**
     * Unlocks a uri
     *
     * This WebDAV method allows you to remove a lock from a node. The client should provide a valid locktoken through the Lock-token http header
     * The server should return 204 (No content) on success
     *
     * @param string $uri
     * @return void
     */
    protected function httpUnlock($uri) {

        $lockToken = $this->server->httpRequest->getHeader('Lock-Token');

        // If the locktoken header is not supplied, we need to throw a bad request exception
        if (!$lockToken) throw new Sabre_DAV_Exception_BadRequest('No lock token was supplied');

        $locks = $this->getLocks($uri);

        // Windows sometimes forgets to include < and > in the Lock-Token
        // header
        if ($lockToken[0]!=='<') $lockToken = '<' . $lockToken . '>';

        foreach($locks as $lock) {

            if ('<opaquelocktoken:' . $lock->token . '>' == $lockToken) {

                $this->unlockNode($uri,$lock);
                $this->server->httpResponse->setHeader('Content-Length','0');
                $this->server->httpResponse->sendStatus(204);
                return;

            }

        }

        // If we got here, it means the locktoken was invalid
        throw new Sabre_DAV_Exception_LockTokenMatchesRequestUri();

    }

    /**
     * Locks a uri
     *
     * All the locking information is supplied in the lockInfo object. The object has a suggested timeout, but this can be safely ignored
     * It is important that if the existing timeout is ignored, the property is overwritten, as this needs to be sent back to the client
     *
     * @param string $uri
     * @param Sabre_DAV_Locks_LockInfo $lockInfo
     * @return bool
     */
    public function lockNode($uri,Sabre_DAV_Locks_LockInfo $lockInfo) {

        if (!$this->server->broadcastEvent('beforeLock',array($uri,$lockInfo))) return;

        if ($this->locksBackend) return $this->locksBackend->lock($uri,$lockInfo);
        throw new Sabre_DAV_Exception_MethodNotAllowed('Locking support is not enabled for this resource. No Locking backend was found so if you didn\'t expect this error, please check your configuration.');

    }

    /**
     * Unlocks a uri
     *
     * This method removes a lock from a uri. It is assumed all the supplied information is correct and verified
     *
     * @param string $uri
     * @param Sabre_DAV_Locks_LockInfo $lockInfo
     * @return bool
     */
    public function unlockNode($uri,Sabre_DAV_Locks_LockInfo $lockInfo) {

        if (!$this->server->broadcastEvent('beforeUnlock',array($uri,$lockInfo))) return;
        if ($this->locksBackend) return $this->locksBackend->unlock($uri,$lockInfo);

    }


    /**
     * Returns the contents of the HTTP Timeout header.
     *
     * The method formats the header into an integer.
     *
     * @return int
     */
    public function getTimeoutHeader() {

        $header = $this->server->httpRequest->getHeader('Timeout');

        if ($header) {

            if (stripos($header,'second-')===0) $header = (int)(substr($header,7));
            else if (strtolower($header)=='infinite') $header=Sabre_DAV_Locks_LockInfo::TIMEOUT_INFINITE;
            else throw new Sabre_DAV_Exception_BadRequest('Invalid HTTP timeout header');

        } else {

            $header = 0;

        }

        return $header;

    }

    /**
     * Generates the response for successful LOCK requests
     *
     * @param Sabre_DAV_Locks_LockInfo $lockInfo
     * @return string
     */
    protected function generateLockResponse(Sabre_DAV_Locks_LockInfo $lockInfo) {

        $dom = new DOMDocument('1.0','utf-8');
        $dom->formatOutput = true;

        $prop = $dom->createElementNS('DAV:','d:prop');
        $dom->appendChild($prop);

        $lockDiscovery = $dom->createElementNS('DAV:','d:lockdiscovery');
        $prop->appendChild($lockDiscovery);

        $lockObj = new Sabre_DAV_Property_LockDiscovery(array($lockInfo),true);
        $lockObj->serialize($this->server,$lockDiscovery);

        return $dom->saveXML();

    }

    /**
     * validateLock should be called when a write operation is about to happen
     * It will check if the requested url is locked, and see if the correct lock tokens are passed
     *
     * @param mixed $urls List of relevant urls. Can be an array, a string or nothing at all for the current request uri
     * @param mixed $lastLock This variable will be populated with the last checked lock object (Sabre_DAV_Locks_LockInfo)
     * @param bool $checkChildLocks If set to true, this function will also look for any locks set on child resources of the supplied urls. This is needed for for example deletion of entire trees.
     * @return bool
     */
    protected function validateLock($urls = null,&$lastLock = null, $checkChildLocks = false) {

        if (is_null($urls)) {
            $urls = array($this->server->getRequestUri());
        } elseif (is_string($urls)) {
            $urls = array($urls);
        } elseif (!is_array($urls)) {
            throw new Sabre_DAV_Exception('The urls parameter should either be null, a string or an array');
        }

        $conditions = $this->getIfConditions();

        // We're going to loop through the urls and make sure all lock conditions are satisfied
        foreach($urls as $url) {

            $locks = $this->getLocks($url, $checkChildLocks);

            // If there were no conditions, but there were locks, we fail
            if (!$conditions && $locks) {
                reset($locks);
                $lastLock = current($locks);
                return false;
            }

            // If there were no locks or conditions, we go to the next url
            if (!$locks && !$conditions) continue;

            foreach($conditions as $condition) {

                if (!$condition['uri']) {
                    $conditionUri = $this->server->getRequestUri();
                } else {
                    $conditionUri = $this->server->calculateUri($condition['uri']);
                }

                // If the condition has a url, and it isn't part of the affected url at all, check the next condition
                if ($conditionUri && strpos($url,$conditionUri)!==0) continue;

                // The tokens array contians arrays with 2 elements. 0=true/false for normal/not condition, 1=locktoken
                // At least 1 condition has to be satisfied
                foreach($condition['tokens'] as $conditionToken) {

                    $etagValid = true;
                    $lockValid  = true;

                    // key 2 can contain an etag
                    if ($conditionToken[2]) {

                        $uri = $conditionUri?$conditionUri:$this->server->getRequestUri();
                        $node = $this->server->tree->getNodeForPath($uri);
                        $etagValid = $node->getETag()==$conditionToken[2];

                    }

                    // key 1 can contain a lock token
                    if ($conditionToken[1]) {

                        $lockValid = false;
                        // Match all the locks
                        foreach($locks as $lockIndex=>$lock) {

                            $lockToken = 'opaquelocktoken:' . $lock->token;

                            // Checking NOT
                            if (!$conditionToken[0] && $lockToken != $conditionToken[1]) {

                                // Condition valid, onto the next
                                $lockValid = true;
                                break;
                            }
                            if ($conditionToken[0] && $lockToken == $conditionToken[1]) {

                                $lastLock = $lock;
                                // Condition valid and lock matched
                                unset($locks[$lockIndex]);
                                $lockValid = true;
                                break;

                            }

                        }

                    }

                    // If, after checking both etags and locks they are stil valid,
                    // we can continue with the next condition.
                    if ($etagValid && $lockValid) continue 2;
               }
               // No conditions matched, so we fail
               throw new Sabre_DAV_Exception_PreconditionFailed('The tokens provided in the if header did not match','If');
            }

            // Conditions were met, we'll also need to check if all the locks are gone
            if (count($locks)) {

                reset($locks);

                // There's still locks, we fail
                $lastLock = current($locks);
                return false;

            }


        }

        // We got here, this means every condition was satisfied
        return true;

    }

    /**
     * This method is created to extract information from the WebDAV HTTP 'If:' header
     *
     * The If header can be quite complex, and has a bunch of features. We're using a regex to extract all relevant information
     * The function will return an array, containing structs with the following keys
     *
     *   * uri   - the uri the condition applies to. If this is returned as an
     *     empty string, this implies it's referring to the request url.
     *   * tokens - The lock token. another 2 dimensional array containing 2 elements (0 = true/false.. If this is a negative condition its set to false, 1 = the actual token)
     *   * etag - an etag, if supplied
     *
     * @return array
     */
    public function getIfConditions() {

        $header = $this->server->httpRequest->getHeader('If');
        if (!$header) return array();

        $matches = array();

        $regex = '/(?:\<(?P<uri>.*?)\>\s)?\((?P<not>Not\s)?(?:\<(?P<token>[^\>]*)\>)?(?:\s?)(?:\[(?P<etag>[^\]]*)\])?\)/im';
        preg_match_all($regex,$header,$matches,PREG_SET_ORDER);

        $conditions = array();

        foreach($matches as $match) {

            $condition = array(
                'uri'   => $match['uri'],
                'tokens' => array(
                    array($match['not']?0:1,$match['token'],isset($match['etag'])?$match['etag']:'')
                ),
            );

            if (!$condition['uri'] && count($conditions)) $conditions[count($conditions)-1]['tokens'][] = array(
                $match['not']?0:1,
                $match['token'],
                isset($match['etag'])?$match['etag']:''
            );
            else {
                $conditions[] = $condition;
            }

        }

        return $conditions;

    }

    /**
     * Parses a webdav lock xml body, and returns a new Sabre_DAV_Locks_LockInfo object
     *
     * @param string $body
     * @return Sabre_DAV_Locks_LockInfo
     */
    protected function parseLockRequest($body) {

        $xml = simplexml_load_string($body,null,LIBXML_NOWARNING);
        $xml->registerXPathNamespace('d','DAV:');
        $lockInfo = new Sabre_DAV_Locks_LockInfo();

        $children = $xml->children("DAV:");
        $lockInfo->owner = (string)$children->owner;

        $lockInfo->token = Sabre_DAV_UUIDUtil::getUUID();
        $lockInfo->scope = count($xml->xpath('d:lockscope/d:exclusive'))>0?Sabre_DAV_Locks_LockInfo::EXCLUSIVE:Sabre_DAV_Locks_LockInfo::SHARED;

        return $lockInfo;

    }


}
