<?php

/**
 * ownCloud
 *
 * @author Vincent Petry
 * @copyright 2015 Vincent Petry <pvince81@owncloud.com>
 *
 * @license AGPL3
 */

namespace OC\Connector\Sabre;

use \Sabre\HTTP\RequestInterface;
use \Sabre\HTTP\ResponseInterface;

/**
 * Copies the "Etag" header to "OC-Etag" after any request.
 * This is a workaround for setups that automatically strip
 * or mangle Etag headers.
 */
class CopyEtagHeaderPlugin extends \Sabre\DAV\ServerPlugin {
	/**
	 * This initializes the plugin.
	 *
	 * @param \Sabre\DAV\Server $server Sabre server
	 *
	 * @return void
	 */
	public function initialize(\Sabre\DAV\Server $server) {
		$server->on('afterMethod', array($this, 'afterMethod'));
	}

	/**
	 * After method, copy the "Etag" header to "OC-Etag" header.
	 *
	 * @param RequestInterface $request request
	 * @param ResponseInterface $response response
	 */
	public function afterMethod(RequestInterface $request, ResponseInterface $response) {
		$eTag = $response->getHeader('Etag');
		if (!empty($eTag)) {
			$response->setHeader('OC-ETag', $eTag);
		}
	}
}
