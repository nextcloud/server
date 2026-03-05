<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Connector\Sabre;

use OCP\Comments\ICommentsManager;
use OCP\IUserSession;
use Sabre\DAV\ICollection;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;

class CommentPropertiesPlugin extends ServerPlugin {
	public const PROPERTY_NAME_HREF = '{http://owncloud.org/ns}comments-href';
	public const PROPERTY_NAME_COUNT = '{http://owncloud.org/ns}comments-count';
	public const PROPERTY_NAME_UNREAD = '{http://owncloud.org/ns}comments-unread';

	protected ?Server $server = null;
	private array $cachedUnreadCount = [];
	private array $cachedCount = [];
	private array $cachedDirectories = [];

	public function __construct(
		private ICommentsManager $commentsManager,
		private IUserSession $userSession,
	) {
	}

	/**
	 * This initializes the plugin.
	 *
	 * This function is called by Sabre\DAV\Server, after
	 * addPlugin is called.
	 *
	 * This method should set up the required event subscriptions.
	 *
	 * @param \Sabre\DAV\Server $server
	 * @return void
	 */
	public function initialize(\Sabre\DAV\Server $server) {
		$this->server = $server;

		$this->server->on('preloadCollection', $this->preloadCollection(...));
		$this->server->on('propFind', [$this, 'handleGetProperties']);
	}

	private function cacheDirectory(Directory $directory, PropFind $propFind): void {
		if (is_null($propFind->getStatus(self::PROPERTY_NAME_UNREAD)) && is_null($propFind->getStatus(self::PROPERTY_NAME_COUNT))) {
			return;
		}

		$children = $directory->getChildren();

		$ids = [];
		foreach ($children as $child) {
			if (!($child instanceof File || $child instanceof Directory)) {
				continue;
			}

			$id = $child->getId();
			if ($id === null) {
				continue;
			}

			$ids[] = (string)$id;
		}

		$ids[] = (string)$directory->getId();
		if (!is_null($propFind->getStatus(self::PROPERTY_NAME_UNREAD))) {
			$user = $this->userSession->getUser();
			if ($user) {
				$unread = $this->commentsManager->getNumberOfUnreadCommentsForObjects('files', $ids, $user);
				foreach ($unread as $id => $count) {
					$this->cachedUnreadCount[(int)$id] = $count;
				}
			} else {
				foreach ($ids as $id) {
					$this->cachedUnreadCount[(int)$id] = null;
				}
			}
		}

		if (!is_null($propFind->getStatus(self::PROPERTY_NAME_COUNT))) {
			$commentCounts = $this->commentsManager->getNumberOfCommentsForObjects('files', $ids);
			foreach ($commentCounts as $id => $count) {
				$this->cachedCount[(int)$id] = $count;
			}
		}

	}

	private function preloadCollection(PropFind $propFind, ICollection $collection): void {
		if (!($collection instanceof Directory)) {
			return;
		}

		$collectionPath = $collection->getPath();
		if (!isset($this->cachedDirectories[$collectionPath]) && (
			$propFind->getStatus(self::PROPERTY_NAME_UNREAD) !== null
			|| $propFind->getStatus(self::PROPERTY_NAME_COUNT) !== null)) {
			$this->cacheDirectory($collection, $propFind);
			$this->cachedDirectories[$collectionPath] = true;
		}
	}

	/**
	 * Adds tags and favorites properties to the response,
	 * if requested.
	 *
	 * @param PropFind $propFind
	 * @param \Sabre\DAV\INode $node
	 * @return void
	 */
	public function handleGetProperties(
		PropFind $propFind,
		\Sabre\DAV\INode $node,
	) {
		if (!($node instanceof File) && !($node instanceof Directory)) {
			return;
		}

		$propFind->handle(self::PROPERTY_NAME_COUNT, function () use ($node): int {
			return $this->cachedCount[$node->getId()] ?? $this->commentsManager->getNumberOfCommentsForObject('files', (string)$node->getId());
		});

		$propFind->handle(self::PROPERTY_NAME_HREF, function () use ($node): ?string {
			return $this->getCommentsLink($node);
		});

		$propFind->handle(self::PROPERTY_NAME_UNREAD, function () use ($node): ?int {
			return $this->cachedUnreadCount[$node->getId()] ?? $this->getUnreadCount($node);
		});
	}

	/**
	 * Returns a reference to the comments node
	 */
	public function getCommentsLink(Node $node): ?string {
		$href = $this->server->getBaseUri();
		$entryPoint = strpos($href, '/remote.php/');
		if ($entryPoint === false) {
			// in case we end up somewhere else, unexpectedly.
			return null;
		}
		$commentsPart = 'dav/comments/files/' . rawurldecode((string)$node->getId());
		return substr_replace($href, $commentsPart, $entryPoint + strlen('/remote.php/'));
	}

	/**
	 * Returns the number of unread comments for the currently logged in user
	 * on the given file or directory node
	 */
	public function getUnreadCount(Node $node): ?int {
		$user = $this->userSession->getUser();
		if (is_null($user)) {
			return null;
		}

		$objectId = (string)$node->getId();
		return $this->commentsManager->getNumberOfUnreadCommentsForObjects('files', [$objectId], $user)[$objectId];
	}
}
