<?php
declare(strict_types=1);
/**
 * @copyright 2018, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Files_Trashbin\Sabre;

use OCA\DAV\Connector\Sabre\FilesPlugin;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;

class PropfindPlugin extends ServerPlugin {

	const TRASHBIN_FILENAME = '{http://nextcloud.org/ns}trashbin-filename';
	const TRASHBIN_ORIGINAL_LOCATION = '{http://nextcloud.org/ns}trashbin-original-location';
	const TRASHBIN_DELETION_TIME = '{http://nextcloud.org/ns}trashbin-deletion-time';

	/** @var Server */
	private $server;

	public function __construct() {
	}

	public function initialize(Server $server) {
		$this->server = $server;

		$this->server->on('propFind', [$this, 'propFind']);
	}


	public function propFind(PropFind $propFind, INode $node) {
		if (!($node instanceof ITrash)) {
			return;
		}

		$propFind->handle(self::TRASHBIN_FILENAME, function() use ($node) {
			return $node->getFilename();
		});

		$propFind->handle(self::TRASHBIN_ORIGINAL_LOCATION, function() use ($node) {
			return $node->getOriginalLocation();
		});

		$propFind->handle(self::TRASHBIN_DELETION_TIME, function () use ($node) {
			return $node->getDeletionTime();
		});

		$propFind->handle(FilesPlugin::SIZE_PROPERTYNAME, function () use ($node) {
			return $node->getSize();
		});

		$propFind->handle(FilesPlugin::FILEID_PROPERTYNAME, function () use ($node) {
			return $node->getFileId();
		});
	}

}
