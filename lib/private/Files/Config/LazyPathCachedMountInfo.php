<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\Config;

use OCP\IUser;

class LazyPathCachedMountInfo extends CachedMountInfo {
	// we don't allow \ in paths so it makes a great placeholder
	private const PATH_PLACEHOLDER = '\\PLACEHOLDER\\';

	/** @var callable(CachedMountInfo): string */
	protected $rootInternalPathCallback;

	/**
	 * @param IUser $user
	 * @param int $storageId
	 * @param int $rootId
	 * @param string $mountPoint
	 * @param string $mountProvider
	 * @param int|null $mountId
	 * @param callable(CachedMountInfo): string $rootInternalPathCallback
	 * @throws \Exception
	 */
	public function __construct(
		IUser $user,
		int $storageId,
		int $rootId,
		string $mountPoint,
		string $mountProvider,
		?int $mountId,
		callable $rootInternalPathCallback,
	) {
		parent::__construct($user, $storageId, $rootId, $mountPoint, $mountProvider, $mountId, self::PATH_PLACEHOLDER);
		$this->rootInternalPathCallback = $rootInternalPathCallback;
	}

	public function getRootInternalPath(): string {
		if ($this->rootInternalPath === self::PATH_PLACEHOLDER) {
			$this->rootInternalPath = ($this->rootInternalPathCallback)($this);
		}
		return $this->rootInternalPath;
	}
}
