<?php

declare(strict_types=1);

namespace Sabre\DAV\PartialUpdate;

use Sabre\DAV;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * Partial update plugin (Patch method).
 *
 * This plugin provides a way to modify only part of a target resource
 * It may bu used to update a file chunk, upload big a file into smaller
 * chunks or resume an upload.
 *
 * $patchPlugin = new \Sabre\DAV\PartialUpdate\Plugin();
 * $server->addPlugin($patchPlugin);
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Jean-Tiare LE BIGOT (http://www.jtlebi.fr/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Plugin extends DAV\ServerPlugin
{
    const RANGE_APPEND = 1;
    const RANGE_START = 2;
    const RANGE_END = 3;

    /**
     * Reference to server.
     *
     * @var DAV\Server
     */
    protected $server;

    /**
     * Initializes the plugin.
     *
     * This method is automatically called by the Server class after addPlugin.
     */
    public function initialize(DAV\Server $server)
    {
        $this->server = $server;
        $server->on('method:PATCH', [$this, 'httpPatch']);
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
        return 'partialupdate';
    }

    /**
     * Use this method to tell the server this plugin defines additional
     * HTTP methods.
     *
     * This method is passed a uri. It should only return HTTP methods that are
     * available for the specified uri.
     *
     * We claim to support PATCH method (partirl update) if and only if
     *     - the node exist
     *     - the node implements our partial update interface
     *
     * @param string $uri
     *
     * @return array
     */
    public function getHTTPMethods($uri)
    {
        $tree = $this->server->tree;

        if ($tree->nodeExists($uri)) {
            $node = $tree->getNodeForPath($uri);
            if ($node instanceof IPatchSupport) {
                return ['PATCH'];
            }
        }

        return [];
    }

    /**
     * Returns a list of features for the HTTP OPTIONS Dav: header.
     *
     * @return array
     */
    public function getFeatures()
    {
        return ['sabredav-partialupdate'];
    }

    /**
     * Patch an uri.
     *
     * The WebDAV patch request can be used to modify only a part of an
     * existing resource. If the resource does not exist yet and the first
     * offset is not 0, the request fails
     */
    public function httpPatch(RequestInterface $request, ResponseInterface $response)
    {
        $path = $request->getPath();

        // Get the node. Will throw a 404 if not found
        $node = $this->server->tree->getNodeForPath($path);
        if (!$node instanceof IPatchSupport) {
            throw new DAV\Exception\MethodNotAllowed('The target resource does not support the PATCH method.');
        }

        $range = $this->getHTTPUpdateRange($request);

        if (!$range) {
            throw new DAV\Exception\BadRequest('No valid "X-Update-Range" found in the headers');
        }

        $contentType = strtolower(
            (string) $request->getHeader('Content-Type')
        );

        if ('application/x-sabredav-partialupdate' != $contentType) {
            throw new DAV\Exception\UnsupportedMediaType('Unknown Content-Type header "'.$contentType.'"');
        }

        $len = $this->server->httpRequest->getHeader('Content-Length');
        if (!$len) {
            throw new DAV\Exception\LengthRequired('A Content-Length header is required');
        }
        switch ($range[0]) {
            case self::RANGE_START:
                // Calculate the end-range if it doesn't exist.
                if (!$range[2]) {
                    $range[2] = $range[1] + $len - 1;
                } else {
                    if ($range[2] < $range[1]) {
                        throw new DAV\Exception\RequestedRangeNotSatisfiable('The end offset ('.$range[2].') is lower than the start offset ('.$range[1].')');
                    }
                    if ($range[2] - $range[1] + 1 != $len) {
                        throw new DAV\Exception\RequestedRangeNotSatisfiable('Actual data length ('.$len.') is not consistent with begin ('.$range[1].') and end ('.$range[2].') offsets');
                    }
                }
                break;
        }

        if (!$this->server->emit('beforeWriteContent', [$path, $node, null])) {
            return;
        }

        $body = $this->server->httpRequest->getBody();

        $etag = $node->patch($body, $range[0], isset($range[1]) ? $range[1] : null);

        $this->server->emit('afterWriteContent', [$path, $node]);

        $response->setHeader('Content-Length', '0');
        if ($etag) {
            $response->setHeader('ETag', $etag);
        }
        $response->setStatus(204);

        // Breaks the event chain
        return false;
    }

    /**
     * Returns the HTTP custom range update header.
     *
     * This method returns null if there is no well-formed HTTP range request
     * header. It returns array(1) if it was an append request, array(2,
     * $start, $end) if it's a start and end range, lastly it's array(3,
     * $endoffset) if the offset was negative, and should be calculated from
     * the end of the file.
     *
     * Examples:
     *
     * null - invalid
     * [1] - append
     * [2,10,15] - update bytes 10, 11, 12, 13, 14, 15
     * [2,10,null] - update bytes 10 until the end of the patch body
     * [3,-5] - update from 5 bytes from the end of the file.
     *
     * @return array|null
     */
    public function getHTTPUpdateRange(RequestInterface $request)
    {
        $range = $request->getHeader('X-Update-Range');
        if (is_null($range)) {
            return null;
        }

        // Matching "Range: bytes=1234-5678: both numbers are optional

        if (!preg_match('/^(append)|(?:bytes=([0-9]+)-([0-9]*))|(?:bytes=(-[0-9]+))$/i', $range, $matches)) {
            return null;
        }

        if ('append' === $matches[1]) {
            return [self::RANGE_APPEND];
        } elseif (strlen($matches[2]) > 0) {
            return [self::RANGE_START, (int) $matches[2], (int) $matches[3] ?: null];
        } else {
            return [self::RANGE_END, (int) $matches[4]];
        }
    }
}
