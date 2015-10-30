<?php
/**
 * @author Roeland Jago Douma <rullzer@owncloud.com>
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
namespace OC\Share20;


use OCP\IAppConfig;
use OCP\IUserManager;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\ILogger;
use OCP\Files\Folder;

use OC\Share20\Exceptions\ShareNotFoundException;
use OC\Share20\Exception\PreconditionFailed;

/**
 * This class is the communication hub for all sharing related operations.
 */
class Manager {

	/**
	 * @var IShareProvider[]
	 */
	private $shareProviders;

	/**
	 * @var string[]
	 */
	private $shareTypeToProviderId;

	/** @var IUser */
	private $currentUser;

	/** @var IUserManager */
	private $userManager;

	/** @var IGroupManager */
	private $groupManager;

	/** @var ILogger */
	private $logger;

	/** @var IAppConfig */
	private $appConfig;

	/** @var IFolder */
	private $userFolder;

	public function __construct(IUser $user,
								IUserManager $userManager,
								IGroupManager $groupManager,
								ILogger $logger,
								IAppConfig $appConfig,
								Folder $userFolder,
								IShareProvider $defaultProvider) {
		$this->currentUser = $user;
		$this->userManager = $userManager;
		$this->groupManager = $groupManager;
		$this->logger = $logger;
		$this->appConfig = $appConfig;
		$this->userFolder = $userFolder;

		// TEMP SOLUTION JUST TO GET STARTED
		$this->shareProviders['ocdef'] = $defaultProvider;
		$this->shareTypeToProviderId = [
			\OCP\Share::SHARE_TYPE_USER  => 'ocdef',
			\OCP\Share::SHARE_TYPE_GROUP => 'ocdef',
			\OCP\Share::SHARE_TYPE_LINK  => 'ocdef',
		];

		// TODO: Get storage share provider from primary storage
	}

	/**
	 * Get a ShareProvider
	 *
	 * @param string $id
	 * @return IShareProvider
	 */
	private function getShareProvider($id) {
		if (!isset($this->shareProviders[$id])) {
			//Throw exception;
		}

		// Check if we have instanciated this provider yet
		if (!($this->shareProviders[$id] instanceOf \OC\Share20\IShareProvider)) {
			throw new \Exception();
		}

		return $this->shareProviders[$id];
	}

	/**
	 * Get shareProvider based on shareType
	 *
	 * @param int $shareType
	 * @return IShareProvider
	 */
	private function getShareProviderByType($shareType) {
		if (!isset($this->shareTypeToProviderId[$shareType])) {
			//Throw exception
		}

		return $this->getShareProvider($this->shareTypeToProviderId[$shareType]);
	}

	/**
	 * Share a path
	 * 
	 * @param Share $share
	 * @return Share The share object
	 */
	public function createShare(Share $share) {
		throw new \Exception();
	}

	/**
	 * Update a share
	 *
	 * @param Share $share
	 * @return Share The share object
	 */
	public function updateShare(Share $share) {
		throw new \Exception();
	}

	/**
	 * Delete a share
	 *
	 * @param Share $share
	 */
	public function deleteShare(Share $share) {
		throw new \Exception();
	}

	/**
	 * Retrieve all shares by the current user
	 *
	 * @param int $page
	 * @param int $perPage
	 * @return Share[]
	 */
	public function getShares($page=0, $perPage=50) {
		throw new \Exception();
	}

	/**
	 * Retrieve a share by the share id
	 *
	 * @param string $id
	 * @return Share
	 *
	 * @throws ShareNotFoundException
	 */
	public function getShareById($id) {
		throw new \Exception();
	}

	/**
	 * Get all the shares for a given path
	 *
	 * @param \OCP\Files\Node $path
	 * @param int $page
	 * @param int $perPage
	 *
	 * @return Share[]
	 */
	public function getSharesByPath(\OCP\Files\Node $path, $page=0, $perPage=50) {
		throw new \Exception();
	}

	/**
	 * Get all shares that are shared with the current user
	 *
	 * @param int $shareType
	 * @param int $page
	 * @param int $perPage
	 *
	 * @return Share[]
	 */
	public function getSharedWithMe($shareType = null, $page=0, $perPage=50) {
		throw new \Exception();
	}

	/**
	 * Get the share by token possible with password
	 *
	 * @param string $token
	 * @param string $password
	 *
	 * @return Share
	 *
	 * @throws ShareNotFoundException
	 */
	public function getShareByToken($token, $password=null) {
		throw new \Exception();
	}

	/**
	 * Get access list to a path. This means
	 * all the users and groups that can access a given path.
	 *
	 * Consider:
	 * -root
	 * |-folder1
	 *  |-folder2
	 *   |-fileA
	 *
	 * fileA is shared with user1
	 * folder2 is shared with group2
	 * folder1 is shared with user2
	 *
	 * Then the access list will to '/folder1/folder2/fileA' is:
	 * [
	 * 	'users' => ['user1', 'user2'],
	 *  'groups' => ['group2']
	 * ]
	 *
	 * This is required for encryption
	 *
	 * @param \OCP\Files\Node $path
	 */
	public function getAccessList(\OCP\Files\Node $path) {
		throw new \Exception();
	}
}
