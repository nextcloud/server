<?php

declare(strict_types=1);

namespace Sabre\DAV;

use Sabre\HTTP;
use Sabre\Uri;

/**
 * SabreDAV DAV client.
 *
 * This client wraps around Curl to provide a convenient API to a WebDAV
 * server.
 *
 * NOTE: This class is experimental, it's api will likely change in the future.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Client extends HTTP\Client
{
    /**
     * The xml service.
     *
     * Uset this service to configure the property and namespace maps.
     *
     * @var mixed
     */
    public $xml;

    /**
     * The elementMap.
     *
     * This property is linked via reference to $this->xml->elementMap.
     * It's deprecated as of version 3.0.0, and should no longer be used.
     *
     * @deprecated
     *
     * @var array
     */
    public $propertyMap = [];

    /**
     * Base URI.
     *
     * This URI will be used to resolve relative urls.
     *
     * @var string
     */
    protected $baseUri;

    /**
     * Basic authentication.
     */
    const AUTH_BASIC = 1;

    /**
     * Digest authentication.
     */
    const AUTH_DIGEST = 2;

    /**
     * NTLM authentication.
     */
    const AUTH_NTLM = 4;

    /**
     * Identity encoding, which basically does not nothing.
     */
    const ENCODING_IDENTITY = 1;

    /**
     * Deflate encoding.
     */
    const ENCODING_DEFLATE = 2;

    /**
     * Gzip encoding.
     */
    const ENCODING_GZIP = 4;

    /**
     * Sends all encoding headers.
     */
    const ENCODING_ALL = 7;

    /**
     * Content-encoding.
     *
     * @var int
     */
    protected $encoding = self::ENCODING_IDENTITY;

    /**
     * Constructor.
     *
     * Settings are provided through the 'settings' argument. The following
     * settings are supported:
     *
     *   * baseUri
     *   * userName (optional)
     *   * password (optional)
     *   * proxy (optional)
     *   * authType (optional)
     *   * encoding (optional)
     *
     *  authType must be a bitmap, using self::AUTH_BASIC, self::AUTH_DIGEST
     *  and self::AUTH_NTLM. If you know which authentication method will be
     *  used, it's recommended to set it, as it will save a great deal of
     *  requests to 'discover' this information.
     *
     *  Encoding is a bitmap with one of the ENCODING constants.
     */
    public function __construct(array $settings)
    {
        if (!isset($settings['baseUri'])) {
            throw new \InvalidArgumentException('A baseUri must be provided');
        }

        parent::__construct();

        $this->baseUri = $settings['baseUri'];

        if (isset($settings['proxy'])) {
            $this->addCurlSetting(CURLOPT_PROXY, $settings['proxy']);
        }

        if (isset($settings['userName'])) {
            $userName = $settings['userName'];
            $password = isset($settings['password']) ? $settings['password'] : '';

            if (isset($settings['authType'])) {
                $curlType = 0;
                if ($settings['authType'] & self::AUTH_BASIC) {
                    $curlType |= CURLAUTH_BASIC;
                }
                if ($settings['authType'] & self::AUTH_DIGEST) {
                    $curlType |= CURLAUTH_DIGEST;
                }
                if ($settings['authType'] & self::AUTH_NTLM) {
                    $curlType |= CURLAUTH_NTLM;
                }
            } else {
                $curlType = CURLAUTH_BASIC | CURLAUTH_DIGEST;
            }

            $this->addCurlSetting(CURLOPT_HTTPAUTH, $curlType);
            $this->addCurlSetting(CURLOPT_USERPWD, $userName.':'.$password);
        }

        if (isset($settings['encoding'])) {
            $encoding = $settings['encoding'];

            $encodings = [];
            if ($encoding & self::ENCODING_IDENTITY) {
                $encodings[] = 'identity';
            }
            if ($encoding & self::ENCODING_DEFLATE) {
                $encodings[] = 'deflate';
            }
            if ($encoding & self::ENCODING_GZIP) {
                $encodings[] = 'gzip';
            }
            $this->addCurlSetting(CURLOPT_ENCODING, implode(',', $encodings));
        }

        $this->addCurlSetting(CURLOPT_USERAGENT, 'sabre-dav/'.Version::VERSION.' (http://sabre.io/)');

        $this->xml = new Xml\Service();
        // BC
        $this->propertyMap = &$this->xml->elementMap;
    }

    /**
     * Does a PROPFIND request with filtered response returning only available properties.
     *
     * The list of requested properties must be specified as an array, in clark
     * notation.
     *
     * Depth should be either 0 or 1. A depth of 1 will cause a request to be
     * made to the server to also return all child resources.
     *
     * For depth 0, just the array of properties for the resource is returned.
     *
     * For depth 1, the returned array will contain a list of resource names as keys,
     * and an array of properties as values.
     *
     * The array of properties will contain the properties as keys with their values as the value.
     * Only properties that are actually returned from the server without error will be
     * returned, anything else is discarded.
     *
     * @param 1|0 $depth
     */
    public function propFind($url, array $properties, $depth = 0): array
    {
        $result = $this->doPropFind($url, $properties, $depth);

        // If depth was 0, we only return the top item
        if (0 === $depth) {
            reset($result);
            $result = current($result);

            return isset($result[200]) ? $result[200] : [];
        }

        $newResult = [];
        foreach ($result as $href => $statusList) {
            $newResult[$href] = isset($statusList[200]) ? $statusList[200] : [];
        }

        return $newResult;
    }

    /**
     * Does a PROPFIND request with unfiltered response.
     *
     * The list of requested properties must be specified as an array, in clark
     * notation.
     *
     * Depth should be either 0 or 1. A depth of 1 will cause a request to be
     * made to the server to also return all child resources.
     *
     * For depth 0, just the multi-level array of status and properties for the resource is returned.
     *
     * For depth 1, the returned array will contain a list of resources as keys and
     * a multi-level array containing status and properties as value.
     *
     * The multi-level array of status and properties is formatted the same as what is
     * documented for parseMultiStatus.
     *
     * All properties that are actually returned from the server are returned by this method.
     *
     * @param 1|0 $depth
     */
    public function propFindUnfiltered(string $url, array $properties, int $depth = 0): array
    {
        $result = $this->doPropFind($url, $properties, $depth);

        // If depth was 0, we only return the top item
        if (0 === $depth) {
            reset($result);

            return current($result);
        } else {
            return $result;
        }
    }

    /**
     * Does a PROPFIND request.
     *
     * The list of requested properties must be specified as an array, in clark
     * notation.
     *
     * Depth should be either 0 or 1. A depth of 1 will cause a request to be
     * made to the server to also return all child resources.
     *
     * The returned array will contain a list of resources as keys and
     * a multi-level array containing status and properties as value.
     *
     * The multi-level array of status and properties is formatted the same as what is
     * documented for parseMultiStatus.
     *
     * @param 1|0 $depth
     */
    private function doPropFind($url, array $properties, $depth = 0): array
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $root = $dom->createElementNS('DAV:', 'd:propfind');
        $prop = $dom->createElement('d:prop');

        foreach ($properties as $property) {
            list(
                $namespace,
                $elementName
            ) = \Sabre\Xml\Service::parseClarkNotation($property);

            if ('DAV:' === $namespace) {
                $element = $dom->createElement('d:'.$elementName);
            } else {
                $element = $dom->createElementNS($namespace, 'x:'.$elementName);
            }

            $prop->appendChild($element);
        }

        $dom->appendChild($root)->appendChild($prop);
        $body = $dom->saveXML();

        $url = $this->getAbsoluteUrl($url);

        $request = new HTTP\Request('PROPFIND', $url, [
            'Depth' => $depth,
            'Content-Type' => 'application/xml',
        ], $body);

        $response = $this->send($request);

        if ((int) $response->getStatus() >= 400) {
            throw new HTTP\ClientHttpException($response);
        }

        return $this->parseMultiStatus($response->getBodyAsString());
    }

    /**
     * Updates a list of properties on the server.
     *
     * The list of properties must have clark-notation properties for the keys,
     * and the actual (string) value for the value. If the value is null, an
     * attempt is made to delete the property.
     *
     * @param string $url
     *
     * @return bool
     */
    public function propPatch($url, array $properties)
    {
        $propPatch = new Xml\Request\PropPatch();
        $propPatch->properties = $properties;
        $xml = $this->xml->write(
            '{DAV:}propertyupdate',
            $propPatch
        );

        $url = $this->getAbsoluteUrl($url);
        $request = new HTTP\Request('PROPPATCH', $url, [
            'Content-Type' => 'application/xml',
        ], $xml);
        $response = $this->send($request);

        if ($response->getStatus() >= 400) {
            throw new HTTP\ClientHttpException($response);
        }

        if (207 === $response->getStatus()) {
            // If it's a 207, the request could still have failed, but the
            // information is hidden in the response body.
            $result = $this->parseMultiStatus($response->getBodyAsString());

            $errorProperties = [];
            foreach ($result as $href => $statusList) {
                foreach ($statusList as $status => $properties) {
                    if ($status >= 400) {
                        foreach ($properties as $propName => $propValue) {
                            $errorProperties[] = $propName.' ('.$status.')';
                        }
                    }
                }
            }
            if ($errorProperties) {
                throw new HTTP\ClientException('PROPPATCH failed. The following properties errored: '.implode(', ', $errorProperties));
            }
        }

        return true;
    }

    /**
     * Performs an HTTP options request.
     *
     * This method returns all the features from the 'DAV:' header as an array.
     * If there was no DAV header, or no contents this method will return an
     * empty array.
     *
     * @return array
     */
    public function options()
    {
        $request = new HTTP\Request('OPTIONS', $this->getAbsoluteUrl(''));
        $response = $this->send($request);

        $dav = $response->getHeader('Dav');
        if (!$dav) {
            return [];
        }

        $features = explode(',', $dav);
        foreach ($features as &$v) {
            $v = trim($v);
        }

        return $features;
    }

    /**
     * Performs an actual HTTP request, and returns the result.
     *
     * If the specified url is relative, it will be expanded based on the base
     * url.
     *
     * The returned array contains 3 keys:
     *   * body - the response body
     *   * httpCode - a HTTP code (200, 404, etc)
     *   * headers - a list of response http headers. The header names have
     *     been lowercased.
     *
     * For large uploads, it's highly recommended to specify body as a stream
     * resource. You can easily do this by simply passing the result of
     * fopen(..., 'r').
     *
     * This method will throw an exception if an HTTP error was received. Any
     * HTTP status code above 399 is considered an error.
     *
     * Note that it is no longer recommended to use this method, use the send()
     * method instead.
     *
     * @param string               $method
     * @param string               $url
     * @param string|resource|null $body
     *
     * @throws clientException, in case a curl error occurred
     *
     * @return array
     */
    public function request($method, $url = '', $body = null, array $headers = [])
    {
        $url = $this->getAbsoluteUrl($url);

        $response = $this->send(new HTTP\Request($method, $url, $headers, $body));

        return [
            'body' => $response->getBodyAsString(),
            'statusCode' => (int) $response->getStatus(),
            'headers' => array_change_key_case($response->getHeaders()),
        ];
    }

    /**
     * Returns the full url based on the given url (which may be relative). All
     * urls are expanded based on the base url as given by the server.
     *
     * @param string $url
     *
     * @return string
     */
    public function getAbsoluteUrl($url)
    {
        return Uri\resolve(
            $this->baseUri,
            (string) $url
        );
    }

    /**
     * Parses a WebDAV multistatus response body.
     *
     * This method returns an array with the following structure
     *
     * [
     *   'url/to/resource' => [
     *     '200' => [
     *        '{DAV:}property1' => 'value1',
     *        '{DAV:}property2' => 'value2',
     *     ],
     *     '404' => [
     *        '{DAV:}property1' => null,
     *        '{DAV:}property2' => null,
     *     ],
     *   ],
     *   'url/to/resource2' => [
     *      .. etc ..
     *   ]
     * ]
     *
     * @param string $body xml body
     *
     * @return array
     */
    public function parseMultiStatus($body)
    {
        $multistatus = $this->xml->expect('{DAV:}multistatus', $body);

        $result = [];

        foreach ($multistatus->getResponses() as $response) {
            $result[$response->getHref()] = $response->getResponseProperties();
        }

        return $result;
    }
}
