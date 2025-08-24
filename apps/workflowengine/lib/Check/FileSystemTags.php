<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Check;

use OC\Files\Storage\Wrapper\Jail;
use OCA\Files_Sharing\SharedStorage;
use OCA\WorkflowEngine\Entity\File;
use OCP\Files\Cache\ICache;
use OCP\Files\IHomeStorage;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserSession;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagNotFoundException;
use OCP\WorkflowEngine\ICheck;
use OCP\WorkflowEngine\IFileCheck;

class FileSystemTags implements ICheck, IFileCheck {
	use TFileCheck;

	/** @var array */
	protected $fileIds;

	/** @var array */
	protected $fileSystemTags;

	public function __construct(
		protected IL10N $l,
		protected ISystemTagManager $systemTagManager,
		protected ISystemTagObjectMapper $systemTagObjectMapper,
		protected IUserSession $userSession,
		protected IGroupManager $groupManager,
	) {
	}

	/**
	 * @param string $operator
	 * @param string $value
	 * @return bool
	 */
	public function executeCheck($operator, $value) {
		$systemTags = $this->getSystemTags();
		return ($operator === 'is') === in_array($value, $systemTags);
	}

	/**
	 * @param string $operator
	 * @param string $value
	 * @throws \UnexpectedValueException
	 */
	public function validateCheck($operator, $value) {
		if (!in_array($operator, ['is', '!is'])) {
			throw new \UnexpectedValueException($this->l->t('The given operator is invalid'), 1);
		}

		try {
			$tags = $this->systemTagManager->getTagsByIds($value);

			$user = $this->userSession->getUser();
			$isAdmin = $user instanceof IUser && $this->groupManager->isAdmin($user->getUID());

			if (!$isAdmin) {
				foreach ($tags as $tag) {
					if (!$tag->isUserVisible()) {
						throw new \UnexpectedValueException($this->l->t('The given tag id is invalid'), 4);
					}
				}
			}
		} catch (TagNotFoundException $e) {
			throw new \UnexpectedValueException($this->l->t('The given tag id is invalid'), 2);
		} catch (\InvalidArgumentException $e) {
			throw new \UnexpectedValueException($this->l->t('The given tag id is invalid'), 3);
		}
	}

	/**
	 * Get the ids of the assigned system tags
	 * @return string[]
	 */
	protected function getSystemTags() {
		$cache = $this->storage->getCache();
		$fileIds = $this->getFileIds($cache, $this->path, !$this->storage->instanceOfStorage(IHomeStorage::class) || $this->storage->instanceOfStorage(SharedStorage::class));

		$systemTags = [];
		foreach ($fileIds as $i => $fileId) {
			if (isset($this->fileSystemTags[$fileId])) {
				$systemTags[] = $this->fileSystemTags[$fileId];
				unset($fileIds[$i]);
			}
		}

		if (!empty($fileIds)) {
			$mappedSystemTags = $this->systemTagObjectMapper->getTagIdsForObjects($fileIds, 'files');
			foreach ($mappedSystemTags as $fileId => $fileSystemTags) {
				$this->fileSystemTags[$fileId] = $fileSystemTags;
				$systemTags[] = $fileSystemTags;
			}
		}

		$systemTags = call_user_func_array('array_merge', $systemTags);
		$systemTags = array_unique($systemTags);
		return $systemTags;
	}

	/**
	 * Get the file ids of the given path and its parents
	 * @param ICache $cache
	 * @param string $path
	 * @param bool $isExternalStorage
	 * @return int[]
	 */
	protected function getFileIds(ICache $cache, $path, $isExternalStorage) {
		$cacheId = $cache->getNumericStorageId();
		if ($this->storage->instanceOfStorage(Jail::class)) {
			$absolutePath = $this->storage->getUnjailedPath($path);
		} else {
			$absolutePath = $path;
		}

		if (isset($this->fileIds[$cacheId][$absolutePath])) {
			return $this->fileIds[$cacheId][$absolutePath];
		}

		$parentIds = [];
		if ($path !== $this->dirname($path)) {
			$parentIds = $this->getFileIds($cache, $this->dirname($path), $isExternalStorage);
		} elseif (!$isExternalStorage) {
			return [];
		}

		$fileId = $cache->getId($path);
		if ($fileId !== -1) {
			$parentIds[] = $fileId;
		}

		$this->fileIds[$cacheId][$absolutePath] = $parentIds;

		return $parentIds;
	}

	protected function dirname($path) {
		$dir = dirname($path);
		return $dir === '.' ? '' : $dir;
	}

	public function supportedEntities(): array {
		return [ File::class ];
	}

	public function isAvailableForScope(int $scope): bool {
		return true;
	}
}
