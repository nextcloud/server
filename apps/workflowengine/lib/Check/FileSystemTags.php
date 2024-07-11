<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Richard Steinmetz <richard@steinmetz.cloud>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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

	/** @var IL10N */
	protected $l;

	/** @var ISystemTagManager */
	protected $systemTagManager;

	/** @var ISystemTagObjectMapper */
	protected $systemTagObjectMapper;
	/** @var IUserSession */
	protected $userSession;
	/** @var IGroupManager */
	protected $groupManager;

	public function __construct(
		IL10N $l,
		ISystemTagManager $systemTagManager,
		ISystemTagObjectMapper $systemTagObjectMapper,
		IUserSession $userSession,
		IGroupManager $groupManager
	) {
		$this->l = $l;
		$this->systemTagManager = $systemTagManager;
		$this->systemTagObjectMapper = $systemTagObjectMapper;
		$this->userSession = $userSession;
		$this->groupManager = $groupManager;
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
