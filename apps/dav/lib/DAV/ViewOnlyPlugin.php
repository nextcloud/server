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
use OCA\Files_Versions\Sabre\VersionFile;
use OCP\Files\NotFoundException;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\DAV\Exception\NotFound;

/**
 * Sabre plugin for restricting file share receiver download:
 */
class ViewOnlyPlugin extends ServerPlugin {
	private ?Server $server = null;
	private LoggerInterface $logger;

	public function __construct(LoggerInterface $logger) {
		$this->logger = $logger;
	}

	/**
	 * This initializes the plugin.
	 *
	 * This function is called by Sabre\DAV\Server, after
	 * addPlugin is called.
	 *
	 * This method should set up the required event subscriptions.
	 */
	public function initialize(Server $server): void {
		$this->server = $server;
		//priority 90 to make sure the plugin is called before
		//Sabre\DAV\CorePlugin::httpGet
		$this->server->on('method:GET', [$this, 'checkViewOnly'], 90);
		$this->server->on('method:COPY', [$this, 'checkViewOnly'], 90);
	}

	/**
	 * Disallow download via DAV Api in case file being received share
	 * and having special permission
	 *
	 * @throws Forbidden
	 * @throws NotFoundException
	 */
	public function checkViewOnly(RequestInterface $request): bool {
		$path = $request->getPath();

		try {
			assert($this->server !== null);
			$davNode = $this->server->tree->getNodeForPath($path);
			if ($davNode instanceof DavFile) {
				// Restrict view-only to nodes which are shared
				$node = $davNode->getNode();
			} else if ($davNode instanceof VersionFile) {
				$node = $davNode->getVersion()->getSourceFile();
			} else {
				return true;
			}

			$storage = $node->getStorage();

			if (!$storage->instanceOfStorage(\OCA\Files_Sharing\SharedStorage::class)) {
				return true;
			}
			// Extract extra permissions
			/** @var \OCA\Files_Sharing\SharedStorage $storage */
			$share = $storage->getShare();

			$attributes = $share->getAttributes();
			if ($attributes === null) {
				return true;
			}

			// Check if read-only and on whether permission can download is both set and disabled.
			$canDownload = $attributes->getAttribute('permissions', 'download');
			if ($canDownload !== null && !$canDownload) {
				throw new Forbidden('Access to this resource has been denied because it is in view-only mode.');
			}
		} catch (NotFound $e) {
			// File not found
		}

		return true;
	}
}
