<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\ObjectStore;

use OCP\IConfig;
use OCP\IUser;

/**
 * Class Mapper
 *
 * @package OC\Files\ObjectStore
 *
 * Map a user to a bucket.
 */
class Mapper {
	/** @var IUser */
	private $user;

	/** @var IConfig */
	private $config;

	/**
	 * Mapper constructor.
	 *
	 * @param IUser $user
	 * @param IConfig $config
	 */
	public function __construct(IUser $user, IConfig $config) {
		$this->user = $user;
		$this->config = $config;
	}

	/**
	 * @param int $numBuckets
	 * @return string
	 */
	public function getBucket($numBuckets = 64) {
		// Get the bucket config and shift if provided.
		// Allow us to prevent writing in old filled buckets
		$config = $this->config->getSystemValue('objectstore_multibucket');
		$minBucket = is_array($config) && isset($config['arguments']['min_bucket'])
			? (int)$config['arguments']['min_bucket']
			: 0;

		$hash = md5($this->user->getUID());
		$num = hexdec(substr($hash, 0, 4));
		return (string)(($num % ($numBuckets - $minBucket)) + $minBucket);
	}
}
