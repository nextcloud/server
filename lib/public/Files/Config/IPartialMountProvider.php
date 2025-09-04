<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\Config;

use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Mount\IMountPoint;

/**
 * This interface represents mounts that can return IMountPoint instances that
 * are related to the provided mount information and metadata.
 */
interface IPartialMountProvider extends IMountProvider {

	/**
	 * todo: $mountInfo may need to be an array of paths (string[])
	 * @param ICachedMountInfo[] $mountsInfo
	 * @param ICacheEntry[] $mountsMetadata
	 * @return IMountPoint[]
	 */
	public function getMountsFromMountPoints(array $mountsInfo, array $mountsMetadata): array;
}
