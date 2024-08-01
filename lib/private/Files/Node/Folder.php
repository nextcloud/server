<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @copyright Copyright (c) 2022 Informatyka Boguslawski sp. z o.o. sp.k., http://www.ib.pl/
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OC\Files\Node;

use OC\Files\Cache\QuerySearchHelper;
use OC\Files\Search\SearchBinaryOperator;
use OC\Files\Search\SearchComparison;
use OC\Files\Search\SearchOrder;
use OC\Files\Search\SearchQuery;
use OC\Files\Utils\PathHelper;
use OC\User\LazyUser;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\FileInfo;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Node as INode;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOperator;
use OCP\Files\Search\ISearchOrder;
use OCP\Files\Search\ISearchQuery;
use OCP\IUserManager;

class Folder extends Node implements \OCP\Files\Folder {

	private ?IUserManager $userManager = null;

	/**
	 * Creates a Folder that represents a non-existing path
	 *
	 * @param string $path path
	 * @return NonExistingFolder non-existing node
	 */
	protected function createNonExistingNode($path) {
		return new NonExistingFolder($this->root, $this->view, $path);
	}

	/**
	 * @param string $path path relative to the folder
	 * @return string
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function getFullPath($path) {
		$path = $this->normalizePath($path);
		if (!$this->isValidPath($path)) {
			throw new NotPermittedException('Invalid path "' . $path . '"');
		}
		return $this->path . $path;
	}

	/**
	 * @param string $path
	 * @return string|null
	 */
	public function getRelativePath($path) {
		return PathHelper::getRelativePath($this->getPath(), $path);
	}

	/**
	 * check if a node is a (grand-)child of the folder
	 *
	 * @param \OC\Files\Node\Node $node
	 * @return bool
	 */
	public function isSubNode($node) {
		return str_starts_with($node->getPath(), $this->path . '/');
	}

	/**
	 * get the content of this directory
	 *
	 * @return Node[]
	 * @throws \OCP\Files\NotFoundException
	 */
	public function getDirectoryListing() {
		$folderContent = $this->view->getDirectoryContent($this->path, '', $this->getFileInfo(false));

		return array_map(function (FileInfo $info) {
			if ($info->getMimetype() === FileInfo::MIMETYPE_FOLDER) {
				return new Folder($this->root, $this->view, $info->getPath(), $info, $this);
			} else {
				return new File($this->root, $this->view, $info->getPath(), $info, $this);
			}
		}, $folderContent);
	}

	protected function createNode(string $path, ?FileInfo $info = null, bool $infoHasSubMountsIncluded = true): INode {
		if (is_null($info)) {
			$isDir = $this->view->is_dir($path);
		} else {
			$isDir = $info->getType() === FileInfo::TYPE_FOLDER;
		}
		$parent = dirname($path) === $this->getPath() ? $this : null;
		if ($isDir) {
			return new Folder($this->root, $this->view, $path, $info, $parent, $infoHasSubMountsIncluded);
		} else {
			return new File($this->root, $this->view, $path, $info, $parent);
		}
	}

	/**
	 * Get the node at $path
	 *
	 * @param string $path
	 * @return \OC\Files\Node\Node
	 * @throws \OCP\Files\NotFoundException
	 */
	public function get($path) {
		return $this->root->get($this->getFullPath($path));
	}

	/**
	 * @param string $path
	 * @return bool
	 */
	public function nodeExists($path) {
		try {
			$this->get($path);
			return true;
		} catch (NotFoundException $e) {
			return false;
		}
	}

	/**
	 * @param string $path
	 * @return \OC\Files\Node\Folder
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function newFolder($path) {
		if ($this->checkPermissions(\OCP\Constants::PERMISSION_CREATE)) {
			$fullPath = $this->getFullPath($path);
			$nonExisting = new NonExistingFolder($this->root, $this->view, $fullPath);
			$this->sendHooks(['preWrite', 'preCreate'], [$nonExisting]);
			if (!$this->view->mkdir($fullPath) && !$this->view->is_dir($fullPath)) {
				throw new NotPermittedException('Could not create folder "' . $fullPath . '"');
			}
			$parent = dirname($fullPath) === $this->getPath() ? $this : null;
			$node = new Folder($this->root, $this->view, $fullPath, null, $parent);
			$this->sendHooks(['postWrite', 'postCreate'], [$node]);
			return $node;
		} else {
			throw new NotPermittedException('No create permission for folder "' . $path . '"');
		}
	}

	/**
	 * @param string $path
	 * @param string | resource | null $content
	 * @return \OC\Files\Node\File
	 * @throws \OCP\Files\NotPermittedException
	 */
	public function newFile($path, $content = null) {
		if ($path === '') {
			throw new NotPermittedException('Could not create as provided path is empty');
		}
		if ($this->checkPermissions(\OCP\Constants::PERMISSION_CREATE)) {
			$fullPath = $this->getFullPath($path);
			$nonExisting = new NonExistingFile($this->root, $this->view, $fullPath);
			$this->sendHooks(['preWrite', 'preCreate'], [$nonExisting]);
			if ($content !== null) {
				$result = $this->view->file_put_contents($fullPath, $content);
			} else {
				$result = $this->view->touch($fullPath);
			}
			if ($result === false) {
				throw new NotPermittedException('Could not create path "' . $fullPath . '"');
			}
			$node = new File($this->root, $this->view, $fullPath, null, $this);
			$this->sendHooks(['postWrite', 'postCreate'], [$node]);
			return $node;
		}
		throw new NotPermittedException('No create permission for path "' . $path . '"');
	}

