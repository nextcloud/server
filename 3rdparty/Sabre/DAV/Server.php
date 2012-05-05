<?php

/**
 * Main DAV server class
 *
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_Server {

    /**
     * Inifinity is used for some request supporting the HTTP Depth header and indicates that the operation should traverse the entire tree
     */
    const DEPTH_INFINITY = -1;

    /**
     * Nodes that are files, should have this as the type property
     */
    const NODE_FILE = 1;

    /**
     * Nodes that are directories, should use this value as the type property
     */
    const NODE_DIRECTORY = 2;

    /**
     * XML namespace for all SabreDAV related elements
     */
    const NS_SABREDAV = 'http://sabredav.org/ns';

    /**
     * The tree object
     *
     * @var Sabre_DAV_Tree
     */
    public $tree;

    /**
     * The base uri
     *
     * @var string
     */
    protected $baseUri = null;

    /**
     * httpResponse
     *
     * @var Sabre_HTTP_Response
     */
    public $httpResponse;

    /**
     * httpRequest
     *
     * @var Sabre_HTTP_Request
     */
    public $httpRequest;

    /**
     * The list of plugins
     *
     * @var array
     */
    protected $plugins = array();

    /**
     * This array contains a list of callbacks we should call when certain events are triggered
     *
     * @var array
     */
    protected $eventSubscriptions = array();

    /**
     * This is a default list of namespaces.
     *
     * If you are defining your own custom namespace, add it here to reduce
     * bandwidth and improve legibility of xml bodies.
     *
     * @var array
     */
    public $xmlNamespaces = array(
        'DAV:' => 'd',
        'http://sabredav.org/ns' => 's',
    );

    /**
     * The propertymap can be used to map properties from
     * requests to property classes.
     *
     * @var array
     */
    public $propertyMap = array(
        '{DAV:}resourcetype' => 'Sabre_DAV_Property_ResourceType',
    );

    public $protectedProperties = array(
        // RFC4918
        '{DAV:}getcontentlength',
        '{DAV:}getetag',
        '{DAV:}getlastmodified',
        '{DAV:}lockdiscovery',
        '{DAV:}resourcetype',
        '{DAV:}supportedlock',

        // RFC4331
        '{DAV:}quota-available-bytes',
        '{DAV:}quota-used-bytes',

        // RFC3744
        '{DAV:}supported-privilege-set',
        '{DAV:}current-user-privilege-set',
        '{DAV:}acl',
        '{DAV:}acl-restrictions',
        '{DAV:}inherited-acl-set',

    );

    /**
     * This is a flag that allow or not showing file, line and code
     * of the exception in the returned XML
     *
     * @var bool
     */
    public $debugExceptions = false;

    /**
     * This property allows you to automatically add the 'resourcetype' value
     * based on a node's classname or interface.
     *
     * The preset ensures that {DAV:}collection is automaticlly added for nodes
     * implementing Sabre_DAV_ICollection.
     *
     * @var array
     */
    public $resourceTypeMapping = array(
        'Sabre_DAV_ICollection' => '{DAV:}collection',
    );

    /**
     * If this setting is turned off, SabreDAV's version number will be hidden
     * from various places.
     *
     * Some people feel this is a good security measure.
     *
     * @var bool
     */
    static public $exposeVersion = true;

    /**
     * Sets up the server
     *
     * If a Sabre_DAV_Tree object is passed as an argument, it will
     * use it as the directory tree. If a Sabre_DAV_INode is passed, it
     * will create a Sabre_DAV_ObjectTree and use the node as the root.
     *
     * If nothing is passed, a Sabre_DAV_SimpleCollection is created in
     * a Sabre_DAV_ObjectTree.
     *
     * If an array is passed, we automatically create a root node, and use
     * the nodes in the array as top-level children.
     *
     * @param Sabre_DAV_Tree|Sabre_DAV_INode|null $treeOrNode The tree object
     */
    public function __construct($treeOrNode = null) {

        if ($treeOrNode instanceof Sabre_DAV_Tree) {
            $this->tree = $treeOrNode;
        } elseif ($treeOrNode instanceof Sabre_DAV_INode) {
            $this->tree = new Sabre_DAV_ObjectTree($treeOrNode);
        } elseif (is_array($treeOrNode)) {

            // If it's an array, a list of nodes was passed, and we need to
            // create the root node.
            foreach($treeOrNode as $node) {
                if (!($node instanceof Sabre_DAV_INode)) {
                    throw new Sabre_DAV_Exception('Invalid argument passed to constructor. If you\'re passing an array, all the values must implement Sabre_DAV_INode');
                }
            }

            $root = new Sabre_DAV_SimpleCollection('root', $treeOrNode);
            $this->tree = new Sabre_DAV_ObjectTree($root);

        } elseif (is_null($treeOrNode)) {
            $root = new Sabre_DAV_SimpleCollection('root');
            $this->tree = new Sabre_DAV_ObjectTree($root);
        } else {
            throw new Sabre_DAV_Exception('Invalid argument passed to constructor. Argument must either be an instance of Sabre_DAV_Tree, Sabre_DAV_INode, an array or null');
        }
        $this->httpResponse = new Sabre_HTTP_Response();
        $this->httpRequest = new Sabre_HTTP_Request();

    }

    /**
     * Starts the DAV Server
     *
     * @return void
     */
    public function exec() {

        try {

            $this->invokeMethod($this->httpRequest->getMethod(), $this->getRequestUri());

        } catch (Exception $e) {

            $DOM = new DOMDocument('1.0','utf-8');
            $DOM->formatOutput = true;

            $error = $DOM->createElementNS('DAV:','d:error');
            $error->setAttribute('xmlns:s',self::NS_SABREDAV);
            $DOM->appendChild($error);

            $error->appendChild($DOM->createElement('s:exception',get_class($e)));
            $error->appendChild($DOM->createElement('s:message',htmlentities($e->getMessage())));
            if ($this->debugExceptions) {
                $error->appendChild($DOM->createElement('s:file',$e->getFile()));
                $error->appendChild($DOM->createElement('s:line',$e->getLine()));
                $error->appendChild($DOM->createElement('s:code',$e->getCode()));
                $error->appendChild($DOM->createElement('s:stacktrace',$e->getTraceAsString()));

            }
            if (self::$exposeVersion) {
                $error->appendChild($DOM->createElement('s:sabredav-version',Sabre_DAV_Version::VERSION));
            }

            if($e instanceof Sabre_DAV_Exception) {

                $httpCode = $e->getHTTPCode();
                $e->serialize($this,$error);
                $headers = $e->getHTTPHeaders($this);

            } else {

                $httpCode = 500;
                $headers = array();

            }
            $headers['Content-Type'] = 'application/xml; charset=utf-8';

            $this->httpResponse->sendStatus($httpCode);
            $this->httpResponse->setHeaders($headers);
            $this->httpResponse->sendBody($DOM->saveXML());

        }

    }

    /**
     * Sets the base server uri
     *
     * @param string $uri
     * @return void
     */
    public function setBaseUri($uri) {

        // If the baseUri does not end with a slash, we must add it
        if ($uri[strlen($uri)-1]!=='/')
            $uri.='/';

        $this->baseUri = $uri;

    }

    /**
     * Returns the base responding uri
     *
     * @return string
     */
    public function getBaseUri() {

        if (is_null($this->baseUri)) $this->baseUri = $this->guessBaseUri();
        return $this->baseUri;

    }

    /**
     * This method attempts to detect the base uri.
     * Only the PATH_INFO variable is considered.
     *
     * If this variable is not set, the root (/) is assumed.
     *
     * @return string
     */
    public function guessBaseUri() {

        $pathInfo = $this->httpRequest->getRawServerValue('PATH_INFO');
        $uri = $this->httpRequest->getRawServerValue('REQUEST_URI');

        // If PATH_INFO is found, we can assume it's accurate.
        if (!empty($pathInfo)) {

            // We need to make sure we ignore the QUERY_STRING part
            if ($pos = strpos($uri,'?'))
                $uri = substr($uri,0,$pos);

            // PATH_INFO is only set for urls, such as: /example.php/path
            // in that case PATH_INFO contains '/path'.
            // Note that REQUEST_URI is percent encoded, while PATH_INFO is
            // not, Therefore they are only comparable if we first decode
            // REQUEST_INFO as well.
            $decodedUri = Sabre_DAV_URLUtil::decodePath($uri);

            // A simple sanity check:
            if(substr($decodedUri,strlen($decodedUri)-strlen($pathInfo))===$pathInfo) {
                $baseUri = substr($decodedUri,0,strlen($decodedUri)-strlen($pathInfo));
                return rtrim($baseUri,'/') . '/';
            }

            throw new Sabre_DAV_Exception('The REQUEST_URI ('. $uri . ') did not end with the contents of PATH_INFO (' . $pathInfo . '). This server might be misconfigured.');

        }

        // The last fallback is that we're just going to assume the server root.
        return '/';

    }

    /**
     * Adds a plugin to the server
     *
     * For more information, console the documentation of Sabre_DAV_ServerPlugin
     *
     * @param Sabre_DAV_ServerPlugin $plugin
     * @return void
     */
    public function addPlugin(Sabre_DAV_ServerPlugin $plugin) {

        $this->plugins[$plugin->getPluginName()] = $plugin;
        $plugin->initialize($this);

    }

    /**
     * Returns an initialized plugin by it's name.
     *
     * This function returns null if the plugin was not found.
     *
     * @param string $name
     * @return Sabre_DAV_ServerPlugin
     */
    public function getPlugin($name) {

        if (isset($this->plugins[$name]))
            return $this->plugins[$name];

        // This is a fallback and deprecated.
        foreach($this->plugins as $plugin) {
            if (get_class($plugin)===$name) return $plugin;
        }

        return null;

    }

    /**
     * Returns all plugins
     *
     * @return array
     */
    public function getPlugins() {

        return $this->plugins;

    }


    /**
     * Subscribe to an event.
     *
     * When the event is triggered, we'll call all the specified callbacks.
     * It is possible to control the order of the callbacks through the
     * priority argument.
     *
     * This is for example used to make sure that the authentication plugin
     * is triggered before anything else. If it's not needed to change this
     * number, it is recommended to ommit.
     *
     * @param string $event
     * @param callback $callback
     * @param int $priority
     * @return void
     */
    public function subscribeEvent($event, $callback, $priority = 100) {

        if (!isset($this->eventSubscriptions[$event])) {
            $this->eventSubscriptions[$event] = array();
        }
        while(isset($this->eventSubscriptions[$event][$priority])) $priority++;
        $this->eventSubscriptions[$event][$priority] = $callback;
        ksort($this->eventSubscriptions[$event]);

    }

    /**
     * Broadcasts an event
     *
     * This method will call all subscribers. If one of the subscribers returns false, the process stops.
     *
     * The arguments parameter will be sent to all subscribers
     *
     * @param string $eventName
     * @param array $arguments
     * @return bool
     */
    public function broadcastEvent($eventName,$arguments = array()) {

        if (isset($this->eventSubscriptions[$eventName])) {

            foreach($this->eventSubscriptions[$eventName] as $subscriber) {

                $result = call_user_func_array($subscriber,$arguments);
                if ($result===false) return false;

            }

        }

        return true;

    }

    /**
     * Handles a http request, and execute a method based on its name
     *
     * @param string $method
     * @param string $uri
     * @return void
     */
    public function invokeMethod($method, $uri) {

        $method = strtoupper($method);

        if (!$this->broadcastEvent('beforeMethod',array($method, $uri))) return;

        // Make sure this is a HTTP method we support
        $internalMethods = array(
            'OPTIONS',
            'GET',
            'HEAD',
            'DELETE',
            'PROPFIND',
            'MKCOL',
            'PUT',
            'PROPPATCH',
            'COPY',
            'MOVE',
            'REPORT'
        );

        if (in_array($method,$internalMethods)) {

            call_user_func(array($this,'http' . $method), $uri);

        } else {

            if ($this->broadcastEvent('unknownMethod',array($method, $uri))) {
                // Unsupported method
                throw new Sabre_DAV_Exception_NotImplemented('There was no handler found for this "' . $method . '" method');
            }

        }

    }

    // {{{ HTTP Method implementations

    /**
     * HTTP OPTIONS
     *
     * @param string $uri
     * @return void
     */
    protected function httpOptions($uri) {

        $methods = $this->getAllowedMethods($uri);

        $this->httpResponse->setHeader('Allow',strtoupper(implode(', ',$methods)));
        $features = array('1','3', 'extended-mkcol');

        foreach($this->plugins as $plugin) $features = array_merge($features,$plugin->getFeatures());

        $this->httpResponse->setHeader('DAV',implode(', ',$features));
        $this->httpResponse->setHeader('MS-Author-Via','DAV');
        $this->httpResponse->setHeader('Accept-Ranges','bytes');
        if (self::$exposeVersion) {
            $this->httpResponse->setHeader('X-Sabre-Version',Sabre_DAV_Version::VERSION);
        }
        $this->httpResponse->setHeader('Content-Length',0);
        $this->httpResponse->sendStatus(200);

    }

    /**
     * HTTP GET
     *
     * This method simply fetches the contents of a uri, like normal
     *
     * @param string $uri
     * @return bool
     */
    protected function httpGet($uri) {

        $node = $this->tree->getNodeForPath($uri,0);

        if (!$this->checkPreconditions(true)) return false;

        if (!($node instanceof Sabre_DAV_IFile)) throw new Sabre_DAV_Exception_NotImplemented('GET is only implemented on File objects');
        $body = $node->get();

        // Converting string into stream, if needed.
        if (is_string($body)) {
            $stream = fopen('php://temp','r+');
            fwrite($stream,$body);
            rewind($stream);
            $body = $stream;
        }

        /*
         * TODO: getetag, getlastmodified, getsize should also be used using
         * this method
         */
        $httpHeaders = $this->getHTTPHeaders($uri);

        /* ContentType needs to get a default, because many webservers will otherwise
         * default to text/html, and we don't want this for security reasons.
         */
        if (!isset($httpHeaders['Content-Type'])) {
            $httpHeaders['Content-Type'] = 'application/octet-stream';
        }


        if (isset($httpHeaders['Content-Length'])) {

            $nodeSize = $httpHeaders['Content-Length'];

            // Need to unset Content-Length, because we'll handle that during figuring out the range
            unset($httpHeaders['Content-Length']);

        } else {
            $nodeSize = null;
        }

        $this->httpResponse->setHeaders($httpHeaders);

        $range = $this->getHTTPRange();
        $ifRange = $this->httpRequest->getHeader('If-Range');
        $ignoreRangeHeader = false;

        // If ifRange is set, and range is specified, we first need to check
        // the precondition.
        if ($nodeSize && $range && $ifRange) {

            // if IfRange is parsable as a date we'll treat it as a DateTime
            // otherwise, we must treat it as an etag.
            try {
                $ifRangeDate = new DateTime($ifRange);

                // It's a date. We must check if the entity is modified since
                // the specified date.
                if (!isset($httpHeaders['Last-Modified'])) $ignoreRangeHeader = true;
                else {
                    $modified = new DateTime($httpHeaders['Last-Modified']);
                    if($modified > $ifRangeDate) $ignoreRangeHeader = true;
                }

            } catch (Exception $e) {

                // It's an entity. We can do a simple comparison.
                if (!isset($httpHeaders['ETag'])) $ignoreRangeHeader = true;
                elseif ($httpHeaders['ETag']!==$ifRange) $ignoreRangeHeader = true;
            }
        }

        // We're only going to support HTTP ranges if the backend provided a filesize
        if (!$ignoreRangeHeader && $nodeSize && $range) {

            // Determining the exact byte offsets
            if (!is_null($range[0])) {

                $start = $range[0];
                $end = $range[1]?$range[1]:$nodeSize-1;
                if($start >= $nodeSize)
                    throw new Sabre_DAV_Exception_RequestedRangeNotSatisfiable('The start offset (' . $range[0] . ') exceeded the size of the entity (' . $nodeSize . ')');

                if($end < $start) throw new Sabre_DAV_Exception_RequestedRangeNotSatisfiable('The end offset (' . $range[1] . ') is lower than the start offset (' . $range[0] . ')');
                if($end >= $nodeSize) $end = $nodeSize-1;

            } else {

                $start = $nodeSize-$range[1];
                $end  = $nodeSize-1;

                if ($start<0) $start = 0;

            }

            // New read/write stream
            $newStream = fopen('php://temp','r+');

            stream_copy_to_stream($body, $newStream, $end-$start+1, $start);
            rewind($newStream);

            $this->httpResponse->setHeader('Content-Length', $end-$start+1);
            $this->httpResponse->setHeader('Content-Range','bytes ' . $start . '-' . $end . '/' . $nodeSize);
            $this->httpResponse->sendStatus(206);
            $this->httpResponse->sendBody($newStream);


        } else {

            if ($nodeSize) $this->httpResponse->setHeader('Content-Length',$nodeSize);
            $this->httpResponse->sendStatus(200);
            $this->httpResponse->sendBody($body);

        }

    }

    /**
     * HTTP HEAD
     *
     * This method is normally used to take a peak at a url, and only get the HTTP response headers, without the body
     * This is used by clients to determine if a remote file was changed, so they can use a local cached version, instead of downloading it again
     *
     * @param string $uri
     * @return void
     */
    protected function httpHead($uri) {

        $node = $this->tree->getNodeForPath($uri);
        /* This information is only collection for File objects.
         * Ideally we want to throw 405 Method Not Allowed for every
         * non-file, but MS Office does not like this
         */
        if ($node instanceof Sabre_DAV_IFile) {
            $headers = $this->getHTTPHeaders($this->getRequestUri());
            if (!isset($headers['Content-Type'])) {
                $headers['Content-Type'] = 'application/octet-stream';
            }
            $this->httpResponse->setHeaders($headers);
        }
        $this->httpResponse->sendStatus(200);

    }

    /**
     * HTTP Delete
     *
     * The HTTP delete method, deletes a given uri
     *
     * @param string $uri
     * @return void
     */
    protected function httpDelete($uri) {

        if (!$this->broadcastEvent('beforeUnbind',array($uri))) return;
        $this->tree->delete($uri);
        $this->broadcastEvent('afterUnbind',array($uri));

        $this->httpResponse->sendStatus(204);
        $this->httpResponse->setHeader('Content-Length','0');

    }


    /**
     * WebDAV PROPFIND
     *
     * This WebDAV method requests information about an uri resource, or a list of resources
     * If a client wants to receive the properties for a single resource it will add an HTTP Depth: header with a 0 value
     * If the value is 1, it means that it also expects a list of sub-resources (e.g.: files in a directory)
     *
     * The request body contains an XML data structure that has a list of properties the client understands
     * The response body is also an xml document, containing information about every uri resource and the requested properties
     *
     * It has to return a HTTP 207 Multi-status status code
     *
     * @param string $uri
     * @return void
     */
    protected function httpPropfind($uri) {

        // $xml = new Sabre_DAV_XMLReader(file_get_contents('php://input'));
        $requestedProperties = $this->parsePropfindRequest($this->httpRequest->getBody(true));

        $depth = $this->getHTTPDepth(1);
        // The only two options for the depth of a propfind is 0 or 1
        if ($depth!=0) $depth = 1;

        $newProperties = $this->getPropertiesForPath($uri,$requestedProperties,$depth);

        // This is a multi-status response
        $this->httpResponse->sendStatus(207);
        $this->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');

        // Normally this header is only needed for OPTIONS responses, however..
        // iCal seems to also depend on these being set for PROPFIND. Since
        // this is not harmful, we'll add it.
        $features = array('1','3', 'extended-mkcol');
        foreach($this->plugins as $plugin) $features = array_merge($features,$plugin->getFeatures());
        $this->httpResponse->setHeader('DAV',implode(', ',$features));

        $data = $this->generateMultiStatus($newProperties);
        $this->httpResponse->sendBody($data);

    }

    /**
     * WebDAV PROPPATCH
     *
     * This method is called to update properties on a Node. The request is an XML body with all the mutations.
     * In this XML body it is specified which properties should be set/updated and/or deleted
     *
     * @param string $uri
     * @return void
     */
    protected function httpPropPatch($uri) {

        $newProperties = $this->parsePropPatchRequest($this->httpRequest->getBody(true));

        $result = $this->updateProperties($uri, $newProperties);

        $this->httpResponse->sendStatus(207);
        $this->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');

        $this->httpResponse->sendBody(
            $this->generateMultiStatus(array($result))
        );

    }

    /**
     * HTTP PUT method
     *
     * This HTTP method updates a file, or creates a new one.
     *
     * If a new resource was created, a 201 Created status code should be returned. If an existing resource is updated, it's a 204 No Content
     *
     * @param string $uri
     * @return bool
     */
    protected function httpPut($uri) {

        $body = $this->httpRequest->getBody();

        // Intercepting Content-Range
        if ($this->httpRequest->getHeader('Content-Range')) {
            /**
            Content-Range is dangerous for PUT requests:  PUT per definition
            stores a full resource.  draft-ietf-httpbis-p2-semantics-15 says
            in section 7.6:
              An origin server SHOULD reject any PUT request that contains a
              Content-Range header field, since it might be misinterpreted as
              partial content (or might be partial content that is being mistakenly
              PUT as a full representation).  Partial content updates are possible
              by targeting a separately identified resource with state that
              overlaps a portion of the larger resource, or by using a different
              method that has been specifically defined for partial updates (for
              example, the PATCH method defined in [RFC5789]).
            This clarifies RFC2616 section 9.6:
              The recipient of the entity MUST NOT ignore any Content-*
              (e.g. Content-Range) headers that it does not understand or implement
              and MUST return a 501 (Not Implemented) response in such cases.
            OTOH is a PUT request with a Content-Range currently the only way to
            continue an aborted upload request and is supported by curl, mod_dav,
            Tomcat and others.  Since some clients do use this feature which results
            in unexpected behaviour (cf PEAR::HTTP_WebDAV_Client 1.0.1), we reject
            all PUT requests with a Content-Range for now.
            */

            throw new Sabre_DAV_Exception_NotImplemented('PUT with Content-Range is not allowed.');
        }

        // Intercepting the Finder problem
        if (($expected = $this->httpRequest->getHeader('X-Expected-Entity-Length')) && $expected > 0) {

            /**
            Many webservers will not cooperate well with Finder PUT requests,
            because it uses 'Chunked' transfer encoding for the request body.

            The symptom of this problem is that Finder sends files to the
            server, but they arrive as 0-length files in PHP.

            If we don't do anything, the user might think they are uploading
            files successfully, but they end up empty on the server. Instead,
            we throw back an error if we detect this.

            The reason Finder uses Chunked, is because it thinks the files
            might change as it's being uploaded, and therefore the
            Content-Length can vary.

            Instead it sends the X-Expected-Entity-Length header with the size
            of the file at the very start of the request. If this header is set,
            but we don't get a request body we will fail the request to
            protect the end-user.
            */

            // Only reading first byte
            $firstByte = fread($body,1);
            if (strlen($firstByte)!==1) {
                throw new Sabre_DAV_Exception_Forbidden('This server is not compatible with OS/X finder. Consider using a different WebDAV client or webserver.');
            }

            // The body needs to stay intact, so we copy everything to a
            // temporary stream.

            $newBody = fopen('php://temp','r+');
            fwrite($newBody,$firstByte);
            stream_copy_to_stream($body, $newBody);
            rewind($newBody);

            $body = $newBody;

        }

        if ($this->tree->nodeExists($uri)) {

            $node = $this->tree->getNodeForPath($uri);

            // Checking If-None-Match and related headers.
            if (!$this->checkPreconditions()) return;

            // If the node is a collection, we'll deny it
            if (!($node instanceof Sabre_DAV_IFile)) throw new Sabre_DAV_Exception_Conflict('PUT is not allowed on non-files.');
            if (!$this->broadcastEvent('beforeWriteContent',array($uri, $node, &$body))) return false;

            $etag = $node->put($body);

            $this->broadcastEvent('afterWriteContent',array($uri, $node));

            $this->httpResponse->setHeader('Content-Length','0');
            if ($etag) $this->httpResponse->setHeader('ETag',$etag);
            $this->httpResponse->sendStatus(204);

        } else {

            $etag = null;
            // If we got here, the resource didn't exist yet.
            if (!$this->createFile($this->getRequestUri(),$body,$etag)) {
                // For one reason or another the file was not created.
                return;
            }

            $this->httpResponse->setHeader('Content-Length','0');
            if ($etag) $this->httpResponse->setHeader('ETag', $etag);
            $this->httpResponse->sendStatus(201);

        }

    }


    /**
     * WebDAV MKCOL
     *
     * The MKCOL method is used to create a new collection (directory) on the server
     *
     * @param string $uri
     * @return void
     */
    protected function httpMkcol($uri) {

        $requestBody = $this->httpRequest->getBody(true);

        if ($requestBody) {

            $contentType = $this->httpRequest->getHeader('Content-Type');
            if (strpos($contentType,'application/xml')!==0 && strpos($contentType,'text/xml')!==0) {

                // We must throw 415 for unsupported mkcol bodies
                throw new Sabre_DAV_Exception_UnsupportedMediaType('The request body for the MKCOL request must have an xml Content-Type');

            }

            $dom = Sabre_DAV_XMLUtil::loadDOMDocument($requestBody);
            if (Sabre_DAV_XMLUtil::toClarkNotation($dom->firstChild)!=='{DAV:}mkcol') {

                // We must throw 415 for unsupported mkcol bodies
                throw new Sabre_DAV_Exception_UnsupportedMediaType('The request body for the MKCOL request must be a {DAV:}mkcol request construct.');

            }

            $properties = array();
            foreach($dom->firstChild->childNodes as $childNode) {

                if (Sabre_DAV_XMLUtil::toClarkNotation($childNode)!=='{DAV:}set') continue;
                $properties = array_merge($properties, Sabre_DAV_XMLUtil::parseProperties($childNode, $this->propertyMap));

            }
            if (!isset($properties['{DAV:}resourcetype']))
                throw new Sabre_DAV_Exception_BadRequest('The mkcol request must include a {DAV:}resourcetype property');

            $resourceType = $properties['{DAV:}resourcetype']->getValue();
            unset($properties['{DAV:}resourcetype']);

        } else {

            $properties = array();
            $resourceType = array('{DAV:}collection');

        }

        $result = $this->createCollection($uri, $resourceType, $properties);

        if (is_array($result)) {
            $this->httpResponse->sendStatus(207);
            $this->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');

            $this->httpResponse->sendBody(
                $this->generateMultiStatus(array($result))
            );

        } else {
            $this->httpResponse->setHeader('Content-Length','0');
            $this->httpResponse->sendStatus(201);
        }

    }

    /**
     * WebDAV HTTP MOVE method
     *
     * This method moves one uri to a different uri. A lot of the actual request processing is done in getCopyMoveInfo
     *
     * @param string $uri
     * @return void
     */
    protected function httpMove($uri) {

        $moveInfo = $this->getCopyAndMoveInfo();

        // If the destination is part of the source tree, we must fail
        if ($moveInfo['destination']==$uri)
            throw new Sabre_DAV_Exception_Forbidden('Source and destination uri are identical.');

        if ($moveInfo['destinationExists']) {

            if (!$this->broadcastEvent('beforeUnbind',array($moveInfo['destination']))) return false;
            $this->tree->delete($moveInfo['destination']);
            $this->broadcastEvent('afterUnbind',array($moveInfo['destination']));

        }

        if (!$this->broadcastEvent('beforeUnbind',array($uri))) return false;
        if (!$this->broadcastEvent('beforeBind',array($moveInfo['destination']))) return false;
        $this->tree->move($uri,$moveInfo['destination']);
        $this->broadcastEvent('afterUnbind',array($uri));
        $this->broadcastEvent('afterBind',array($moveInfo['destination']));

        // If a resource was overwritten we should send a 204, otherwise a 201
        $this->httpResponse->setHeader('Content-Length','0');
        $this->httpResponse->sendStatus($moveInfo['destinationExists']?204:201);

    }

    /**
     * WebDAV HTTP COPY method
     *
     * This method copies one uri to a different uri, and works much like the MOVE request
     * A lot of the actual request processing is done in getCopyMoveInfo
     *
     * @param string $uri
     * @return bool
     */
    protected function httpCopy($uri) {

        $copyInfo = $this->getCopyAndMoveInfo();
        // If the destination is part of the source tree, we must fail
        if ($copyInfo['destination']==$uri)
            throw new Sabre_DAV_Exception_Forbidden('Source and destination uri are identical.');

        if ($copyInfo['destinationExists']) {
            if (!$this->broadcastEvent('beforeUnbind',array($copyInfo['destination']))) return false;
            $this->tree->delete($copyInfo['destination']);

        }
        if (!$this->broadcastEvent('beforeBind',array($copyInfo['destination']))) return false;
        $this->tree->copy($uri,$copyInfo['destination']);
        $this->broadcastEvent('afterBind',array($copyInfo['destination']));

        // If a resource was overwritten we should send a 204, otherwise a 201
        $this->httpResponse->setHeader('Content-Length','0');
        $this->httpResponse->sendStatus($copyInfo['destinationExists']?204:201);

    }



    /**
     * HTTP REPORT method implementation
     *
     * Although the REPORT method is not part of the standard WebDAV spec (it's from rfc3253)
     * It's used in a lot of extensions, so it made sense to implement it into the core.
     *
     * @param string $uri
     * @return void
     */
    protected function httpReport($uri) {

        $body = $this->httpRequest->getBody(true);
        $dom = Sabre_DAV_XMLUtil::loadDOMDocument($body);

        $reportName = Sabre_DAV_XMLUtil::toClarkNotation($dom->firstChild);

        if ($this->broadcastEvent('report',array($reportName,$dom, $uri))) {

            // If broadcastEvent returned true, it means the report was not supported
            throw new Sabre_DAV_Exception_ReportNotImplemented();

        }

    }

    // }}}
    // {{{ HTTP/WebDAV protocol helpers

    /**
     * Returns an array with all the supported HTTP methods for a specific uri.
     *
     * @param string $uri
     * @return array
     */
    public function getAllowedMethods($uri) {

        $methods = array(
            'OPTIONS',
            'GET',
            'HEAD',
            'DELETE',
            'PROPFIND',
            'PUT',
            'PROPPATCH',
            'COPY',
            'MOVE',
            'REPORT'
        );

        // The MKCOL is only allowed on an unmapped uri
        try {
            $this->tree->getNodeForPath($uri);
        } catch (Sabre_DAV_Exception_NotFound $e) {
            $methods[] = 'MKCOL';
        }

        // We're also checking if any of the plugins register any new methods
        foreach($this->plugins as $plugin) $methods = array_merge($methods, $plugin->getHTTPMethods($uri));
        array_unique($methods);

        return $methods;

    }

    /**
     * Gets the uri for the request, keeping the base uri into consideration
     *
     * @return string
     */
    public function getRequestUri() {

        return $this->calculateUri($this->httpRequest->getUri());

    }

    /**
     * Calculates the uri for a request, making sure that the base uri is stripped out
     *
     * @param string $uri
     * @throws Sabre_DAV_Exception_Forbidden A permission denied exception is thrown whenever there was an attempt to supply a uri outside of the base uri
     * @return string
     */
    public function calculateUri($uri) {

        if ($uri[0]!='/' && strpos($uri,'://')) {

            $uri = parse_url($uri,PHP_URL_PATH);

        }

        $uri = str_replace('//','/',$uri);

        if (strpos($uri,$this->getBaseUri())===0) {

            return trim(Sabre_DAV_URLUtil::decodePath(substr($uri,strlen($this->getBaseUri()))),'/');

        // A special case, if the baseUri was accessed without a trailing
        // slash, we'll accept it as well.
        } elseif ($uri.'/' === $this->getBaseUri()) {

            return '';

        } else {

            throw new Sabre_DAV_Exception_Forbidden('Requested uri (' . $uri . ') is out of base uri (' . $this->getBaseUri() . ')');

        }

    }

    /**
     * Returns the HTTP depth header
     *
     * This method returns the contents of the HTTP depth request header. If the depth header was 'infinity' it will return the Sabre_DAV_Server::DEPTH_INFINITY object
     * It is possible to supply a default depth value, which is used when the depth header has invalid content, or is completely non-existent
     *
     * @param mixed $default
     * @return int
     */
    public function getHTTPDepth($default = self::DEPTH_INFINITY) {

        // If its not set, we'll grab the default
        $depth = $this->httpRequest->getHeader('Depth');

        if (is_null($depth)) return $default;

        if ($depth == 'infinity') return self::DEPTH_INFINITY;


        // If its an unknown value. we'll grab the default
        if (!ctype_digit($depth)) return $default;

        return (int)$depth;

    }

    /**
     * Returns the HTTP range header
     *
     * This method returns null if there is no well-formed HTTP range request
     * header or array($start, $end).
     *
     * The first number is the offset of the first byte in the range.
     * The second number is the offset of the last byte in the range.
     *
     * If the second offset is null, it should be treated as the offset of the last byte of the entity
     * If the first offset is null, the second offset should be used to retrieve the last x bytes of the entity
     *
     * @return array|null
     */
    public function getHTTPRange() {

        $range = $this->httpRequest->getHeader('range');
        if (is_null($range)) return null;

        // Matching "Range: bytes=1234-5678: both numbers are optional

        if (!preg_match('/^bytes=([0-9]*)-([0-9]*)$/i',$range,$matches)) return null;

        if ($matches[1]==='' && $matches[2]==='') return null;

        return array(
            $matches[1]!==''?$matches[1]:null,
            $matches[2]!==''?$matches[2]:null,
        );

    }


    /**
     * Returns information about Copy and Move requests
     *
     * This function is created to help getting information about the source and the destination for the
     * WebDAV MOVE and COPY HTTP request. It also validates a lot of information and throws proper exceptions
     *
     * The returned value is an array with the following keys:
     *   * destination - Destination path
     *   * destinationExists - Whether or not the destination is an existing url (and should therefore be overwritten)
     *
     * @return array
     */
    public function getCopyAndMoveInfo() {

        // Collecting the relevant HTTP headers
        if (!$this->httpRequest->getHeader('Destination')) throw new Sabre_DAV_Exception_BadRequest('The destination header was not supplied');
        $destination = $this->calculateUri($this->httpRequest->getHeader('Destination'));
        $overwrite = $this->httpRequest->getHeader('Overwrite');
        if (!$overwrite) $overwrite = 'T';
        if (strtoupper($overwrite)=='T') $overwrite = true;
        elseif (strtoupper($overwrite)=='F') $overwrite = false;
        // We need to throw a bad request exception, if the header was invalid
        else throw new Sabre_DAV_Exception_BadRequest('The HTTP Overwrite header should be either T or F');

        list($destinationDir) = Sabre_DAV_URLUtil::splitPath($destination);

        try {
            $destinationParent = $this->tree->getNodeForPath($destinationDir);
            if (!($destinationParent instanceof Sabre_DAV_ICollection)) throw new Sabre_DAV_Exception_UnsupportedMediaType('The destination node is not a collection');
        } catch (Sabre_DAV_Exception_NotFound $e) {

            // If the destination parent node is not found, we throw a 409
            throw new Sabre_DAV_Exception_Conflict('The destination node is not found');
        }

        try {

            $destinationNode = $this->tree->getNodeForPath($destination);

            // If this succeeded, it means the destination already exists
            // we'll need to throw precondition failed in case overwrite is false
            if (!$overwrite) throw new Sabre_DAV_Exception_PreconditionFailed('The destination node already exists, and the overwrite header is set to false','Overwrite');

        } catch (Sabre_DAV_Exception_NotFound $e) {

            // Destination didn't exist, we're all good
            $destinationNode = false;



        }

        // These are the three relevant properties we need to return
        return array(
            'destination'       => $destination,
            'destinationExists' => $destinationNode==true,
            'destinationNode'   => $destinationNode,
        );

    }

    /**
     * Returns a list of properties for a path
     *
     * This is a simplified version getPropertiesForPath.
     * if you aren't interested in status codes, but you just
     * want to have a flat list of properties. Use this method.
     *
     * @param string $path
     * @param array $propertyNames
     */
    public function getProperties($path, $propertyNames) {

        $result = $this->getPropertiesForPath($path,$propertyNames,0);
        return $result[0][200];

    }

    /**
     * A kid-friendly way to fetch properties for a node's children.
     *
     * The returned array will be indexed by the path of the of child node.
     * Only properties that are actually found will be returned.
     *
     * The parent node will not be returned.
     *
     * @param string $path
     * @param array $propertyNames
     * @return array
     */
    public function getPropertiesForChildren($path, $propertyNames) {

        $result = array();
        foreach($this->getPropertiesForPath($path,$propertyNames,1) as $k=>$row) {

            // Skipping the parent path
            if ($k === 0) continue;

            $result[$row['href']] = $row[200];

        }
        return $result;

    }

    /**
     * Returns a list of HTTP headers for a particular resource
     *
     * The generated http headers are based on properties provided by the
     * resource. The method basically provides a simple mapping between
     * DAV property and HTTP header.
     *
     * The headers are intended to be used for HEAD and GET requests.
     *
     * @param string $path
     * @return array
     */
    public function getHTTPHeaders($path) {

        $propertyMap = array(
            '{DAV:}getcontenttype'   => 'Content-Type',
            '{DAV:}getcontentlength' => 'Content-Length',
            '{DAV:}getlastmodified'  => 'Last-Modified',
            '{DAV:}getetag'          => 'ETag',
        );

        $properties = $this->getProperties($path,array_keys($propertyMap));

        $headers = array();
        foreach($propertyMap as $property=>$header) {
            if (!isset($properties[$property])) continue;

            if (is_scalar($properties[$property])) {
                $headers[$header] = $properties[$property];

            // GetLastModified gets special cased
            } elseif ($properties[$property] instanceof Sabre_DAV_Property_GetLastModified) {
                $headers[$header] = Sabre_HTTP_Util::toHTTPDate($properties[$property]->getTime());
            }

        }

        return $headers;

    }

    /**
     * Returns a list of properties for a given path
     *
     * The path that should be supplied should have the baseUrl stripped out
     * The list of properties should be supplied in Clark notation. If the list is empty
     * 'allprops' is assumed.
     *
     * If a depth of 1 is requested child elements will also be returned.
     *
     * @param string $path
     * @param array $propertyNames
     * @param int $depth
     * @return array
     */
    public function getPropertiesForPath($path, $propertyNames = array(), $depth = 0) {

        if ($depth!=0) $depth = 1;

        $returnPropertyList = array();

        $parentNode = $this->tree->getNodeForPath($path);
        $nodes = array(
            $path => $parentNode
        );
        if ($depth==1 && $parentNode instanceof Sabre_DAV_ICollection) {
            foreach($this->tree->getChildren($path) as $childNode)
                $nodes[$path . '/' . $childNode->getName()] = $childNode;
        }

        // If the propertyNames array is empty, it means all properties are requested.
        // We shouldn't actually return everything we know though, and only return a
        // sensible list.
        $allProperties = count($propertyNames)==0;

        foreach($nodes as $myPath=>$node) {

            $currentPropertyNames = $propertyNames;

            $newProperties = array(
                '200' => array(),
                '404' => array(),
            );

            if ($allProperties) {
                // Default list of propertyNames, when all properties were requested.
                $currentPropertyNames = array(
                    '{DAV:}getlastmodified',
                    '{DAV:}getcontentlength',
                    '{DAV:}resourcetype',
                    '{DAV:}quota-used-bytes',
                    '{DAV:}quota-available-bytes',
                    '{DAV:}getetag',
                    '{DAV:}getcontenttype',
                );
            }

            // If the resourceType was not part of the list, we manually add it
            // and mark it for removal. We need to know the resourcetype in order
            // to make certain decisions about the entry.
            // WebDAV dictates we should add a / and the end of href's for collections
            $removeRT = false;
            if (!in_array('{DAV:}resourcetype',$currentPropertyNames)) {
                $currentPropertyNames[] = '{DAV:}resourcetype';
                $removeRT = true;
            }

            $result = $this->broadcastEvent('beforeGetProperties',array($myPath, $node, &$currentPropertyNames, &$newProperties));
            // If this method explicitly returned false, we must ignore this
            // node as it is inaccessible.
            if ($result===false) continue;

            if (count($currentPropertyNames) > 0) {

                if ($node instanceof Sabre_DAV_IProperties)
                    $newProperties['200'] = $newProperties[200] + $node->getProperties($currentPropertyNames);

            }


            foreach($currentPropertyNames as $prop) {

                if (isset($newProperties[200][$prop])) continue;

                switch($prop) {
                    case '{DAV:}getlastmodified'       : if ($node->getLastModified()) $newProperties[200][$prop] = new Sabre_DAV_Property_GetLastModified($node->getLastModified()); break;
                    case '{DAV:}getcontentlength'      :
                        if ($node instanceof Sabre_DAV_IFile) {
                            $size = $node->getSize();
                            if (!is_null($size)) {
                                $newProperties[200][$prop] = (int)$node->getSize();
                            }
                        }
                        break;
                    case '{DAV:}quota-used-bytes'      :
                        if ($node instanceof Sabre_DAV_IQuota) {
                            $quotaInfo = $node->getQuotaInfo();
                            $newProperties[200][$prop] = $quotaInfo[0];
                        }
                        break;
                    case '{DAV:}quota-available-bytes' :
                        if ($node instanceof Sabre_DAV_IQuota) {
                            $quotaInfo = $node->getQuotaInfo();
                            $newProperties[200][$prop] = $quotaInfo[1];
                        }
                        break;
                    case '{DAV:}getetag'               : if ($node instanceof Sabre_DAV_IFile && $etag = $node->getETag())  $newProperties[200][$prop] = $etag; break;
                    case '{DAV:}getcontenttype'        : if ($node instanceof Sabre_DAV_IFile && $ct = $node->getContentType())  $newProperties[200][$prop] = $ct; break;
                    case '{DAV:}supported-report-set'  :
                        $reports = array();
                        foreach($this->plugins as $plugin) {
                            $reports = array_merge($reports, $plugin->getSupportedReportSet($myPath));
                        }
                        $newProperties[200][$prop] = new Sabre_DAV_Property_SupportedReportSet($reports);
                        break;
                    case '{DAV:}resourcetype' :
                        $newProperties[200]['{DAV:}resourcetype'] = new Sabre_DAV_Property_ResourceType();
                        foreach($this->resourceTypeMapping as $className => $resourceType) {
                            if ($node instanceof $className) $newProperties[200]['{DAV:}resourcetype']->add($resourceType);
                        }
                        break;

                }

                // If we were unable to find the property, we will list it as 404.
                if (!$allProperties && !isset($newProperties[200][$prop])) $newProperties[404][$prop] = null;

            }

            $this->broadcastEvent('afterGetProperties',array(trim($myPath,'/'),&$newProperties));

            $newProperties['href'] = trim($myPath,'/');

            // Its is a WebDAV recommendation to add a trailing slash to collectionnames.
            // Apple's iCal also requires a trailing slash for principals (rfc 3744).
            // Therefore we add a trailing / for any non-file. This might need adjustments
            // if we find there are other edge cases.
            if ($myPath!='' && isset($newProperties[200]['{DAV:}resourcetype']) && count($newProperties[200]['{DAV:}resourcetype']->getValue())>0) $newProperties['href'] .='/';

            // If the resourcetype property was manually added to the requested property list,
            // we will remove it again.
            if ($removeRT) unset($newProperties[200]['{DAV:}resourcetype']);

            $returnPropertyList[] = $newProperties;

        }

        return $returnPropertyList;

    }

    /**
     * This method is invoked by sub-systems creating a new file.
     *
     * Currently this is done by HTTP PUT and HTTP LOCK (in the Locks_Plugin).
     * It was important to get this done through a centralized function,
     * allowing plugins to intercept this using the beforeCreateFile event.
     *
     * This method will return true if the file was actually created
     *
     * @param string   $uri
     * @param resource $data
     * @param string   $etag
     * @return bool
     */
    public function createFile($uri,$data, &$etag = null) {

        list($dir,$name) = Sabre_DAV_URLUtil::splitPath($uri);

        if (!$this->broadcastEvent('beforeBind',array($uri))) return false;

        $parent = $this->tree->getNodeForPath($dir);

        if (!$this->broadcastEvent('beforeCreateFile',array($uri, &$data, $parent))) return false;

        $etag = $parent->createFile($name,$data);
        $this->tree->markDirty($dir);

        $this->broadcastEvent('afterBind',array($uri));
        $this->broadcastEvent('afterCreateFile',array($uri, $parent));

        return true;
    }

    /**
     * This method is invoked by sub-systems creating a new directory.
     *
     * @param string $uri
     * @return void
     */
    public function createDirectory($uri) {

        $this->createCollection($uri,array('{DAV:}collection'),array());

    }

    /**
     * Use this method to create a new collection
     *
     * The {DAV:}resourcetype is specified using the resourceType array.
     * At the very least it must contain {DAV:}collection.
     *
     * The properties array can contain a list of additional properties.
     *
     * @param string $uri The new uri
     * @param array $resourceType The resourceType(s)
     * @param array $properties A list of properties
     * @return array|null
     */
    public function createCollection($uri, array $resourceType, array $properties) {

        list($parentUri,$newName) = Sabre_DAV_URLUtil::splitPath($uri);

        // Making sure {DAV:}collection was specified as resourceType
        if (!in_array('{DAV:}collection', $resourceType)) {
            throw new Sabre_DAV_Exception_InvalidResourceType('The resourceType for this collection must at least include {DAV:}collection');
        }


        // Making sure the parent exists
        try {

            $parent = $this->tree->getNodeForPath($parentUri);

        } catch (Sabre_DAV_Exception_NotFound $e) {

            throw new Sabre_DAV_Exception_Conflict('Parent node does not exist');

        }

        // Making sure the parent is a collection
        if (!$parent instanceof Sabre_DAV_ICollection) {
            throw new Sabre_DAV_Exception_Conflict('Parent node is not a collection');
        }



        // Making sure the child does not already exist
        try {
            $parent->getChild($newName);

            // If we got here.. it means there's already a node on that url, and we need to throw a 405
            throw new Sabre_DAV_Exception_MethodNotAllowed('The resource you tried to create already exists');

        } catch (Sabre_DAV_Exception_NotFound $e) {
            // This is correct
        }


        if (!$this->broadcastEvent('beforeBind',array($uri))) return;

        // There are 2 modes of operation. The standard collection
        // creates the directory, and then updates properties
        // the extended collection can create it directly.
        if ($parent instanceof Sabre_DAV_IExtendedCollection) {

            $parent->createExtendedCollection($newName, $resourceType, $properties);

        } else {

            // No special resourcetypes are supported
            if (count($resourceType)>1) {
                throw new Sabre_DAV_Exception_InvalidResourceType('The {DAV:}resourcetype you specified is not supported here.');
            }

            $parent->createDirectory($newName);
            $rollBack = false;
            $exception = null;
            $errorResult = null;

            if (count($properties)>0) {

                try {

                    $errorResult = $this->updateProperties($uri, $properties);
                    if (!isset($errorResult[200])) {
                        $rollBack = true;
                    }

                } catch (Sabre_DAV_Exception $e) {

                    $rollBack = true;
                    $exception = $e;

                }

            }

            if ($rollBack) {
                if (!$this->broadcastEvent('beforeUnbind',array($uri))) return;
                $this->tree->delete($uri);

                // Re-throwing exception
                if ($exception) throw $exception;

                return $errorResult;
            }

        }
        $this->tree->markDirty($parentUri);
        $this->broadcastEvent('afterBind',array($uri));

    }

    /**
     * This method updates a resource's properties
     *
     * The properties array must be a list of properties. Array-keys are
     * property names in clarknotation, array-values are it's values.
     * If a property must be deleted, the value should be null.
     *
     * Note that this request should either completely succeed, or
     * completely fail.
     *
     * The response is an array with statuscodes for keys, which in turn
     * contain arrays with propertynames. This response can be used
     * to generate a multistatus body.
     *
     * @param string $uri
     * @param array $properties
     * @return array
     */
    public function updateProperties($uri, array $properties) {

        // we'll start by grabbing the node, this will throw the appropriate
        // exceptions if it doesn't.
        $node = $this->tree->getNodeForPath($uri);

        $result = array(
            200 => array(),
            403 => array(),
            424 => array(),
        );
        $remainingProperties = $properties;
        $hasError = false;

        // Running through all properties to make sure none of them are protected
        if (!$hasError) foreach($properties as $propertyName => $value) {
            if(in_array($propertyName, $this->protectedProperties)) {
                $result[403][$propertyName] = null;
                unset($remainingProperties[$propertyName]);
                $hasError = true;
            }
        }

        if (!$hasError) {
            // Allowing plugins to take care of property updating
            $hasError = !$this->broadcastEvent('updateProperties',array(
                &$remainingProperties,
                &$result,
                $node
            ));
        }

        // If the node is not an instance of Sabre_DAV_IProperties, every
        // property is 403 Forbidden
        if (!$hasError && count($remainingProperties) && !($node instanceof Sabre_DAV_IProperties)) {
            $hasError = true;
            foreach($properties as $propertyName=> $value) {
                $result[403][$propertyName] = null;
            }
            $remainingProperties = array();
        }

        // Only if there were no errors we may attempt to update the resource
        if (!$hasError) {

            if (count($remainingProperties)>0) {

                $updateResult = $node->updateProperties($remainingProperties);

                if ($updateResult===true) {
                    // success
                    foreach($remainingProperties as $propertyName=>$value) {
                        $result[200][$propertyName] = null;
                    }

                } elseif ($updateResult===false) {
                    // The node failed to update the properties for an
                    // unknown reason
                    foreach($remainingProperties as $propertyName=>$value) {
                        $result[403][$propertyName] = null;
                    }

                } elseif (is_array($updateResult)) {

                    // The node has detailed update information
                    // We need to merge the results with the earlier results.
                    foreach($updateResult as $status => $props) {
                        if (is_array($props)) {
                            if (!isset($result[$status]))
                                $result[$status] = array();

                            $result[$status] = array_merge($result[$status], $updateResult[$status]);
                        }
                    }

                } else {
                    throw new Sabre_DAV_Exception('Invalid result from updateProperties');
                }
                $remainingProperties = array();
            }

        }

        foreach($remainingProperties as $propertyName=>$value) {
            // if there are remaining properties, it must mean
            // there's a dependency failure
            $result[424][$propertyName] = null;
        }

        // Removing empty array values
        foreach($result as $status=>$props) {

            if (count($props)===0) unset($result[$status]);

        }
        $result['href'] = $uri;
        return $result;

    }

    /**
     * This method checks the main HTTP preconditions.
     *
     * Currently these are:
     *   * If-Match
     *   * If-None-Match
     *   * If-Modified-Since
     *   * If-Unmodified-Since
     *
     * The method will return true if all preconditions are met
     * The method will return false, or throw an exception if preconditions
     * failed. If false is returned the operation should be aborted, and
     * the appropriate HTTP response headers are already set.
     *
     * Normally this method will throw 412 Precondition Failed for failures
     * related to If-None-Match, If-Match and If-Unmodified Since. It will
     * set the status to 304 Not Modified for If-Modified_since.
     *
     * If the $handleAsGET argument is set to true, it will also return 304
     * Not Modified for failure of the If-None-Match precondition. This is the
     * desired behaviour for HTTP GET and HTTP HEAD requests.
     *
     * @param bool $handleAsGET
     * @return bool
     */
    public function checkPreconditions($handleAsGET = false) {

        $uri = $this->getRequestUri();
        $node = null;
        $lastMod = null;
        $etag = null;

        if ($ifMatch = $this->httpRequest->getHeader('If-Match')) {

            // If-Match contains an entity tag. Only if the entity-tag
            // matches we are allowed to make the request succeed.
            // If the entity-tag is '*' we are only allowed to make the
            // request succeed if a resource exists at that url.
            try {
                $node = $this->tree->getNodeForPath($uri);
            } catch (Sabre_DAV_Exception_NotFound $e) {
                throw new Sabre_DAV_Exception_PreconditionFailed('An If-Match header was specified and the resource did not exist','If-Match');
            }

            // Only need to check entity tags if they are not *
            if ($ifMatch!=='*') {

                // There can be multiple etags
                $ifMatch = explode(',',$ifMatch);
                $haveMatch = false;
                foreach($ifMatch as $ifMatchItem) {

                    // Stripping any extra spaces
                    $ifMatchItem = trim($ifMatchItem,' ');

                    $etag = $node->getETag();
                    if ($etag===$ifMatchItem) {
                        $haveMatch = true;
                    }
                }
                if (!$haveMatch) {
                     throw new Sabre_DAV_Exception_PreconditionFailed('An If-Match header was specified, but none of the specified the ETags matched.','If-Match');
                }
            }
        }

        if ($ifNoneMatch = $this->httpRequest->getHeader('If-None-Match')) {

            // The If-None-Match header contains an etag.
            // Only if the ETag does not match the current ETag, the request will succeed
            // The header can also contain *, in which case the request
            // will only succeed if the entity does not exist at all.
            $nodeExists = true;
            if (!$node) {
                try {
                    $node = $this->tree->getNodeForPath($uri);
                } catch (Sabre_DAV_Exception_NotFound $e) {
                    $nodeExists = false;
                }
            }
            if ($nodeExists) {
                $haveMatch = false;
                if ($ifNoneMatch==='*') $haveMatch = true;
                else {

                    // There might be multiple etags
                    $ifNoneMatch = explode(',', $ifNoneMatch);
                    $etag = $node->getETag();

                    foreach($ifNoneMatch as $ifNoneMatchItem) {

                        // Stripping any extra spaces
                        $ifNoneMatchItem = trim($ifNoneMatchItem,' ');

                        if ($etag===$ifNoneMatchItem) $haveMatch = true;

                    }

                }

                if ($haveMatch) {
                    if ($handleAsGET) {
                        $this->httpResponse->sendStatus(304);
                        return false;
                    } else {
                        throw new Sabre_DAV_Exception_PreconditionFailed('An If-None-Match header was specified, but the ETag matched (or * was specified).','If-None-Match');
                    }
                }
            }

        }

        if (!$ifNoneMatch && ($ifModifiedSince = $this->httpRequest->getHeader('If-Modified-Since'))) {

            // The If-Modified-Since header contains a date. We
            // will only return the entity if it has been changed since
            // that date. If it hasn't been changed, we return a 304
            // header
            // Note that this header only has to be checked if there was no If-None-Match header
            // as per the HTTP spec.
            $date = Sabre_HTTP_Util::parseHTTPDate($ifModifiedSince);

            if ($date) {
                if (is_null($node)) {
                    $node = $this->tree->getNodeForPath($uri);
                }
                $lastMod = $node->getLastModified();
                if ($lastMod) {
                    $lastMod = new DateTime('@' . $lastMod);
                    if ($lastMod <= $date) {
                        $this->httpResponse->sendStatus(304);
                        $this->httpResponse->setHeader('Last-Modified', Sabre_HTTP_Util::toHTTPDate($lastMod));
                        return false;
                    }
                }
            }
        }

        if ($ifUnmodifiedSince = $this->httpRequest->getHeader('If-Unmodified-Since')) {

            // The If-Unmodified-Since will allow allow the request if the
            // entity has not changed since the specified date.
            $date = Sabre_HTTP_Util::parseHTTPDate($ifUnmodifiedSince);

            // We must only check the date if it's valid
            if ($date) {
                if (is_null($node)) {
                    $node = $this->tree->getNodeForPath($uri);
                }
                $lastMod = $node->getLastModified();
                if ($lastMod) {
                    $lastMod = new DateTime('@' . $lastMod);
                    if ($lastMod > $date) {
                        throw new Sabre_DAV_Exception_PreconditionFailed('An If-Unmodified-Since header was specified, but the entity has been changed since the specified date.','If-Unmodified-Since');
                    }
                }
            }

        }
        return true;

    }

    // }}}
    // {{{ XML Readers & Writers


    /**
     * Generates a WebDAV propfind response body based on a list of nodes
     *
     * @param array $fileProperties The list with nodes
     * @return string
     */
    public function generateMultiStatus(array $fileProperties) {

        $dom = new DOMDocument('1.0','utf-8');
        //$dom->formatOutput = true;
        $multiStatus = $dom->createElement('d:multistatus');
        $dom->appendChild($multiStatus);

        // Adding in default namespaces
        foreach($this->xmlNamespaces as $namespace=>$prefix) {

            $multiStatus->setAttribute('xmlns:' . $prefix,$namespace);

        }

        foreach($fileProperties as $entry) {

            $href = $entry['href'];
            unset($entry['href']);

            $response = new Sabre_DAV_Property_Response($href,$entry);
            $response->serialize($this,$multiStatus);

        }

        return $dom->saveXML();

    }

    /**
     * This method parses a PropPatch request
     *
     * PropPatch changes the properties for a resource. This method
     * returns a list of properties.
     *
     * The keys in the returned array contain the property name (e.g.: {DAV:}displayname,
     * and the value contains the property value. If a property is to be removed the value
     * will be null.
     *
     * @param string $body xml body
     * @return array list of properties in need of updating or deletion
     */
    public function parsePropPatchRequest($body) {

        //We'll need to change the DAV namespace declaration to something else in order to make it parsable
        $dom = Sabre_DAV_XMLUtil::loadDOMDocument($body);

        $newProperties = array();

        foreach($dom->firstChild->childNodes as $child) {

            if ($child->nodeType !== XML_ELEMENT_NODE) continue;

            $operation = Sabre_DAV_XMLUtil::toClarkNotation($child);

            if ($operation!=='{DAV:}set' && $operation!=='{DAV:}remove') continue;

            $innerProperties = Sabre_DAV_XMLUtil::parseProperties($child, $this->propertyMap);

            foreach($innerProperties as $propertyName=>$propertyValue) {

                if ($operation==='{DAV:}remove') {
                    $propertyValue = null;
                }

                $newProperties[$propertyName] = $propertyValue;

            }

        }

        return $newProperties;

    }

    /**
     * This method parses the PROPFIND request and returns its information
     *
     * This will either be a list of properties, or an empty array; in which case
     * an {DAV:}allprop was requested.
     *
     * @param string $body
     * @return array
     */
    public function parsePropFindRequest($body) {

        // If the propfind body was empty, it means IE is requesting 'all' properties
        if (!$body) return array();

        $dom = Sabre_DAV_XMLUtil::loadDOMDocument($body);
        $elem = $dom->getElementsByTagNameNS('urn:DAV','propfind')->item(0);
        return array_keys(Sabre_DAV_XMLUtil::parseProperties($elem));

    }

    // }}}

}

