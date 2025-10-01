<?php

declare(strict_types=1);

namespace Sabre\DAV;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sabre\Event\EmitterInterface;
use Sabre\Event\WildcardEmitterTrait;
use Sabre\HTTP;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\Uri;
use Sabre\Xml\Writer;

/**
 * Main DAV server class.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Server implements LoggerAwareInterface, EmitterInterface
{
    use LoggerAwareTrait;
    use WildcardEmitterTrait;

    /**
     * Infinity is used for some request supporting the HTTP Depth header and indicates that the operation should traverse the entire tree.
     */
    const DEPTH_INFINITY = -1;

    /**
     * XML namespace for all SabreDAV related elements.
     */
    const NS_SABREDAV = 'http://sabredav.org/ns';

    /**
     * The tree object.
     *
     * @var Tree
     */
    public $tree;

    /**
     * The base uri.
     *
     * @var string
     */
    protected $baseUri = null;

    /**
     * httpResponse.
     *
     * @var HTTP\Response
     */
    public $httpResponse;

    /**
     * httpRequest.
     *
     * @var HTTP\Request
     */
    public $httpRequest;

    /**
     * PHP HTTP Sapi.
     *
     * @var HTTP\Sapi
     */
    public $sapi;

    /**
     * The list of plugins.
     *
     * @var array
     */
    protected $plugins = [];

    /**
     * This property will be filled with a unique string that describes the
     * transaction. This is useful for performance measuring and logging
     * purposes.
     *
     * By default it will just fill it with a lowercased HTTP method name, but
     * plugins override this. For example, the WebDAV-Sync sync-collection
     * report will set this to 'report-sync-collection'.
     *
     * @var string
     */
    public $transactionType;

    /**
     * This is a list of properties that are always server-controlled, and
     * must not get modified with PROPPATCH.
     *
     * Plugins may add to this list.
     *
     * @var string[]
     */
    public $protectedProperties = [
        // RFC4918
        '{DAV:}getcontentlength',
        '{DAV:}getetag',
        '{DAV:}getlastmodified',
        '{DAV:}lockdiscovery',
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

        // RFC3253
        '{DAV:}supported-method-set',
        '{DAV:}supported-report-set',

        // RFC6578
        '{DAV:}sync-token',

        // calendarserver.org extensions
        '{http://calendarserver.org/ns/}ctag',

        // sabredav extensions
        '{http://sabredav.org/ns}sync-token',
    ];

    /**
     * This is a flag that allow or not showing file, line and code
     * of the exception in the returned XML.
     *
     * @var bool
     */
    public $debugExceptions = false;

    /**
     * This property allows you to automatically add the 'resourcetype' value
     * based on a node's classname or interface.
     *
     * The preset ensures that {DAV:}collection is automatically added for nodes
     * implementing Sabre\DAV\ICollection.
     *
     * @var array
     */
    public $resourceTypeMapping = [
        'Sabre\\DAV\\ICollection' => '{DAV:}collection',
    ];

    /**
     * This property allows the usage of Depth: infinity on PROPFIND requests.
     *
     * By default Depth: infinity is treated as Depth: 1. Allowing Depth:
     * infinity is potentially risky, as it allows a single client to do a full
     * index of the webdav server, which is an easy DoS attack vector.
     *
     * Only turn this on if you know what you're doing.
     *
     * @var bool
     */
    public $enablePropfindDepthInfinity = false;

    /**
     * Reference to the XML utility object.
     *
     * @var Xml\Service
     */
    public $xml;

    /**
     * If this setting is turned off, SabreDAV's version number will be hidden
     * from various places.
     *
     * Some people feel this is a good security measure.
     *
     * @var bool
     */
    public static $exposeVersion = true;

    /**
     * If this setting is turned on, any multi status response on any PROPFIND will be streamed to the output buffer.
     * This will be beneficial for large result sets which will no longer consume a large amount of memory as well as
     * send back data to the client earlier.
     *
     * @var bool
     */
    public static $streamMultiStatus = false;

    /**
     * Sets up the server.
     *
     * If a Sabre\DAV\Tree object is passed as an argument, it will
     * use it as the directory tree. If a Sabre\DAV\INode is passed, it
     * will create a Sabre\DAV\Tree and use the node as the root.
     *
     * If nothing is passed, a Sabre\DAV\SimpleCollection is created in
     * a Sabre\DAV\Tree.
     *
     * If an array is passed, we automatically create a root node, and use
     * the nodes in the array as top-level children.
     *
     * @param Tree|INode|array|null $treeOrNode The tree object
     *
     * @throws Exception
     */
    public function __construct($treeOrNode = null, ?HTTP\Sapi $sapi = null)
    {
        if ($treeOrNode instanceof Tree) {
            $this->tree = $treeOrNode;
        } elseif ($treeOrNode instanceof INode) {
            $this->tree = new Tree($treeOrNode);
        } elseif (is_array($treeOrNode)) {
            $root = new SimpleCollection('root', $treeOrNode);
            $this->tree = new Tree($root);
        } elseif (is_null($treeOrNode)) {
            $root = new SimpleCollection('root');
            $this->tree = new Tree($root);
        } else {
            throw new Exception('Invalid argument passed to constructor. Argument must either be an instance of Sabre\\DAV\\Tree, Sabre\\DAV\\INode, an array or null');
        }

        $this->xml = new Xml\Service();
        $this->sapi = $sapi ?? new HTTP\Sapi();
        $this->httpResponse = new HTTP\Response();
        $this->httpRequest = $this->sapi->getRequest();
        $this->addPlugin(new CorePlugin());
    }

    /**
     * Starts the DAV Server.
     */
    public function start()
    {
        try {
            // If nginx (pre-1.2) is used as a proxy server, and SabreDAV as an
            // origin, we must make sure we send back HTTP/1.0 if this was
            // requested.
            // This is mainly because nginx doesn't support Chunked Transfer
            // Encoding, and this forces the webserver SabreDAV is running on,
            // to buffer entire responses to calculate Content-Length.
            $this->httpResponse->setHTTPVersion($this->httpRequest->getHTTPVersion());

            // Setting the base url
            $this->httpRequest->setBaseUrl($this->getBaseUri());
            $this->invokeMethod($this->httpRequest, $this->httpResponse);
        } catch (\Throwable $e) {
            try {
                $this->emit('exception', [$e]);
            } catch (\Exception $ignore) {
            }
            $DOM = new \DOMDocument('1.0', 'utf-8');
            $DOM->formatOutput = true;

            $error = $DOM->createElementNS('DAV:', 'd:error');
            $error->setAttribute('xmlns:s', self::NS_SABREDAV);
            $DOM->appendChild($error);

            $h = function ($v) {
                return htmlspecialchars((string) $v, ENT_NOQUOTES, 'UTF-8');
            };

            if (self::$exposeVersion) {
                $error->appendChild($DOM->createElement('s:sabredav-version', $h(Version::VERSION)));
            }

            $error->appendChild($DOM->createElement('s:exception', $h(get_class($e))));
            $error->appendChild($DOM->createElement('s:message', $h($e->getMessage())));
            if ($this->debugExceptions) {
                $error->appendChild($DOM->createElement('s:file', $h($e->getFile())));
                $error->appendChild($DOM->createElement('s:line', $h($e->getLine())));
                $error->appendChild($DOM->createElement('s:code', $h($e->getCode())));
                $error->appendChild($DOM->createElement('s:stacktrace', $h($e->getTraceAsString())));
            }

            if ($this->debugExceptions) {
                $previous = $e;
                while ($previous = $previous->getPrevious()) {
                    $xPrevious = $DOM->createElement('s:previous-exception');
                    $xPrevious->appendChild($DOM->createElement('s:exception', $h(get_class($previous))));
                    $xPrevious->appendChild($DOM->createElement('s:message', $h($previous->getMessage())));
                    $xPrevious->appendChild($DOM->createElement('s:file', $h($previous->getFile())));
                    $xPrevious->appendChild($DOM->createElement('s:line', $h($previous->getLine())));
                    $xPrevious->appendChild($DOM->createElement('s:code', $h($previous->getCode())));
                    $xPrevious->appendChild($DOM->createElement('s:stacktrace', $h($previous->getTraceAsString())));
                    $error->appendChild($xPrevious);
                }
            }

            if ($e instanceof Exception) {
                $httpCode = $e->getHTTPCode();
                $e->serialize($this, $error);
                $headers = $e->getHTTPHeaders($this);
            } else {
                $httpCode = 500;
                $headers = [];
            }
            $headers['Content-Type'] = 'application/xml; charset=utf-8';

            $this->httpResponse->setStatus($httpCode);
            $this->httpResponse->setHeaders($headers);
            $this->httpResponse->setBody($DOM->saveXML());
            $this->sapi->sendResponse($this->httpResponse);
        }
    }

    /**
     * Alias of start().
     *
     * @deprecated
     */
    public function exec()
    {
        $this->start();
    }

    /**
     * Sets the base server uri.
     *
     * @param string $uri
     */
    public function setBaseUri($uri)
    {
        // If the baseUri does not end with a slash, we must add it
        if ('/' !== $uri[strlen($uri) - 1]) {
            $uri .= '/';
        }

        $this->baseUri = $uri;
    }

    /**
     * Returns the base responding uri.
     *
     * @return string
     */
    public function getBaseUri()
    {
        if (is_null($this->baseUri)) {
            $this->baseUri = $this->guessBaseUri();
        }

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
    public function guessBaseUri()
    {
        $pathInfo = $this->httpRequest->getRawServerValue('PATH_INFO');
        $uri = $this->httpRequest->getRawServerValue('REQUEST_URI');

        // If PATH_INFO is found, we can assume it's accurate.
        if (!empty($pathInfo)) {
            // We need to make sure we ignore the QUERY_STRING part
            if ($pos = strpos($uri, '?')) {
                $uri = substr($uri, 0, $pos);
            }

            // PATH_INFO is only set for urls, such as: /example.php/path
            // in that case PATH_INFO contains '/path'.
            // Note that REQUEST_URI is percent encoded, while PATH_INFO is
            // not, Therefore they are only comparable if we first decode
            // REQUEST_INFO as well.
            $decodedUri = HTTP\decodePath($uri);

            // A simple sanity check:
            if (substr($decodedUri, strlen($decodedUri) - strlen($pathInfo)) === $pathInfo) {
                $baseUri = substr($decodedUri, 0, strlen($decodedUri) - strlen($pathInfo));

                return rtrim($baseUri, '/').'/';
            }

            throw new Exception('The REQUEST_URI ('.$uri.') did not end with the contents of PATH_INFO ('.$pathInfo.'). This server might be misconfigured.');
        }

        // The last fallback is that we're just going to assume the server root.
        return '/';
    }

    /**
     * Adds a plugin to the server.
     *
     * For more information, console the documentation of Sabre\DAV\ServerPlugin
     */
    public function addPlugin(ServerPlugin $plugin)
    {
        $this->plugins[$plugin->getPluginName()] = $plugin;
        $plugin->initialize($this);
    }

    /**
     * Returns an initialized plugin by it's name.
     *
     * This function returns null if the plugin was not found.
     *
     * @param string $name
     *
     * @return ServerPlugin
     */
    public function getPlugin($name)
    {
        if (isset($this->plugins[$name])) {
            return $this->plugins[$name];
        }

        return null;
    }

    /**
     * Returns all plugins.
     *
     * @return array
     */
    public function getPlugins()
    {
        return $this->plugins;
    }

    /**
     * Returns the PSR-3 logger object.
     *
     * @return LoggerInterface
     */
    public function getLogger()
    {
        if (!$this->logger) {
            $this->logger = new NullLogger();
        }

        return $this->logger;
    }

    /**
     * Handles a http request, and execute a method based on its name.
     *
     * @param bool $sendResponse whether to send the HTTP response to the DAV client
     */
    public function invokeMethod(RequestInterface $request, ResponseInterface $response, $sendResponse = true)
    {
        $method = $request->getMethod();

        if (!$this->emit('beforeMethod:'.$method, [$request, $response])) {
            return;
        }

        if (self::$exposeVersion) {
            $response->setHeader('X-Sabre-Version', Version::VERSION);
        }

        $this->transactionType = strtolower($method);

        if (!$this->checkPreconditions($request, $response)) {
            $this->sapi->sendResponse($response);

            return;
        }

        if ($this->emit('method:'.$method, [$request, $response])) {
            $exMessage = 'There was no plugin in the system that was willing to handle this '.$method.' method.';
            if ('GET' === $method) {
                $exMessage .= ' Enable the Browser plugin to get a better result here.';
            }

            // Unsupported method
            throw new Exception\NotImplemented($exMessage);
        }

        if (!$this->emit('afterMethod:'.$method, [$request, $response])) {
            return;
        }

        if (null === $response->getStatus()) {
            throw new Exception('No subsystem set a valid HTTP status code. Something must have interrupted the request without providing further detail.');
        }
        if ($sendResponse) {
            $this->sapi->sendResponse($response);
            $this->emit('afterResponse', [$request, $response]);
        }
    }

    // {{{ HTTP/WebDAV protocol helpers

    /**
     * Returns an array with all the supported HTTP methods for a specific uri.
     *
     * @param string $path
     *
     * @return array
     */
    public function getAllowedMethods($path)
    {
        $methods = [
            'OPTIONS',
            'GET',
            'HEAD',
            'DELETE',
            'PROPFIND',
            'PUT',
            'PROPPATCH',
            'COPY',
            'MOVE',
            'REPORT',
        ];

        // The MKCOL is only allowed on an unmapped uri
        try {
            $this->tree->getNodeForPath($path);
        } catch (Exception\NotFound $e) {
            $methods[] = 'MKCOL';
        }

        // We're also checking if any of the plugins register any new methods
        foreach ($this->plugins as $plugin) {
            $methods = array_merge($methods, $plugin->getHTTPMethods($path));
        }
        array_unique($methods);

        return $methods;
    }

    /**
     * Gets the uri for the request, keeping the base uri into consideration.
     *
     * @return string
     */
    public function getRequestUri()
    {
        return $this->calculateUri($this->httpRequest->getUrl());
    }

    /**
     * Turns a URI such as the REQUEST_URI into a local path.
     *
     * This method:
     *   * strips off the base path
     *   * normalizes the path
     *   * uri-decodes the path
     *
     * @param string $uri
     *
     * @throws Exception\Forbidden A permission denied exception is thrown whenever there was an attempt to supply a uri outside of the base uri
     *
     * @return string
     */
    public function calculateUri($uri)
    {
        if ('' != $uri && '/' != $uri[0] && strpos($uri, '://')) {
            $uri = parse_url($uri, PHP_URL_PATH);
        }

        $uri = Uri\normalize(preg_replace('|/+|', '/', $uri));
        $baseUri = Uri\normalize($this->getBaseUri());

        if (0 === strpos($uri, $baseUri)) {
            return trim(HTTP\decodePath(substr($uri, strlen($baseUri))), '/');

        // A special case, if the baseUri was accessed without a trailing
        // slash, we'll accept it as well.
        } elseif ($uri.'/' === $baseUri) {
            return '';
        } else {
            throw new Exception\Forbidden('Requested uri ('.$uri.') is out of base uri ('.$this->getBaseUri().')');
        }
    }

    /**
     * Returns the HTTP depth header.
     *
     * This method returns the contents of the HTTP depth request header. If the depth header was 'infinity' it will return the Sabre\DAV\Server::DEPTH_INFINITY object
     * It is possible to supply a default depth value, which is used when the depth header has invalid content, or is completely non-existent
     *
     * @param mixed $default
     *
     * @return int
     */
    public function getHTTPDepth($default = self::DEPTH_INFINITY)
    {
        // If its not set, we'll grab the default
        $depth = $this->httpRequest->getHeader('Depth');

        if (is_null($depth)) {
            return $default;
        }

        if ('infinity' == $depth) {
            return self::DEPTH_INFINITY;
        }

        // If its an unknown value. we'll grab the default
        if (!ctype_digit($depth)) {
            return $default;
        }

        return (int) $depth;
    }

    /**
     * Returns the HTTP range header.
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
     * @return int[]|null
     */
    public function getHTTPRange()
    {
        $range = $this->httpRequest->getHeader('range');
        if (is_null($range)) {
            return null;
        }

        // Matching "Range: bytes=1234-5678: both numbers are optional

        if (!preg_match('/^bytes=([0-9]*)-([0-9]*)$/i', $range, $matches)) {
            return null;
        }

        if ('' === $matches[1] && '' === $matches[2]) {
            return null;
        }

        return [
            '' !== $matches[1] ? (int) $matches[1] : null,
            '' !== $matches[2] ? (int) $matches[2] : null,
        ];
    }

    /**
     * Returns the HTTP Prefer header information.
     *
     * The prefer header is defined in:
     * http://tools.ietf.org/html/draft-snell-http-prefer-14
     *
     * This method will return an array with options.
     *
     * Currently, the following options may be returned:
     *  [
     *      'return-asynch'         => true,
     *      'return-minimal'        => true,
     *      'return-representation' => true,
     *      'wait'                  => 30,
     *      'strict'                => true,
     *      'lenient'               => true,
     *  ]
     *
     * This method also supports the Brief header, and will also return
     * 'return-minimal' if the brief header was set to 't'.
     *
     * For the boolean options, false will be returned if the headers are not
     * specified. For the integer options it will be 'null'.
     *
     * @return array
     */
    public function getHTTPPrefer()
    {
        $result = [
            // can be true or false
            'respond-async' => false,
            // Could be set to 'representation' or 'minimal'.
            'return' => null,
            // Used as a timeout, is usually a number.
            'wait' => null,
            // can be 'strict' or 'lenient'.
            'handling' => false,
        ];

        if ($prefer = $this->httpRequest->getHeader('Prefer')) {
            $result = array_merge(
                $result,
                HTTP\parsePrefer($prefer)
            );
        } elseif ('t' == $this->httpRequest->getHeader('Brief')) {
            $result['return'] = 'minimal';
        }

        return $result;
    }

    /**
     * Returns information about Copy and Move requests.
     *
     * This function is created to help getting information about the source and the destination for the
     * WebDAV MOVE and COPY HTTP request. It also validates a lot of information and throws proper exceptions
     *
     * The returned value is an array with the following keys:
     *   * destination - Destination path
     *   * destinationExists - Whether or not the destination is an existing url (and should therefore be overwritten)
     *
     * @throws Exception\BadRequest           upon missing or broken request headers
     * @throws Exception\UnsupportedMediaType when trying to copy into a
     *                                        non-collection
     * @throws Exception\PreconditionFailed   if overwrite is set to false, but
     *                                        the destination exists
     * @throws Exception\Forbidden            when source and destination paths are
     *                                        identical
     * @throws Exception\Conflict             when trying to copy a node into its own
     *                                        subtree
     *
     * @return array
     */
    public function getCopyAndMoveInfo(RequestInterface $request)
    {
        // Collecting the relevant HTTP headers
        if (!$request->getHeader('Destination')) {
            throw new Exception\BadRequest('The destination header was not supplied');
        }
        $destination = $this->calculateUri($request->getHeader('Destination'));
        $overwrite = $request->getHeader('Overwrite');
        if (!$overwrite) {
            $overwrite = 'T';
        }
        if ('T' == strtoupper($overwrite)) {
            $overwrite = true;
        } elseif ('F' == strtoupper($overwrite)) {
            $overwrite = false;
        }
        // We need to throw a bad request exception, if the header was invalid
        else {
            throw new Exception\BadRequest('The HTTP Overwrite header should be either T or F');
        }
        list($destinationDir) = Uri\split($destination);

        try {
            $destinationParent = $this->tree->getNodeForPath($destinationDir);
            if (!($destinationParent instanceof ICollection)) {
                throw new Exception\UnsupportedMediaType('The destination node is not a collection');
            }
        } catch (Exception\NotFound $e) {
            // If the destination parent node is not found, we throw a 409
            throw new Exception\Conflict('The destination node is not found');
        }

        try {
            $destinationNode = $this->tree->getNodeForPath($destination);

            // If this succeeded, it means the destination already exists
            // we'll need to throw precondition failed in case overwrite is false
            if (!$overwrite) {
                throw new Exception\PreconditionFailed('The destination node already exists, and the overwrite header is set to false', 'Overwrite');
            }
        } catch (Exception\NotFound $e) {
            // Destination didn't exist, we're all good
            $destinationNode = false;
        }

        $requestPath = $request->getPath();
        if ($destination === $requestPath) {
            throw new Exception\Forbidden('Source and destination uri are identical.');
        }
        if (substr($destination, 0, strlen($requestPath) + 1) === $requestPath.'/') {
            throw new Exception\Conflict('The destination may not be part of the same subtree as the source path.');
        }

        // These are the three relevant properties we need to return
        return [
            'destination' => $destination,
            'destinationExists' => (bool) $destinationNode,
            'destinationNode' => $destinationNode,
        ];
    }

    /**
     * Returns a list of properties for a path.
     *
     * This is a simplified version getPropertiesForPath. If you aren't
     * interested in status codes, but you just want to have a flat list of
     * properties, use this method.
     *
     * Please note though that any problems related to retrieving properties,
     * such as permission issues will just result in an empty array being
     * returned.
     *
     * @param string $path
     * @param array  $propertyNames
     *
     * @return array
     */
    public function getProperties($path, $propertyNames)
    {
        $result = $this->getPropertiesForPath($path, $propertyNames, 0);
        if (isset($result[0][200])) {
            return $result[0][200];
        } else {
            return [];
        }
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
     * @param array  $propertyNames
     *
     * @return array
     */
    public function getPropertiesForChildren($path, $propertyNames)
    {
        $result = [];
        foreach ($this->getPropertiesForPath($path, $propertyNames, 1) as $k => $row) {
            // Skipping the parent path
            if (0 === $k) {
                continue;
            }

            $result[$row['href']] = $row[200];
        }

        return $result;
    }

    /**
     * Returns a list of HTTP headers for a particular resource.
     *
     * The generated http headers are based on properties provided by the
     * resource. The method basically provides a simple mapping between
     * DAV property and HTTP header.
     *
     * The headers are intended to be used for HEAD and GET requests.
     *
     * @param string $path
     *
     * @return array
     */
    public function getHTTPHeaders($path)
    {
        $propertyMap = [
            '{DAV:}getcontenttype' => 'Content-Type',
            '{DAV:}getcontentlength' => 'Content-Length',
            '{DAV:}getlastmodified' => 'Last-Modified',
            '{DAV:}getetag' => 'ETag',
        ];

        $properties = $this->getProperties($path, array_keys($propertyMap));

        $headers = [];
        foreach ($propertyMap as $property => $header) {
            if (!isset($properties[$property])) {
                continue;
            }

            if (is_scalar($properties[$property])) {
                $headers[$header] = $properties[$property];

            // GetLastModified gets special cased
            } elseif ($properties[$property] instanceof Xml\Property\GetLastModified) {
                $headers[$header] = HTTP\toDate($properties[$property]->getTime());
            }
        }

        return $headers;
    }

    /**
     * Small helper to support PROPFIND with DEPTH_INFINITY.
     *
     * @param array $yieldFirst
     *
     * @return \Traversable
     */
    private function generatePathNodes(PropFind $propFind, ?array $yieldFirst = null)
    {
        if (null !== $yieldFirst) {
            yield $yieldFirst;
        }
        $newDepth = $propFind->getDepth();
        $path = $propFind->getPath();

        if (self::DEPTH_INFINITY !== $newDepth) {
            --$newDepth;
        }

        $propertyNames = $propFind->getRequestedProperties();
        $propFindType = !$propFind->isAllProps() ? PropFind::NORMAL : PropFind::ALLPROPS;

        foreach ($this->tree->getChildren($path) as $childNode) {
            if ('' !== $path) {
                $subPath = $path.'/'.$childNode->getName();
            } else {
                $subPath = $childNode->getName();
            }
            $subPropFind = new PropFind($subPath, $propertyNames, $newDepth, $propFindType);

            yield [
                $subPropFind,
                $childNode,
            ];

            if ((self::DEPTH_INFINITY === $newDepth || $newDepth >= 1) && $childNode instanceof ICollection) {
                foreach ($this->generatePathNodes($subPropFind) as $subItem) {
                    yield $subItem;
                }
            }
        }
    }

    /**
     * Returns a list of properties for a given path.
     *
     * The path that should be supplied should have the baseUrl stripped out
     * The list of properties should be supplied in Clark notation. If the list is empty
     * 'allprops' is assumed.
     *
     * If a depth of 1 is requested child elements will also be returned.
     *
     * @param string $path
     * @param array  $propertyNames
     * @param int    $depth
     *
     * @return array
     *
     * @deprecated Use getPropertiesIteratorForPath() instead (as it's more memory efficient)
     * @see getPropertiesIteratorForPath()
     */
    public function getPropertiesForPath($path, $propertyNames = [], $depth = 0)
    {
        return iterator_to_array($this->getPropertiesIteratorForPath($path, $propertyNames, $depth));
    }

    /**
     * Returns a list of properties for a given path.
     *
     * The path that should be supplied should have the baseUrl stripped out
     * The list of properties should be supplied in Clark notation. If the list is empty
     * 'allprops' is assumed.
     *
     * If a depth of 1 is requested child elements will also be returned.
     *
     * @param string $path
     * @param array  $propertyNames
     * @param int    $depth
     *
     * @return \Iterator
     */
    public function getPropertiesIteratorForPath($path, $propertyNames = [], $depth = 0)
    {
        // The only two options for the depth of a propfind is 0 or 1 - as long as depth infinity is not enabled
        if (!$this->enablePropfindDepthInfinity && 0 != $depth) {
            $depth = 1;
        }

        $path = trim($path, '/');

        $propFindType = $propertyNames ? PropFind::NORMAL : PropFind::ALLPROPS;
        $propFind = new PropFind($path, (array) $propertyNames, $depth, $propFindType);

        $parentNode = $this->tree->getNodeForPath($path);

        $propFindRequests = [[
            $propFind,
            $parentNode,
        ]];

        if (($depth > 0 || self::DEPTH_INFINITY === $depth) && $parentNode instanceof ICollection) {
            $propFindRequests = $this->generatePathNodes(clone $propFind, current($propFindRequests));
        }

        foreach ($propFindRequests as $propFindRequest) {
            list($propFind, $node) = $propFindRequest;
            $r = $this->getPropertiesByNode($propFind, $node);
            if ($r) {
                $result = $propFind->getResultForMultiStatus();
                $result['href'] = $propFind->getPath();

                // WebDAV recommends adding a slash to the path, if the path is
                // a collection.
                // Furthermore, iCal also demands this to be the case for
                // principals. This is non-standard, but we support it.
                $resourceType = $this->getResourceTypeForNode($node);
                if (in_array('{DAV:}collection', $resourceType) || in_array('{DAV:}principal', $resourceType)) {
                    $result['href'] .= '/';
                }
                yield $result;
            }
        }
    }

    /**
     * Returns a list of properties for a list of paths.
     *
     * The path that should be supplied should have the baseUrl stripped out
     * The list of properties should be supplied in Clark notation. If the list is empty
     * 'allprops' is assumed.
     *
     * The result is returned as an array, with paths for it's keys.
     * The result may be returned out of order.
     *
     * @return array
     */
    public function getPropertiesForMultiplePaths(array $paths, array $propertyNames = [])
    {
        $result = [
        ];

        $nodes = $this->tree->getMultipleNodes($paths);

        foreach ($nodes as $path => $node) {
            $propFind = new PropFind($path, $propertyNames);
            $r = $this->getPropertiesByNode($propFind, $node);
            if ($r) {
                $result[$path] = $propFind->getResultForMultiStatus();
                $result[$path]['href'] = $path;

                $resourceType = $this->getResourceTypeForNode($node);
                if (in_array('{DAV:}collection', $resourceType) || in_array('{DAV:}principal', $resourceType)) {
                    $result[$path]['href'] .= '/';
                }
            }
        }

        return $result;
    }

    /**
     * Determines all properties for a node.
     *
     * This method tries to grab all properties for a node. This method is used
     * internally getPropertiesForPath and a few others.
     *
     * It could be useful to call this, if you already have an instance of your
     * target node and simply want to run through the system to get a correct
     * list of properties.
     *
     * @return bool
     */
    public function getPropertiesByNode(PropFind $propFind, INode $node)
    {
        return $this->emit('propFind', [$propFind, $node]);
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
     *
     * @return bool
     */
    public function createFile($uri, $data, &$etag = null)
    {
        list($dir, $name) = Uri\split($uri);

        if (!$this->emit('beforeBind', [$uri])) {
            return false;
        }

        try {
            $parent = $this->tree->getNodeForPath($dir);
        } catch (Exception\NotFound $e) {
            throw new Exception\Conflict('Files cannot be created in non-existent collections');
        }

        if (!$parent instanceof ICollection) {
            throw new Exception\Conflict('Files can only be created as children of collections');
        }

        // It is possible for an event handler to modify the content of the
        // body, before it gets written. If this is the case, $modified
        // should be set to true.
        //
        // If $modified is true, we must not send back an ETag.
        $modified = false;
        if (!$this->emit('beforeCreateFile', [$uri, &$data, $parent, &$modified])) {
            return false;
        }

        $etag = $parent->createFile($name, $data);

        if ($modified) {
            $etag = null;
        }

        $this->tree->markDirty($dir.'/'.$name);

        $this->emit('afterBind', [$uri]);
        $this->emit('afterCreateFile', [$uri, $parent]);

        return true;
    }

    /**
     * This method is invoked by sub-systems updating a file.
     *
     * This method will return true if the file was actually updated
     *
     * @param string   $uri
     * @param resource $data
     * @param string   $etag
     *
     * @return bool
     */
    public function updateFile($uri, $data, &$etag = null)
    {
        $node = $this->tree->getNodeForPath($uri);

        // It is possible for an event handler to modify the content of the
        // body, before it gets written. If this is the case, $modified
        // should be set to true.
        //
        // If $modified is true, we must not send back an ETag.
        $modified = false;
        if (!$this->emit('beforeWriteContent', [$uri, $node, &$data, &$modified])) {
            return false;
        }

        $etag = $node->put($data);
        if ($modified) {
            $etag = null;
        }
        $this->emit('afterWriteContent', [$uri, $node]);

        return true;
    }

    /**
     * This method is invoked by sub-systems creating a new directory.
     *
     * @param string $uri
     */
    public function createDirectory($uri)
    {
        $this->createCollection($uri, new MkCol(['{DAV:}collection'], []));
    }

    /**
     * Use this method to create a new collection.
     *
     * @param string $uri The new uri
     *
     * @return array|null
     */
    public function createCollection($uri, MkCol $mkCol)
    {
        list($parentUri, $newName) = Uri\split($uri);

        // Making sure the parent exists
        try {
            $parent = $this->tree->getNodeForPath($parentUri);
        } catch (Exception\NotFound $e) {
            throw new Exception\Conflict('Parent node does not exist');
        }

        // Making sure the parent is a collection
        if (!$parent instanceof ICollection) {
            throw new Exception\Conflict('Parent node is not a collection');
        }

        // Making sure the child does not already exist
        try {
            $parent->getChild($newName);

            // If we got here.. it means there's already a node on that url, and we need to throw a 405
            throw new Exception\MethodNotAllowed('The resource you tried to create already exists');
        } catch (Exception\NotFound $e) {
            // NotFound is the expected behavior.
        }

        if (!$this->emit('beforeBind', [$uri])) {
            return;
        }

        if ($parent instanceof IExtendedCollection) {
            /*
             * If the parent is an instance of IExtendedCollection, it means that
             * we can pass the MkCol object directly as it may be able to store
             * properties immediately.
             */
            $parent->createExtendedCollection($newName, $mkCol);
        } else {
            /*
             * If the parent is a standard ICollection, it means only
             * 'standard' collections can be created, so we should fail any
             * MKCOL operation that carries extra resourcetypes.
             */
            if (count($mkCol->getResourceType()) > 1) {
                throw new Exception\InvalidResourceType('The {DAV:}resourcetype you specified is not supported here.');
            }

            $parent->createDirectory($newName);
        }

        // If there are any properties that have not been handled/stored,
        // we ask the 'propPatch' event to handle them. This will allow for
        // example the propertyStorage system to store properties upon MKCOL.
        if ($mkCol->getRemainingMutations()) {
            $this->emit('propPatch', [$uri, $mkCol]);
        }
        $success = $mkCol->commit();

        if (!$success) {
            $result = $mkCol->getResult();

            $formattedResult = [
                'href' => $uri,
            ];

            foreach ($result as $propertyName => $status) {
                if (!isset($formattedResult[$status])) {
                    $formattedResult[$status] = [];
                }
                $formattedResult[$status][$propertyName] = null;
            }

            return $formattedResult;
        }

        $this->tree->markDirty($parentUri);
        $this->emit('afterBind', [$uri]);
        $this->emit('afterCreateCollection', [$uri]);
    }

    /**
     * This method updates a resource's properties.
     *
     * The properties array must be a list of properties. Array-keys are
     * property names in clarknotation, array-values are it's values.
     * If a property must be deleted, the value should be null.
     *
     * Note that this request should either completely succeed, or
     * completely fail.
     *
     * The response is an array with properties for keys, and http status codes
     * as their values.
     *
     * @param string $path
     *
     * @return array
     */
    public function updateProperties($path, array $properties)
    {
        $propPatch = new PropPatch($properties);
        $this->emit('propPatch', [$path, $propPatch]);
        $propPatch->commit();

        return $propPatch->getResult();
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
     * @return bool
     */
    public function checkPreconditions(RequestInterface $request, ResponseInterface $response)
    {
        $path = $request->getPath();
        $node = null;
        $lastMod = null;
        $etag = null;

        if ($ifMatch = $request->getHeader('If-Match')) {
            // If-Match contains an entity tag. Only if the entity-tag
            // matches we are allowed to make the request succeed.
            // If the entity-tag is '*' we are only allowed to make the
            // request succeed if a resource exists at that url.
            try {
                $node = $this->tree->getNodeForPath($path);
            } catch (Exception\NotFound $e) {
                throw new Exception\PreconditionFailed('An If-Match header was specified and the resource did not exist', 'If-Match');
            }

            // Only need to check entity tags if they are not *
            if ('*' !== $ifMatch) {
                // There can be multiple ETags
                $ifMatch = explode(',', $ifMatch);
                $haveMatch = false;
                foreach ($ifMatch as $ifMatchItem) {
                    // Stripping any extra spaces
                    $ifMatchItem = trim($ifMatchItem, ' ');

                    $etag = $node instanceof IFile ? $node->getETag() : null;
                    if ($etag === $ifMatchItem) {
                        $haveMatch = true;
                    } else {
                        // Evolution has a bug where it sometimes prepends the "
                        // with a \. This is our workaround.
                        if (str_replace('\\"', '"', $ifMatchItem) === $etag) {
                            $haveMatch = true;
                        }
                    }
                }
                if (!$haveMatch) {
                    if ($etag) {
                        $response->setHeader('ETag', $etag);
                    }
                    throw new Exception\PreconditionFailed('An If-Match header was specified, but none of the specified ETags matched.', 'If-Match');
                }
            }
        }

        if ($ifNoneMatch = $request->getHeader('If-None-Match')) {
            // The If-None-Match header contains an ETag.
            // Only if the ETag does not match the current ETag, the request will succeed
            // The header can also contain *, in which case the request
            // will only succeed if the entity does not exist at all.
            $nodeExists = true;
            if (!$node) {
                try {
                    $node = $this->tree->getNodeForPath($path);
                } catch (Exception\NotFound $e) {
                    $nodeExists = false;
                }
            }
            if ($nodeExists) {
                $haveMatch = false;
                if ('*' === $ifNoneMatch) {
                    $haveMatch = true;
                } else {
                    // There might be multiple ETags
                    $ifNoneMatch = explode(',', $ifNoneMatch);
                    $etag = $node instanceof IFile ? $node->getETag() : null;

                    foreach ($ifNoneMatch as $ifNoneMatchItem) {
                        // Stripping any extra spaces
                        $ifNoneMatchItem = trim($ifNoneMatchItem, ' ');

                        if ($etag === $ifNoneMatchItem) {
                            $haveMatch = true;
                        }
                    }
                }

                if ($haveMatch) {
                    if ($etag) {
                        $response->setHeader('ETag', $etag);
                    }
                    if ('GET' === $request->getMethod()) {
                        $response->setStatus(304);

                        return false;
                    } else {
                        throw new Exception\PreconditionFailed('An If-None-Match header was specified, but the ETag matched (or * was specified).', 'If-None-Match');
                    }
                }
            }
        }

        if (!$ifNoneMatch && ($ifModifiedSince = $request->getHeader('If-Modified-Since'))) {
            // The If-Modified-Since header contains a date. We
            // will only return the entity if it has been changed since
            // that date. If it hasn't been changed, we return a 304
            // header
            // Note that this header only has to be checked if there was no If-None-Match header
            // as per the HTTP spec.
            $date = HTTP\parseDate($ifModifiedSince);

            if ($date) {
                if (is_null($node)) {
                    $node = $this->tree->getNodeForPath($path);
                }
                $lastMod = $node->getLastModified();
                if ($lastMod) {
                    $lastMod = new \DateTime('@'.$lastMod);
                    if ($lastMod <= $date) {
                        $response->setStatus(304);
                        $response->setHeader('Last-Modified', HTTP\toDate($lastMod));

                        return false;
                    }
                }
            }
        }

        if ($ifUnmodifiedSince = $request->getHeader('If-Unmodified-Since')) {
            // The If-Unmodified-Since will allow allow the request if the
            // entity has not changed since the specified date.
            $date = HTTP\parseDate($ifUnmodifiedSince);

            // We must only check the date if it's valid
            if ($date) {
                if (is_null($node)) {
                    $node = $this->tree->getNodeForPath($path);
                }
                $lastMod = $node->getLastModified();
                if ($lastMod) {
                    $lastMod = new \DateTime('@'.$lastMod);
                    if ($lastMod > $date) {
                        throw new Exception\PreconditionFailed('An If-Unmodified-Since header was specified, but the entity has been changed since the specified date.', 'If-Unmodified-Since');
                    }
                }
            }
        }

        // Now the hardest, the If: header. The If: header can contain multiple
        // urls, ETags and so-called 'state tokens'.
        //
        // Examples of state tokens include lock-tokens (as defined in rfc4918)
        // and sync-tokens (as defined in rfc6578).
        //
        // The only proper way to deal with these, is to emit events, that a
        // Sync and Lock plugin can pick up.
        $ifConditions = $this->getIfConditions($request);

        foreach ($ifConditions as $kk => $ifCondition) {
            foreach ($ifCondition['tokens'] as $ii => $token) {
                $ifConditions[$kk]['tokens'][$ii]['validToken'] = false;
            }
        }

        // Plugins are responsible for validating all the tokens.
        // If a plugin deemed a token 'valid', it will set 'validToken' to
        // true.
        $this->emit('validateTokens', [$request, &$ifConditions]);

        // Now we're going to analyze the result.

        // Every ifCondition needs to validate to true, so we exit as soon as
        // we have an invalid condition.
        foreach ($ifConditions as $ifCondition) {
            $uri = $ifCondition['uri'];
            $tokens = $ifCondition['tokens'];

            // We only need 1 valid token for the condition to succeed.
            foreach ($tokens as $token) {
                $tokenValid = $token['validToken'] || !$token['token'];

                $etagValid = false;
                if (!$token['etag']) {
                    $etagValid = true;
                }
                // Checking the ETag, only if the token was already deemed
                // valid and there is one.
                if ($token['etag'] && $tokenValid) {
                    // The token was valid, and there was an ETag. We must
                    // grab the current ETag and check it.
                    $node = $this->tree->getNodeForPath($uri);
                    $etagValid = $node instanceof IFile && $node->getETag() == $token['etag'];
                }

                if (($tokenValid && $etagValid) ^ $token['negate']) {
                    // Both were valid, so we can go to the next condition.
                    continue 2;
                }
            }

            // If we ended here, it means there was no valid ETag + token
            // combination found for the current condition. This means we fail!
            throw new Exception\PreconditionFailed('Failed to find a valid token/etag combination for '.$uri, 'If');
        }

        return true;
    }

    /**
     * This method is created to extract information from the WebDAV HTTP 'If:' header.
     *
     * The If header can be quite complex, and has a bunch of features. We're using a regex to extract all relevant information
     * The function will return an array, containing structs with the following keys
     *
     *   * uri   - the uri the condition applies to.
     *   * tokens - The lock token. another 2 dimensional array containing 3 elements
     *
     * Example 1:
     *
     * If: (<opaquelocktoken:181d4fae-7d8c-11d0-a765-00a0c91e6bf2>)
     *
     * Would result in:
     *
     * [
     *    [
     *       'uri' => '/request/uri',
     *       'tokens' => [
     *          [
     *              [
     *                  'negate' => false,
     *                  'token'  => 'opaquelocktoken:181d4fae-7d8c-11d0-a765-00a0c91e6bf2',
     *                  'etag'   => ""
     *              ]
     *          ]
     *       ],
     *    ]
     * ]
     *
     * Example 2:
     *
     * If: </path/> (Not <opaquelocktoken:181d4fae-7d8c-11d0-a765-00a0c91e6bf2> ["Im An ETag"]) (["Another ETag"]) </path2/> (Not ["Path2 ETag"])
     *
     * Would result in:
     *
     * [
     *    [
     *       'uri' => 'path',
     *       'tokens' => [
     *          [
     *              [
     *                  'negate' => true,
     *                  'token'  => 'opaquelocktoken:181d4fae-7d8c-11d0-a765-00a0c91e6bf2',
     *                  'etag'   => '"Im An ETag"'
     *              ],
     *              [
     *                  'negate' => false,
     *                  'token'  => '',
     *                  'etag'   => '"Another ETag"'
     *              ]
     *          ]
     *       ],
     *    ],
     *    [
     *       'uri' => 'path2',
     *       'tokens' => [
     *          [
     *              [
     *                  'negate' => true,
     *                  'token'  => '',
     *                  'etag'   => '"Path2 ETag"'
     *              ]
     *          ]
     *       ],
     *    ],
     * ]
     *
     * @return array
     */
    public function getIfConditions(RequestInterface $request)
    {
        $header = $request->getHeader('If');
        if (!$header) {
            return [];
        }

        $matches = [];

        $regex = '/(?:\<(?P<uri>.*?)\>\s)?\((?P<not>Not\s)?(?:\<(?P<token>[^\>]*)\>)?(?:\s?)(?:\[(?P<etag>[^\]]*)\])?\)/im';
        preg_match_all($regex, $header, $matches, PREG_SET_ORDER);

        $conditions = [];

        foreach ($matches as $match) {
            // If there was no uri specified in this match, and there were
            // already conditions parsed, we add the condition to the list of
            // conditions for the previous uri.
            if (!$match['uri'] && count($conditions)) {
                $conditions[count($conditions) - 1]['tokens'][] = [
                    'negate' => $match['not'] ? true : false,
                    'token' => $match['token'],
                    'etag' => isset($match['etag']) ? $match['etag'] : '',
                ];
            } else {
                if (!$match['uri']) {
                    $realUri = $request->getPath();
                } else {
                    $realUri = $this->calculateUri($match['uri']);
                }

                $conditions[] = [
                    'uri' => $realUri,
                    'tokens' => [
                        [
                            'negate' => $match['not'] ? true : false,
                            'token' => $match['token'],
                            'etag' => isset($match['etag']) ? $match['etag'] : '',
                        ],
                    ],
                ];
            }
        }

        return $conditions;
    }

    /**
     * Returns an array with resourcetypes for a node.
     *
     * @return array
     */
    public function getResourceTypeForNode(INode $node)
    {
        $result = [];
        foreach ($this->resourceTypeMapping as $className => $resourceType) {
            if ($node instanceof $className) {
                $result[] = $resourceType;
            }
        }

        return $result;
    }

    // }}}
    // {{{ XML Readers & Writers

    /**
     * Returns a callback generating a WebDAV propfind response body based on a list of nodes.
     *
     * If 'strip404s' is set to true, all 404 responses will be removed.
     *
     * @param array|\Traversable $fileProperties The list with nodes
     * @param bool               $strip404s
     *
     * @return callable|string
     */
    public function generateMultiStatus($fileProperties, $strip404s = false)
    {
        $this->emit('beforeMultiStatus', [&$fileProperties]);

        $w = $this->xml->getWriter();
        if (self::$streamMultiStatus) {
            return function () use ($fileProperties, $strip404s, $w) {
                $w->openUri('php://output');
                $this->writeMultiStatus($w, $fileProperties, $strip404s);
                $w->flush();
            };
        }
        $w->openMemory();
        $this->writeMultiStatus($w, $fileProperties, $strip404s);

        return $w->outputMemory();
    }

    /**
     * @param $fileProperties
     */
    private function writeMultiStatus(Writer $w, $fileProperties, bool $strip404s)
    {
        $w->contextUri = $this->baseUri;
        $w->startDocument();

        $w->startElement('{DAV:}multistatus');

        foreach ($fileProperties as $entry) {
            $href = $entry['href'];
            unset($entry['href']);
            if ($strip404s) {
                unset($entry[404]);
            }
            $response = new Xml\Element\Response(
                ltrim($href, '/'),
                $entry
            );
            $w->write([
                'name' => '{DAV:}response',
                'value' => $response,
            ]);
        }
        $w->endElement();
        $w->endDocument();
    }
}