	private function queryFromOperator(ISearchOperator $operator, string $uid = null, int $limit = 0, int $offset = 0): ISearchQuery {
		if ($uid === null) {
			$user = null;
		} else {
			/** @var IUserManager $userManager */
			$userManager = \OCP\Server::get(IUserManager::class);
			$user = $userManager->get($uid);
		}
		return new SearchQuery($operator, $limit, $offset, [], $user);
	}

	/**
	 * search for files with the name matching $query
	 *
	 * @param string|ISearchQuery $query
	 * @return \OC\Files\Node\Node[]
	 */
	public function search($query) {
		if (is_string($query)) {
			$query = $this->queryFromOperator(new SearchComparison(ISearchComparison::COMPARE_LIKE, 'name', '%' . $query . '%'));
		}

		// search is handled by a single query covering all caches that this folder contains
		// this is done by collect

		$limitToHome = $query->limitToHome();
		if ($limitToHome && count(explode('/', $this->path)) !== 3) {
			throw new \InvalidArgumentException('searching by owner is only allowed in the users home folder');
		}

		/** @var QuerySearchHelper $searchHelper */
		$searchHelper = \OC::$server->get(QuerySearchHelper::class);
		[$caches, $mountByMountPoint] = $searchHelper->getCachesAndMountPointsForSearch($this->root, $this->path, $limitToHome);
		$resultsPerCache = $searchHelper->searchInCaches($query, $caches);

		// loop through all results per-cache, constructing the FileInfo object from the CacheEntry and merge them all
		$files = array_merge(...array_map(function (array $results, string $relativeMountPoint) use ($mountByMountPoint) {
			$mount = $mountByMountPoint[$relativeMountPoint];
			return array_map(function (ICacheEntry $result) use ($relativeMountPoint, $mount) {
				return $this->cacheEntryToFileInfo($mount, $relativeMountPoint, $result);
			}, $results);
		}, array_values($resultsPerCache), array_keys($resultsPerCache)));

		// don't include this folder in the results
		$files = array_filter($files, function (FileInfo $file) {
			return $file->getPath() !== $this->getPath();
		});

		// since results were returned per-cache, they are no longer fully sorted
		$order = $query->getOrder();
		if ($order) {
			usort($files, function (FileInfo $a, FileInfo $b) use ($order) {
				foreach ($order as $orderField) {
					$cmp = $orderField->sortFileInfo($a, $b);
					if ($cmp !== 0) {
						return $cmp;
					}
				}
				return 0;
			});
		}

		return array_map(function (FileInfo $file) {
			return $this->createNode($file->getPath(), $file);
		}, $files);
	}

	private function cacheEntryToFileInfo(IMountPoint $mount, string $appendRoot, ICacheEntry $cacheEntry): FileInfo {
		$cacheEntry['internalPath'] = $cacheEntry['path'];
		$cacheEntry['path'] = rtrim($appendRoot . $cacheEntry->getPath(), '/');
		$subPath = $cacheEntry['path'] !== '' ? '/' . $cacheEntry['path'] : '';
		$storage = $mount->getStorage();

		$owner = null;
		$ownerId = $storage->getOwner($cacheEntry['internalPath']);
		if (!empty($ownerId)) {
			// Cache the user manager (for performance)
			if ($this->userManager === null) {
				$this->userManager = \OCP\Server::get(IUserManager::class);
			}
			$owner = new LazyUser($ownerId, $this->userManager);
		}

		return new \OC\Files\FileInfo(
			$this->path . $subPath,
			$storage,
			$cacheEntry['internalPath'],
			$cacheEntry,
			$mount,
			$owner,
		);
	}

