<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\Node;

use OC\Files\Storage\Wrapper\Quota;
use OC\Files\View;
use OCP\Files\Folder as IFolder;
use OCP\Files\IRootFolder;
use OCP\Files\IUserFolder;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IUser;

/**
 * @since 32.0.0
 */
class UserFolder extends Folder implements IUserFolder {

	public function __construct(
		IRootFolder $root,
		View $view,
		string $path,
		IFolder $parent,
		protected IConfig $config,
		protected IUser $user,
		protected ICacheFactory $cacheFactory,
	) {
		parent::__construct($root, $view, $path, parent: $parent);
	}

	public function getUserQuota(bool $useCache = true): array {
		// return from cache if requested and we already cached it
		$memcache = $this->cacheFactory->createLocal('storage_info');
		if ($useCache) {
			$cached = $memcache->get($this->getPath());
			if ($cached) {
				return $cached;
			}
		}

		$quotaIncludeExternalStorage = $this->config->getSystemValueBool('quota_include_external_storage');
		$rootInfo = $this->getFileInfo($quotaIncludeExternalStorage);

		/** @var int|float $used */
		$used = max($rootInfo->getSize(), 0.0);
		/** @var int|float $quota */
		$quota = \OCP\Files\FileInfo::SPACE_UNLIMITED;
		$mount = $rootInfo->getMountPoint();
		$storage = $mount->getStorage();
		if ($storage === null) {
			throw new \RuntimeException('Storage returned from mount point is null.');
		}

		if ($storage->instanceOfStorage(Quota::class)) {
			$quota = $storage->getQuota();
		} elseif ($quotaIncludeExternalStorage) {
			$quota = $this->user->getQuotaBytes();
		}

		$free = $storage->free_space($rootInfo->getInternalPath());
		if (is_bool($free)) {
			$free = 0.0;
		}

		if ($free >= 0) {
			$total = $free + $used;
		} else {
			$total = $free; //either unknown or unlimited
		}

		$relative = $total > 0
			? $used / $total
			: 0;
		$this->config->setUserValue($this->user->getUID(), 'files', 'lastSeenQuotaUsage', (string)$relative);

		$info = [
			'free' => $free,
			'used' => $used,
			'quota' => $quota,
			'total' => $total,
		];
		$memcache->set($this->getPath(), $info, 5 * 60);

		return $info;
	}

}
