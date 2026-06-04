<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files\Activity;

use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\ITagManager;

class Helper {
	/** If a user has a lot of favorites the query might get too slow and long */
	public const FAVORITE_LIMIT = 50;

	public function __construct(
		protected ITagManager $tagManager,
		protected IRootFolder $rootFolder,
	) {
	}

	/**
	 * Return an array with nodes marked as favorites
	 *
	 * @param string $user User ID
	 * @param bool $foldersOnly Only return folders (default false)
	 * @return Node[]
	 * @psalm-return ($foldersOnly is true ? Folder[] : Node[])
	 * @throws \RuntimeException when too many or no favorites where found
	 */
	public function getFavoriteNodes(string $user, bool $foldersOnly = false): array {
		$tags = $this->tagManager->load('files', [], false, $user);
		$favorites = $tags->getFavorites();

		if (empty($favorites)) {
			throw new \RuntimeException('No favorites', 1);
		} elseif (isset($favorites[self::FAVORITE_LIMIT])) {
			throw new \RuntimeException('Too many favorites', 2);
		}

		// Can not DI because the user is not known on instantiation
		$userFolder = $this->rootFolder->getUserFolder($user);
		$favoriteNodes = [];
		foreach ($favorites as $favorite) {
			$node = $userFolder->getFirstNodeById($favorite);
			if ($node) {
				if (!$foldersOnly || $node instanceof Folder) {
					$favoriteNodes[] = $node;
				}
			}
		}

		if (empty($favoriteNodes)) {
			throw new \RuntimeException('No favorites', 1);
		}

		return $favoriteNodes;
	}

	/**
	 * Returns an array with the favorites
	 *
	 * @param string $user
	 * @return array
	 * @throws \RuntimeException when too many or no favorites where found
	 */
	public function getFavoriteFilePaths(string $user): array {
		$userFolder = $this->rootFolder->getUserFolder($user);
		$nodes = $this->getFavoriteNodes($user);
		$folders = $items = [];
		foreach ($nodes as $node) {
			$path = $userFolder->getRelativePath($node->getPath());

			$items[] = $path;
			if ($node instanceof Folder) {
				$folders[] = $path;
			}
		}

		return [
			'items' => $items,
			'folders' => $folders,
		];
	}
}
