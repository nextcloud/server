<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Vincent Petry <pvince81@owncloud.com>
 *
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

use \Sabre\DAV\PropFind;
use \Sabre\DAV\PropPatch;
use OCP\IUserSession;
use OCP\Share\IShare;
use OCA\DAV\Connector\Sabre\ShareTypeList;

/**
 * Sabre Plugin to provide share-related properties
 */
class SharesPlugin extends \Sabre\DAV\ServerPlugin {

	const NS_OWNCLOUD = 'http://owncloud.org/ns';
	const SHARETYPES_PROPERTYNAME = '{http://owncloud.org/ns}share-types';

	/**
	 * Reference to main server object
	 *
	 * @var \Sabre\DAV\Server
	 */
	private $server;

	/**
	 * @var \OCP\Share\IManager
	 */
	private $shareManager;

	/**
	 * @var \Sabre\DAV\Tree
	 */
	private $tree;

	/**
	 * @var string
	 */
	private $userId;

	/**
	 * @var \OCP\Files\Folder
	 */
	private $userFolder;

	/**
	 * @var IShare[]
	 */
	private $cachedShareTypes;

	private $cachedFolders = [];

	/**
	 * @param \Sabre\DAV\Tree $tree tree
	 * @param IUserSession $userSession user session
	 * @param \OCP\Files\Folder $userFolder user home folder
	 * @param \OCP\Share\IManager $shareManager share manager
	 */
	public function __construct(
		\Sabre\DAV\Tree $tree,
		IUserSession $userSession,
		\OCP\Files\Folder $userFolder,
		\OCP\Share\IManager $shareManager
	) {
		$this->tree = $tree;
		$this->shareManager = $shareManager;
		$this->userFolder = $userFolder;
		$this->userId = $userSession->getUser()->getUID();
		$this->cachedShareTypes = [];
	}

	/**
	 * This initializes the plugin.
	 *
	 * This function is called by \Sabre\DAV\Server, after
	 * addPlugin is called.
	 *
	 * This method should set up the required event subscriptions.
	 *
	 * @param \Sabre\DAV\Server $server
	 */
	public function initialize(\Sabre\DAV\Server $server) {
		$server->xml->namespacesMap[self::NS_OWNCLOUD] = 'oc';
		$server->xml->elementMap[self::SHARETYPES_PROPERTYNAME] = 'OCA\\DAV\\Connector\\Sabre\\ShareTypeList';
		$server->protectedProperties[] = self::SHARETYPES_PROPERTYNAME;

		$this->server = $server;
		$this->server->on('propFind', array($this, 'handleGetProperties'));
	}

	/**
	 * Return a list of share types for outgoing shares
	 *
	 * @param \OCP\Files\Node $node file node
	 *
	 * @return int[] array of share types
	 */
	private function getShareTypes(\OCP\Files\Node $node) {
		$shareTypes = [];
		$requestedShareTypes = [
			\OCP\Share::SHARE_TYPE_USER,
			\OCP\Share::SHARE_TYPE_GROUP,
			\OCP\Share::SHARE_TYPE_LINK,
			\OCP\Share::SHARE_TYPE_REMOTE,
			\OCP\Share::SHARE_TYPE_EMAIL,
		];
		foreach ($requestedShareTypes as $requestedShareType) {
			// one of each type is enough to find out about the types
			$shares = $this->shareManager->getSharesBy(
				$this->userId,
				$requestedShareType,
				$node,
				false,
				1
			);
			if (!empty($shares)) {
				$shareTypes[] = $requestedShareType;
			}
		}
		return $shareTypes;
	}

	private function getSharesTypesInFolder(\OCP\Files\Folder $node) {
		$shares = $this->shareManager->getSharesInFolder(
			$this->userId,
			$node,
			false
		);

		$shareTypesByFileId = [];

		foreach($shares as $fileId => $sharesForFile) {
			$types = array_map(function(IShare $share) {
				return $share->getShareType();
			}, $sharesForFile);
			$types = array_unique($types);
			sort($types);
			$shareTypesByFileId[$fileId] = $types;
		}

		return $shareTypesByFileId;
	}

	/**
	 * Adds shares to propfind response
	 *
	 * @param PropFind $propFind propfind object
	 * @param \Sabre\DAV\INode $sabreNode sabre node
	 */
	public function handleGetProperties(
		PropFind $propFind,
		\Sabre\DAV\INode $sabreNode
	) {
		if (!($sabreNode instanceof \OCA\DAV\Connector\Sabre\Node)) {
			return;
		}

		// need prefetch ?
		if ($sabreNode instanceof \OCA\DAV\Connector\Sabre\Directory
			&& $propFind->getDepth() !== 0
			&& !is_null($propFind->getStatus(self::SHARETYPES_PROPERTYNAME))
		) {
			$folderNode = $this->userFolder->get($sabreNode->getPath());

			$childShares = $this->getSharesTypesInFolder($folderNode);
			$this->cachedFolders[] = $sabreNode->getPath();
			$this->cachedShareTypes[$folderNode->getId()] = $this->getShareTypes($folderNode);
			foreach ($childShares as $id => $shares) {
				$this->cachedShareTypes[$id] = $shares;
			}
		}

		$propFind->handle(self::SHARETYPES_PROPERTYNAME, function () use ($sabreNode) {
			if (isset($this->cachedShareTypes[$sabreNode->getId()])) {
				$shareTypes = $this->cachedShareTypes[$sabreNode->getId()];
			} else {
				list($parentPath,) = \Sabre\Uri\split($sabreNode->getPath());
				if ($parentPath === '') {
					$parentPath = '/';
				}
				// if we already cached the folder this file is in we know there are no shares for this file
				if (array_search($parentPath, $this->cachedFolders) === false) {
					$node = $this->userFolder->get($sabreNode->getPath());
					$shareTypes = $this->getShareTypes($node);
				} else {
					return [];
				}
			}

			return new ShareTypeList($shareTypes);
		});
	}
}
