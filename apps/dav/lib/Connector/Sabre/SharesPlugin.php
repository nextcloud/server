<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Tobias Kaminsky <tobias@kaminsky.me>
 * @author Vincent Petry <vincent@nextcloud.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Connector\Sabre;

use OCA\DAV\Connector\Sabre\Node as DavNode;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\IUserSession;
use OCP\Share\IShare;
use OCP\Share\IManager;
use Sabre\DAV\PropFind;
use Sabre\DAV\Tree;
use Sabre\DAV\Server;

/**
 * Sabre Plugin to provide share-related properties
 */
class SharesPlugin extends \Sabre\DAV\ServerPlugin {
	public const NS_OWNCLOUD = 'http://owncloud.org/ns';
	public const NS_NEXTCLOUD = 'http://nextcloud.org/ns';
	public const SHARETYPES_PROPERTYNAME = '{http://owncloud.org/ns}share-types';
	public const SHAREES_PROPERTYNAME = '{http://nextcloud.org/ns}sharees';

	/**
	 * Reference to main server object
	 *
	 * @var \Sabre\DAV\Server
	 */
	private $server;
	private IManager $shareManager;
	private Tree $tree;
	private string $userId;
	private Folder $userFolder;
	/** @var IShare[][] */
	private array $cachedShares = [];
	/** @var string[] */
	private array $cachedFolders = [];

	public function __construct(
		Tree $tree,
		IUserSession $userSession,
		Folder $userFolder,
		IManager $shareManager
	) {
		$this->tree = $tree;
		$this->shareManager = $shareManager;
		$this->userFolder = $userFolder;
		$this->userId = $userSession->getUser()->getUID();
	}

	/**
	 * This initializes the plugin.
	 *
	 * This function is called by \Sabre\DAV\Server, after
	 * addPlugin is called.
	 *
	 * This method should set up the required event subscriptions.
	 *
	 * @return void
	 */
	public function initialize(Server $server) {
		$server->xml->namespaceMap[self::NS_OWNCLOUD] = 'oc';
		$server->xml->elementMap[self::SHARETYPES_PROPERTYNAME] = ShareTypeList::class;
		$server->protectedProperties[] = self::SHARETYPES_PROPERTYNAME;
		$server->protectedProperties[] = self::SHAREES_PROPERTYNAME;

		$this->server = $server;
		$this->server->on('propFind', [$this, 'handleGetProperties']);
	}

	/**
	 * @param Node $node
	 * @return IShare[]
	 */
	private function getShare(Node $node): array {
		$result = [];
		$requestedShareTypes = [
			IShare::TYPE_USER,
			IShare::TYPE_GROUP,
			IShare::TYPE_LINK,
			IShare::TYPE_REMOTE,
			IShare::TYPE_EMAIL,
			IShare::TYPE_ROOM,
			IShare::TYPE_CIRCLE,
			IShare::TYPE_DECK,
			IShare::TYPE_SCIENCEMESH,
		];
		foreach ($requestedShareTypes as $requestedShareType) {
			$shares = $this->shareManager->getSharesBy(
				$this->userId,
				$requestedShareType,
				$node,
				false,
				-1
			);
			foreach ($shares as $share) {
				$result[] = $share;
			}
		}
		return $result;
	}

	/**
	 * @param Folder $node
	 * @return IShare[][]
	 */
	private function getSharesFolder(Folder $node): array {
		return $this->shareManager->getSharesInFolder(
			$this->userId,
			$node,
			true
		);
	}

	/**
	 * @param DavNode $sabreNode
	 * @return IShare[]
	 */
	private function getShares(DavNode $sabreNode): array {
		if (isset($this->cachedShares[$sabreNode->getId()])) {
			$shares = $this->cachedShares[$sabreNode->getId()];
		} else {
			[$parentPath,] = \Sabre\Uri\split($sabreNode->getPath());
			if ($parentPath === '') {
				$parentPath = '/';
			}
			// if we already cached the folder this file is in we know there are no shares for this file
			if (array_search($parentPath, $this->cachedFolders) === false) {
				try {
					$node = $sabreNode->getNode();
				} catch (NotFoundException $e) {
					return [];
				}
				$shares = $this->getShare($node);
				$this->cachedShares[$sabreNode->getId()] = $shares;
			} else {
				return [];
			}
		}

		return $shares;
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
		if (!($sabreNode instanceof DavNode)) {
			return;
		}

		// need prefetch ?
		if ($sabreNode instanceof Directory
			&& $propFind->getDepth() !== 0
			&& (
				!is_null($propFind->getStatus(self::SHARETYPES_PROPERTYNAME)) ||
				!is_null($propFind->getStatus(self::SHAREES_PROPERTYNAME))
			)
		) {
			$folderNode = $sabreNode->getNode();
			$this->cachedFolders[] = $sabreNode->getPath();
			$childShares = $this->getSharesFolder($folderNode);
			foreach ($childShares as $id => $shares) {
				$this->cachedShares[$id] = $shares;
			}
		}

		$propFind->handle(self::SHARETYPES_PROPERTYNAME, function () use ($sabreNode): ShareTypeList {
			$shares = $this->getShares($sabreNode);

			$shareTypes = array_unique(array_map(function (IShare $share) {
				return $share->getShareType();
			}, $shares));

			return new ShareTypeList($shareTypes);
		});

		$propFind->handle(self::SHAREES_PROPERTYNAME, function () use ($sabreNode): ShareeList {
			$shares = $this->getShares($sabreNode);

			return new ShareeList($shares);
		});
	}
}
