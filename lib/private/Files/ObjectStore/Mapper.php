<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
