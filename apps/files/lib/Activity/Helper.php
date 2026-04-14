<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files\Activity;

use OC\Files\Search\SearchBinaryOperator;
use OC\Files\Search\SearchComparison;
use OC\Files\Search\SearchOrder;
use OC\Files\Search\SearchQuery;
use OCP\Files\FileInfo;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOrder;
use OCP\IUserManager;

class Helper {
	/** If a user has a lot of favorites the query might get too slow and long */
	public const FAVORITE_LIMIT = 50;

	public function __construct(
		protected IRootFolder $rootFolder,
		protected IUserManager $userManager,
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
		$userObject = $this->userManager->get($user);
		if ($userObject === null) {
			throw new \RuntimeException('No favorites', 1);
		}

		$userFolder = $this->rootFolder->getUserFolder($user);

		$operator = new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'favorite', true);
		if ($foldersOnly) {
			$operator = new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_AND, [
				$operator,
				new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'mimetype', FileInfo::MIMETYPE_FOLDER),
			]);
		}

		$favoriteNodes = $userFolder->search(new SearchQuery(
			$operator,
			self::FAVORITE_LIMIT + 1,
			0,
			[
				new SearchOrder(ISearchOrder::DIRECTION_DESCENDING, 'mtime'),
			],
			$userObject,
		));

		if (empty($favoriteNodes)) {
			throw new \RuntimeException('No favorites', 1);
		} elseif (isset($favoriteNodes[self::FAVORITE_LIMIT])) {
			throw new \RuntimeException('Too many favorites', 2);
		}

		if ($foldersOnly) {
			/** @var Folder[] $favoriteNodes */
			return $favoriteNodes;
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
