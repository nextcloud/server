<?php

declare(strict_types=1);

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
	 * @param ICachedMountInfo[] $mountInfo
	 * @param ICacheEntry[] $mountMetadata
	 * @return IMountPoint[]
	 */
	public function getMountsFromMountPoints(array $mountInfo, array $mountMetadata): array;
}
