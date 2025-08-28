<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\ObjectStore;

use OCP\IUser;

/**
 * Class Mapper
 *
 * @package OC\Files\ObjectStore
 *
 * Map a user to a bucket.
 */
class Mapper {
	public function __construct(
		private readonly IUser $user,
		private readonly array $config,
	) {
	}

	public function getBucket(int $numBuckets = 64): string {
		// Get the bucket config and shift if provided.
		// Allow us to prevent writing in old filled buckets
		$minBucket = isset($this->config['arguments']['min_bucket'])
			? (int)$this->config['arguments']['min_bucket']
			: 0;

		$hash = md5($this->user->getUID());
		$num = hexdec(substr($hash, 0, 4));
		return (string)(($num % ($numBuckets - $minBucket)) + $minBucket);
	}
}
