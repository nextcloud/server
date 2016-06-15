<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vincent Petry <pvince81@owncloud.com>
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

		$groupedShares = $this->groupShares($shares);

		$superShares = $this->superShares($groupedShares);


		$mounts = [];
		foreach ($superShares as $share) {
			try {
				$mounts[] = new SharedMount(
					'\OC\Files\Storage\Shared',
					$mounts,
					[
						'user' => $user->getUID(),
						'superShare' => $share[0],
						'groupedShares' => $share[1],
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

	/**
	 * @param \OCP\Share\IShare[] $shares
	 * @return \OCP\Share\IShare[]
	 */
	private function groupShares(array $shares) {
		$tmp = [];

		foreach ($shares as $share) {
			if (!isset($tmp[$share->getNodeId()])) {
				$tmp[$share->getNodeId()] = [];
			}
			$tmp[$share->getNodeId()][$share->getTarget()][] = $share;
		}

		$result = [];
		foreach ($tmp as $tmp2) {
			foreach ($tmp2 as $item) {
				$result[] = $item;
			}
		}

		return $result;
	}

	/**
	 * Extract super shares
	 *
	 * @param array $shares Array of \OCP\Share\IShare[]
	 * @return array Tuple of [superShare, groupedShares]
	 */
	private function superShares(array $groupedShares) {
		$result = [];

		/** @var \OCP\Share\IShare[] $shares */
		foreach ($groupedShares as $shares) {
			if (count($shares) === 0) {
				continue;
			}

			$superShare = $this->shareManager->newShare();

			$superShare->setId($shares[0]->getId())
				->setShareOwner($shares[0]->getShareOwner())
				->setNodeId($shares[0]->getNodeId())
				->setTarget($shares[0]->getTarget());

			$permissions = 0;
			foreach ($shares as $share) {
				$permissions |= $share->getPermissions();
			}

			$superShare->setPermissions($permissions);

			$result[] = [$superShare, $shares];
		}

		return $result;
	}
}
