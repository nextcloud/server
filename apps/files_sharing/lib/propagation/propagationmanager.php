<?php
/**
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

namespace OCA\Files_Sharing\Propagation;

use OC\Files\Filesystem;
use OC\Files\View;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Util;


/**
 * Keep track of all change and share propagators by owner
 */
class PropagationManager {
	/**
	 * @var \OCP\IUserSession
	 */
	private $userSession;

	/**
	 * @var \OCP\IConfig
	 */
	private $config;

	/**
	 * Change propagators for share owner
	 *
	 * @var \OC\Files\Cache\ChangePropagator[]
	 */
	private $changePropagators = [];

	/**
	 * Recipient propagators
	 *
	 * @var \OCA\Files_Sharing\Propagation\RecipientPropagator[]
	 */
	private $sharePropagators = [];

	/**
	 * @var IGroupManager
	 */
	private $groupManager;

	/**
	 * @var IUserManager
	 */
	private $userManager;

	public function __construct(IUserSession $userSession, IConfig $config, IGroupManager $groupManager, IUserManager $userManager) {
		$this->userSession = $userSession;
		$this->config = $config;
		$this->groupManager = $groupManager;
		$this->userManager = $userManager;
	}

	/**
	 * @param string $user
	 * @return \OC\Files\Cache\ChangePropagator
	 */
	public function getChangePropagator($user) {
		$activeUser = $this->userSession->getUser();

		// for the local user we want to propagator from the active view, not any cached one
		if ($activeUser && $activeUser->getUID() === $user && Filesystem::getView() instanceof View) {
			// it's important that we take the existing propagator here to make sure we can listen to external changes
			$this->changePropagators[$user] = Filesystem::getView()->getUpdater()->getPropagator();
		}
		if (isset($this->changePropagators[$user])) {
			return $this->changePropagators[$user];
		}
		$view = new View('/' . $user . '/files');
		$this->changePropagators[$user] = $view->getUpdater()->getPropagator();
		return $this->changePropagators[$user];
	}

	/**
	 * Propagates etag changes for the given shares to the given user
	 *
	 * @param array array of shares for which to trigger etag change
	 * @param string $user
	 */
	public function propagateSharesToUser($shares, $user) {
		$changePropagator = $this->getChangePropagator($user);
		foreach ($shares as $share) {
			$changePropagator->addChange($share['file_target']);
		}
		$time = microtime(true);
		$changePropagator->propagateChanges(floor($time));
	}

	/**
	 * @param IUser|string $user
	 * @return \OCA\Files_Sharing\Propagation\RecipientPropagator
	 */
	public function getSharePropagator($user) {
		if (!$user instanceof IUser) {
			$user = $this->userManager->get($user);
		}
		if (isset($this->sharePropagators[$user->getUID()])) {
			return $this->sharePropagators[$user->getUID()];
		}
		$this->sharePropagators[$user->getUID()] = new RecipientPropagator($user, $this->getChangePropagator($user->getUID()), $this->config, $this, $this->groupManager);
		return $this->sharePropagators[$user->getUID()];
	}

	/**
	 * Attach the recipient propagator for $user to the change propagator of a share owner to mark shares as dirty when the owner makes a change to a share
	 *
	 * @param string $shareOwner
	 * @param string $user
	 */
	public function listenToOwnerChanges($shareOwner, $user) {
		$sharePropagator = $this->getSharePropagator($user);
		$ownerPropagator = $this->getChangePropagator($shareOwner);
		$sharePropagator->attachToPropagator($ownerPropagator, $shareOwner);
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
		$recipientPropagator = $this->getSharePropagator($user);
		$watcher = new ChangeWatcher(Filesystem::getView(), $recipientPropagator);

		// for marking shares owned by the active user as dirty when a file inside them changes
		$this->listenToOwnerChanges($user->getUID(), $user->getUID());
		Util::connectHook('OC_Filesystem', 'post_write', $watcher, 'writeHook');
		Util::connectHook('OC_Filesystem', 'post_delete', $watcher, 'writeHook');
		Util::connectHook('OC_Filesystem', 'post_rename', $watcher, 'renameHook');
		Util::connectHook('OCP\Share', 'post_update_permissions', $watcher, 'permissionsHook');
	}
}
