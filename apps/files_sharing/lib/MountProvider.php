<?php
/**
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Roeland Jago Douma <rullzer@owncloud.com>
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

use OCP\Files\Config\IMountProvider;
use OCP\Files\Storage\IStorageFactory;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IUser;
use OCP\Share\IManager;

class MountProvider implements IMountProvider {
	/**
	 * @var \OCP\IConfig
	 */
	protected $config;

	/**
	 * @var IManager
	 */
	protected $shareManager;

	/**
	 * @var ILogger
	 */
	protected $logger;

	/**
	 * @param \OCP\IConfig $config
	 * @param IManager $shareManager
	 * @param ILogger $logger
	 */
	public function __construct(IConfig $config, IManager $shareManager, ILogger $logger) {
		$this->config = $config;
		$this->shareManager = $shareManager;
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
		$shares = $this->shareManager->getSharedWith($user->getUID(), \OCP\Share::SHARE_TYPE_USER, null, -1);
		$shares = array_merge($shares, $this->shareManager->getSharedWith($user->getUID(), \OCP\Share::SHARE_TYPE_GROUP, null, -1));
		// filter out excluded shares and group shares that includes self
		$shares = array_filter($shares, function (\OCP\Share\IShare $share) use ($user) {
			return $share->getPermissions() > 0 && $share->getShareOwner() !== $user->getUID();
		});

		$mounts = [];
		foreach ($shares as $share) {

			try {
				$mounts[] = new SharedMount(
					'\OC\Files\Storage\Shared',
					$mounts,
					[
						'user' => $user->getUID(),
						'newShare' => $share,
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