	/**
	 * search for files by mimetype
	 *
	 * @param string $mimetype
	 * @return Node[]
	 */
	public function searchByMime($mimetype) {
		if (!str_contains($mimetype, '/')) {
			$query = $this->queryFromOperator(new SearchComparison(ISearchComparison::COMPARE_LIKE, 'mimetype', $mimetype . '/%'));
		} else {
			$query = $this->queryFromOperator(new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'mimetype', $mimetype));
		}
		return $this->search($query);
	}

	/**
	 * search for files by tag
	 *
	 * @param string|int $tag name or tag id
	 * @param string $userId owner of the tags
	 * @return Node[]
	 */
	public function searchByTag($tag, $userId) {
		$query = $this->queryFromOperator(new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'tagname', $tag), $userId);
		return $this->search($query);
	}

	public function searchBySystemTag(string $tagName, string $userId, int $limit = 0, int $offset = 0): array {
		$query = $this->queryFromOperator(new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'systemtag', $tagName), $userId, $limit, $offset);
		return $this->search($query);
	}

	/**
	 * @param int $id
	 * @return \OC\Files\Node\Node[]
	 */
	public function getById($id) {
		return $this->root->getByIdInPath((int)$id, $this->getPath());
	}

	protected function getAppDataDirectoryName(): string {
		$instanceId = \OC::$server->getConfig()->getSystemValueString('instanceid');
		return 'appdata_' . $instanceId;
	}

	/**
	 * In case the path we are currently in is inside the appdata_* folder,
	 * the original getById method does not work, because it can only look inside
	 * the user's mount points. But the user has no mount point for the root storage.
	 *
	 * So in that case we directly check the mount of the root if it contains
	 * the id. If it does we check if the path is inside the path we are working
	 * in.
	 *
	 * @param int $id
	 * @return array
	 */
	protected function getByIdInRootMount(int $id): array {
		if (!method_exists($this->root, 'createNode')) {
			// Always expected to be false. Being a method of Folder, this is
			// always implemented. For it is an internal method and should not
			// be exposed and made public, it is not part of an interface.
			return [];
		}
		$mount = $this->root->getMount('');
		$storage = $mount->getStorage();
		$cacheEntry = $storage?->getCache($this->path)->get($id);
		if (!$cacheEntry) {
			return [];
		}

		$absolutePath = '/' . ltrim($cacheEntry->getPath(), '/');
		$currentPath = rtrim($this->path, '/') . '/';

		if (!str_starts_with($absolutePath, $currentPath)) {
			return [];
		}

		return [$this->root->createNode(
			$absolutePath, new \OC\Files\FileInfo(
				$absolutePath,
				$storage,
				$cacheEntry->getPath(),
				$cacheEntry,
				$mount
			))];
	}

	public function getFreeSpace() {
		return $this->view->free_space($this->path);
	}

	public function delete() {
		if ($this->checkPermissions(\OCP\Constants::PERMISSION_DELETE)) {
			$this->sendHooks(['preDelete']);
			$fileInfo = $this->getFileInfo();
			$this->view->rmdir($this->path);
			$nonExisting = new NonExistingFolder($this->root, $this->view, $this->path, $fileInfo);
			$this->sendHooks(['postDelete'], [$nonExisting]);
		} else {
			throw new NotPermittedException('No delete permission for path "' . $this->path . '"');
		}
	}

	/**
	 * Add a suffix to the name in case the file exists
	 *
	 * @param string $name
	 * @return string
	 * @throws NotPermittedException
	 */
	public function getNonExistingName($name) {
		$uniqueName = \OC_Helper::buildNotExistingFileNameForView($this->getPath(), $name, $this->view);
		return trim($this->getRelativePath($uniqueName), '/');
	}

	/**
	 * @param int $limit
	 * @param int $offset
	 * @return INode[]
	 */
	public function getRecent($limit, $offset = 0) {
		$filterOutNonEmptyFolder = new SearchBinaryOperator(
			// filter out non empty folders
			ISearchBinaryOperator::OPERATOR_OR,
			[
				new SearchBinaryOperator(
					ISearchBinaryOperator::OPERATOR_NOT,
					[
						new SearchComparison(
							ISearchComparison::COMPARE_EQUAL,
							'mimetype',
							FileInfo::MIMETYPE_FOLDER
						),
					]
				),
				new SearchComparison(
					ISearchComparison::COMPARE_EQUAL,
					'size',
					0
				),
			]
		);

		$filterNonRecentFiles = new SearchComparison(
			ISearchComparison::COMPARE_GREATER_THAN,
			'mtime',
			strtotime("-2 week")
		);
		if ($offset === 0 && $limit <= 100) {
			$query = new SearchQuery(
				new SearchBinaryOperator(
					ISearchBinaryOperator::OPERATOR_AND,
					[
						$filterOutNonEmptyFolder,
						$filterNonRecentFiles,
					],
				),
				$limit,
				$offset,
				[
					new SearchOrder(
						ISearchOrder::DIRECTION_DESCENDING,
						'mtime'
					),
				]
			);
		} else {
			$query = new SearchQuery(
				$filterOutNonEmptyFolder,
				$limit,
				$offset,
				[
					new SearchOrder(
						ISearchOrder::DIRECTION_DESCENDING,
						'mtime'
					),
				]
			);
		}

		return $this->search($query);
	}
}
