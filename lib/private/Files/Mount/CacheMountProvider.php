<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Mount;

use OCP\Files\Config\IMountProvider;
use OCP\Files\Storage\IStorageFactory;
use OCP\IConfig;
use OCP\IUser;
use Override;

/**
 * Mount provider for custom cache storages
 */
class CacheMountProvider implements IMountProvider {
	public function __construct(
		private readonly IConfig $config,
	) {
	}

	#[Override]
	public function getMountsForUser(IUser $user, IStorageFactory $loader): array {
		$cacheBaseDir = $this->config->getSystemValueString('cache_path', '');
		if ($cacheBaseDir !== '') {
			$cacheDir = rtrim($cacheBaseDir, '/') . '/' . $user->getUID();
			if (!file_exists($cacheDir)) {
				mkdir($cacheDir, 0770, true);
				mkdir($cacheDir . '/uploads', 0770, true);
			}

			return [
				new MountPoint('\OC\Files\Storage\Local', '/' . $user->getUID() . '/cache', ['datadir' => $cacheDir], $loader, null, null, self::class),
				new MountPoint('\OC\Files\Storage\Local', '/' . $user->getUID() . '/uploads', ['datadir' => $cacheDir . '/uploads'], $loader, null, null, self::class)
			];
		} else {
			return [];
		}
	}
}
