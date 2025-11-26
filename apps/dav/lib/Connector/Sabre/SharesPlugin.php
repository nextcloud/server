<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Connector\Sabre;

use OC\Share20\Exception\BackendError;
use OCA\DAV\Connector\Sabre\Exception\Forbidden;
use OCA\DAV\Connector\Sabre\Node as DavNode;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Files\Storage\ISharedStorage;
use OCP\IUserSession;
use OCP\Share\IManager;
use OCP\Share\IShare;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\ICollection;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\Tree;

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
	private string $userId;

	/** @var IShare[][] */
	private array $cachedShares = [];

	/**
	 * Tracks which folders have been cached.
	 * When a folder is cached, it will appear with its path as key and true
	 * as value.
	 *
	 * @var bool[]
	 */
	private array $cachedFolders = [];

	public function __construct(
		private Tree $tree,
		private IUserSession $userSession,
		private Folder $userFolder,
		private IManager $shareManager,
	) {
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
		$this->server->on('preloadCollection', $this->preloadCollection(...));
		$this->server->on('propFind', $this->handleGetProperties(...));
		$this->server->on('beforeCopy', $this->validateMoveOrCopy(...));
		$this->server->on('beforeMove', $this->validateMoveOrCopy(...));
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
			$result[] = $this->shareManager->getSharesBy(
				$this->userId,
				$requestedShareType,
				$node,
				false,
				-1
			);

			// Also check for shares where the user is the recipient
			try {
				$result[] = $this->shareManager->getSharedWith(
					$this->userId,
					$requestedShareType,
					$node,
					-1
				);
			} catch (BackendError $e) {
				// ignore
			}
		}

		return array_merge(...$result);
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
			return $this->cachedShares[$sabreNode->getId()];
		}

		[$parentPath,] = \Sabre\Uri\split($sabreNode->getPath());
		if ($parentPath === '') {
			$parentPath = '/';
		}

		// if we already cached the folder containing this file
		// then we already know there are no shares here.
		if (!isset($this->cachedFolders[$parentPath])) {
			try {
				$node = $sabreNode->getNode();
			} catch (NotFoundException $e) {
				return [];
			}

			$shares = $this->getShare($node);
			$this->cachedShares[$sabreNode->getId()] = $shares;
			return $shares;
		}

		return [];
	}

	private function preloadCollection(PropFind $propFind, ICollection $collection): void {
		if (!$collection instanceof Directory
			|| isset($this->cachedFolders[$collection->getPath()])
			|| (
				$propFind->getStatus(self::SHARETYPES_PROPERTYNAME) === null
				&& $propFind->getStatus(self::SHAREES_PROPERTYNAME) === null
			)
		) {
			return;
		}

		// If the node is a directory and we are requesting share types or sharees
		// then we get all the shares in the folder and cache them.
		// This is more performant than iterating each files afterwards.
		$folderNode = $collection->getNode();
		$this->cachedFolders[$collection->getPath()] = true;
		foreach ($this->getSharesFolder($folderNode) as $id => $shares) {
			$this->cachedShares[$id] = $shares;
		}
	}

	/**
	 * Adds shares to propfind response
	 *
	 * @param PropFind $propFind propfind object
	 * @param \Sabre\DAV\INode $sabreNode sabre node
	 */
	public function handleGetProperties(
		PropFind $propFind,
		\Sabre\DAV\INode $sabreNode,
	) {
		if (!($sabreNode instanceof DavNode)) {
			return;
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

	/**
	 * Ensure that when copying or moving a node it is not transferred from one share to another,
	 * if the user is neither the owner nor has re-share permissions.
	 * For share creation we already ensure this in the share manager.
	 */
	public function validateMoveOrCopy(string $source, string $target): bool {
		try {
			$targetNode = $this->tree->getNodeForPath($target);
		} catch (NotFound) {
			[$targetPath,] = \Sabre\Uri\split($target);
			$targetNode = $this->tree->getNodeForPath($targetPath);
		}

		$sourceNode = $this->tree->getNodeForPath($source);
		if ((!$sourceNode instanceof DavNode) || (!$targetNode instanceof DavNode)) {
			return true;
		}

		$sourceNode = $sourceNode->getNode();
		if ($sourceNode->isShareable()) {
			return true;
		}

		$targetShares = $this->getShare($targetNode->getNode());
		if (empty($targetShares)) {
			// Target is not a share so no re-sharing inprogress
			return true;
		}

		$sourceStorage = $sourceNode->getStorage();
		if ($sourceStorage->instanceOfStorage(ISharedStorage::class)) {
			// source is also a share - check if it is the same share

			/** @var ISharedStorage $sourceStorage */
			$sourceShare = $sourceStorage->getShare();
			foreach ($targetShares as $targetShare) {
				if ($targetShare->getId() === $sourceShare->getId()) {
					return true;
				}
			}
		}

		throw new Forbidden('You cannot move a non-shareable node into a share');
	}
}
