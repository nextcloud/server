<?php
/**
 * @author Vincent Petry <pvince81@owncloud.com>
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

namespace OCA\Files_Sharing\Propagation;

use OC\Files\Filesystem;
use OC\Files\View;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\IGroup;
use OCP\IUser;
use OCP\IGroupManager;
use OCA\Files_Sharing\Propagation\PropagationManager;

/**
 * Propagate changes on group changes
 */
class GroupPropagationManager {
	/**
	 * @var \OCP\IUserSession
	 */
	private $userSession;

	/**
	 * @var \OCP\IGroupManager
	 */
	private $groupManager;

	/**
	 * @var PropagationManager
	 */
	private $propagationManager;

	/**
	 * Items shared with a given user.
	 * Key is user id and value is an array of shares.
	 *
	 * @var array
	 */
	private $userShares = [];

	public function __construct(IUserSession $userSession, IGroupManager $groupManager, PropagationManager $propagationManager) {
		$this->userSession = $userSession;
		$this->groupManager = $groupManager;
		$this->propagationManager = $propagationManager;
	}

	public function onPreProcessUser(IGroup $group, IUser $targetUser) {
		$this->userShares[$targetUser->getUID()] = $this->getUserShares($targetUser->getUID());
	}

	public function onPostAddUser(IGroup $group, IUser $targetUser) {
		$targetUserId = $targetUser->getUID();
		$sharesAfter = $this->getUserShares($targetUserId);

		$this->propagateSharesDiff($targetUserId, $sharesAfter, $this->userShares[$targetUserId]);
		unset($this->userShares[$targetUserId]);
	}

	public function onPostRemoveUser(IGroup $group, IUser $targetUser) {
		$targetUserId = $targetUser->getUID();
		$sharesAfter = $this->getUserShares($targetUserId);

		$this->propagateSharesDiff($targetUserId, $this->userShares[$targetUserId], $sharesAfter);
		unset($this->userShares[$targetUserId]);
	}

	private function getUserShares($targetUserId) {
		return \OCP\Share::getItemsSharedWithUser('file', $targetUserId);
	}

	/**
	 * Propagate etag for the shares that are in $shares1 but not in $shares2.
	 *
	 * @param string $targetUserId user id for which to propagate shares
	 * @param array $shares1
	 * @param array $shares2
	 */
	private function propagateSharesDiff($targetUserId, $shares1, $shares2) {
		$sharesToPropagate = array_udiff(
			$shares1,
			$shares2,
			function($share1, $share2) {
				return ($share2['id'] - $share1['id']);
			}
		);

		\OC\Files\Filesystem::initMountPoints($targetUserId);
		$this->propagationManager->propagateSharesToUser($sharesToPropagate, $targetUserId);
	}

	/**
	 * To be called from setupFS trough a hook
	 *
	 * Sets up listening to changes made to shares owned by the current user
	 */
	public function globalSetup() {
		$user = $this->userSession->getUser();
		if (!$user) {
			return;
		}

		$this->groupManager->listen('\OC\Group', 'preAddUser', [$this, 'onPreProcessUser']);
		$this->groupManager->listen('\OC\Group', 'postAddUser', [$this, 'onPostAddUser']);
		$this->groupManager->listen('\OC\Group', 'preRemoveUser', [$this, 'onPreProcessUser']);
		$this->groupManager->listen('\OC\Group', 'postRemoveUser', [$this, 'onPostRemoveUser']);
	}

	public function tearDown() {
		$this->groupManager->removeListener('\OC\Group', 'preAddUser', [$this, 'onPreProcessUser']);
		$this->groupManager->removeListener('\OC\Group', 'postAddUser', [$this, 'onPostAddUser']);
		$this->groupManager->removeListener('\OC\Group', 'preRemoveUser', [$this, 'onPreProcessUser']);
		$this->groupManager->removeListener('\OC\Group', 'postRemoveUser', [$this, 'onPostRemoveUser']);
	}
}
