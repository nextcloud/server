<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\DAV\Connector\Sabre;

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
