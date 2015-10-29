<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

namespace OCA\DAV;

use OCA\DAV\Connector\Sabre\Directory;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class ZipFolderPlugin extends ServerPlugin {

	/** @var Server */
	private $server;

	/**
	 * This initializes the plugin.
	 *
	 * This function is called by Sabre\DAV\Server, after
	 * addPlugin is called.
	 *
	 * This method should set up the required event subscriptions.
	 *
	 * @param Server $server
	 * @return void
	 */
	function initialize(Server $server) {
		$this->server = $server;
		$server->on('beforeMethod', array($this, 'downloadFolderAsZip'), 30);
	}

	function downloadFolderAsZip(RequestInterface $request, ResponseInterface $response) {
		if ($request->getMethod() !== 'GET') {
			return;
		}
		$path = $request->getPath();
		if ($this->server->tree->nodeExists($path))
			return;

		$elements = pathinfo($path);
		$ext = isset($elements['extension']) ? $elements['extension'] : null;
		if (is_null($ext) || !in_array($ext, ['zip', 'tar'])) {
			return;
		}

		$pathToFolder = substr($path, 0, -4);
		$node = $this->server->tree->getNodeForPath($pathToFolder);

		if (!$node instanceof Directory)
			return;

		//
		// TODO: build a Tar and a Zip Streamer which use php streams
		//
		$response->setBody();
	}
}
