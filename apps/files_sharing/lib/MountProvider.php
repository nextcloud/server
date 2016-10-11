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

		$superShares = $this->buildSuperShares($shares, $user);

		$mounts = [];
		foreach ($superShares as $share) {
			try {
				$mounts[] = new SharedMount(
					'\OC\Files\Storage\Shared',
					$mounts,
					[
						'user' => $user->getUID(),
						// parent share
						'superShare' => $share[0],
						// children/component of the superShare
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
	 * Groups shares by path (nodeId) and target path
	 *
	 * @param \OCP\Share\IShare[] $shares
	 * @return \OCP\Share\IShare[][] array of grouped shares, each element in the
	 * array is a group which itself is an array of shares
	 */
	private function groupShares(array $shares) {
		$tmp = [];

		foreach ($shares as $share) {
			if (!isset($tmp[$share->getNodeId()])) {
				$tmp[$share->getNodeId()] = [];
			}
			$tmp[$share->getNodeId()][] = $share;
		}

		$result = [];
		// sort by stime, the super share will be based on the least recent share
		foreach ($tmp as &$tmp2) {
			@usort($tmp2, function($a, $b) {
				if ($a->getShareTime() < $b->getShareTime()) {
					return -1;
				}
				return 1;
			});
			$result[] = $tmp2;
		}

		return array_values($result);
	}

	/**
	 * Build super shares (virtual share) by grouping them by node id and target,
	 * then for each group compute the super share and return it along with the matching
	 * grouped shares. The most permissive permissions are used based on the permissions
	 * of all shares within the group.
	 *
	 * @param \OCP\Share\IShare[] $allShares
	 * @param \OCP\IUser $user user
	 * @return array Tuple of [superShare, groupedShares]
	 */
	private function buildSuperShares(array $allShares, \OCP\IUser $user) {
		$result = [];

		$groupedShares = $this->groupShares($allShares);

		/** @var \OCP\Share\IShare[] $shares */
		foreach ($groupedShares as $shares) {
			if (count($shares) === 0) {
				continue;
			}

			$superShare = $this->shareManager->newShare();

			// compute super share based on first entry of the group
			$superShare->setId($shares[0]->getId())
				->setShareOwner($shares[0]->getShareOwner())
				->setNodeId($shares[0]->getNodeId())
				->setTarget($shares[0]->getTarget());

			// use most permissive permissions
			$permissions = 0;
			foreach ($shares as $share) {
				$permissions |= $share->getPermissions();
				if ($share->getTarget() !== $superShare->getTarget()) {
					// adjust target, for database consistency
					$share->setTarget($superShare->getTarget());
					$this->shareManager->moveShare($share, $user->getUID());
				}
			}

			$superShare->setPermissions($permissions);

			$result[] = [$superShare, $shares];
		}

		return $result;
	}
}
