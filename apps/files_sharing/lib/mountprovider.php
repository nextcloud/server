<?php
/**
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
use OCA\Files_Sharing\Propagation\PropagationManager;
use OCP\Files\Config\IMountProvider;
use OCP\Files\Storage\IStorageFactory;
use OCP\IConfig;
use OCP\IUser;

class MountProvider implements IMountProvider {
	/**
	 * @var \OCP\IConfig
	 */
	protected $config;

	/**
	 * @var \OCA\Files_Sharing\Propagation\PropagationManager
	 */
	protected $propagationManager;

	/**
	 * @param \OCP\IConfig $config
	 * @param \OCA\Files_Sharing\Propagation\PropagationManager $propagationManager
	 */
	public function __construct(IConfig $config, PropagationManager $propagationManager) {
		$this->config = $config;
		$this->propagationManager = $propagationManager;
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
		$propagator = $this->propagationManager->getSharePropagator($user);
		$propagator->propagateDirtyMountPoints($shares);
		$shares = array_filter($shares, function ($share) {
			return $share['permissions'] > 0;
		});
		$shares = array_map(function ($share) use ($user, $storageFactory) {
			// for updating etags for the share owner when we make changes to this share.
			$ownerPropagator = $this->propagationManager->getChangePropagator($share['uid_owner']);

			return new SharedMount(
				'\OC\Files\Storage\Shared',
				'/' . $user->getUID() . '/' . $share['file_target'],
				array(
					'propagationManager' => $this->propagationManager,
					'propagator' => $ownerPropagator,
					'share' => $share,
					'user' => $user->getUID()
				),
				$storageFactory
			);
		}, $shares);
		// array_filter removes the null values from the array
		return array_filter($shares);
	}
}
