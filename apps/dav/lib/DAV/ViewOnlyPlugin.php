<?php
/**
 * @author Piotr Mrowczynski piotr@owncloud.com
 *
 * @copyright Copyright (c) 2019, ownCloud GmbH
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

namespace OCA\DAV\DAV;

use OCA\DAV\Connector\Sabre\Exception\Forbidden;
use OCA\DAV\Connector\Sabre\File as DavFile;
use OCA\DAV\Meta\MetaFile;
use OCP\Files\FileInfo;
use OCP\Files\NotFoundException;
use OCP\ILogger;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\DAV\Exception\NotFound;

/**
 * Sabre plugin for restricting file share receiver download:
 */
class ViewOnlyPlugin extends ServerPlugin {

	/** @var Server $server */
	private $server;

	/** @var ILogger $logger */
	private $logger;

	/**
	 * @param ILogger $logger
	 */
	public function __construct(ILogger $logger) {
		$this->logger = $logger;
	}

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
	public function initialize(Server $server) {
		$this->server = $server;
		//priority 90 to make sure the plugin is called before
		//Sabre\DAV\CorePlugin::httpGet
		$this->server->on('method:GET', [$this, 'checkViewOnly'], 90);
	}

	/**
	 * Disallow download via DAV Api in case file being received share
	 * and having special permission
	 *
	 * @param RequestInterface $request request object
	 * @return boolean
	 * @throws Forbidden
	 * @throws NotFoundException
	 */
	public function checkViewOnly(
		RequestInterface $request
	) {
		$path = $request->getPath();

		try {
			$davNode = $this->server->tree->getNodeForPath($path);
			if (!($davNode instanceof DavFile)) {
				return true;
			}
			// Restrict view-only to nodes which are shared
			$node = $davNode->getNode();

			$storage = $node->getStorage();
			// using string as we have no guarantee that "files_sharing" app is loaded
			if (!$storage->instanceOfStorage('OCA\Files_Sharing\SharedStorage')) {
				return true;
			}
			// Extract extra permissions
			/** @var \OCA\Files_Sharing\SharedStorage $storage */
			$share = $storage->getShare();

			// Check if read-only and on whether permission can download is both set and disabled.
			$canDownload = $share->getAttributes()->getAttribute('permissions', 'download');
			if ($canDownload !== null && !$canDownload) {
				throw new Forbidden('Access to this resource has been denied because it is in view-only mode.');
			}
		} catch (NotFound $e) {
			$this->logger->warning($e->getMessage());
		}

		return true;
	}
}
