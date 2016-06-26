<?php
/**
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_Sharing;

use OC\Files\Filesystem;
use OC\User\NoUserException;
use OCP\Files\Config\IMountProvider;
use OCP\Files\Storage\IStorageFactory;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IUser;

class MountProvider implements IMountProvider {
	/**
	 * @var \OCP\IConfig
	 */
	protected $config;

	/**
	 * @var ILogger
	 */
	protected $logger;

	/**
	 * @param \OCP\IConfig $config
	 * @param ILogger $logger
	 */
	public function __construct(IConfig $config, ILogger $logger) {
		$this->config = $config;
		$this->logger = $logger;
	}


	/**
	 * Get all mountpoints applicable for the user and check for shares where we need to update the etags
	 *
	 * @param \OCP\IUser $user
	 * @param \OCP\Files\Storage\IStorageFactory $storageFactory
	 * @return \OCP\Files\Mount\IMountPoint[]
	 */
	public function getMountsForUser(IUser $user, IStorageFactory $storageFactory) {
		$shares = \OCP\Share::getItemsSharedWithUser('file', $user->getUID());
		$shares = array_filter($shares, function ($share) {
			return $share['permissions'] > 0;
		});
		$mounts = [];
		foreach ($shares as $share) {
			try {
				$mounts[] = new SharedMount(
					'\OC\Files\Storage\Shared',
					$mounts,
					[
						'share' => $share,
						'user' => $user->getUID()
					],
					$storageFactory
				);
			} catch (\Exception $e) {
				$this->logger->logException($e);
				$this->logger->error('Error while trying to create shared mount');
			}
		}

		// array_filter removes the null values from the array
		return array_filter($mounts);
	}
}
