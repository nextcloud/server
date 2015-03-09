<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Sharing\Propagation;

use OC\Files\Filesystem;
use OC\Files\View;
use OCP\IConfig;
use OCP\IUserSession;


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

	private $globalSetupDone = false;

	function __construct(IUserSession $userSession, IConfig $config) {
		$this->userSession = $userSession;
		$this->config = $config;
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
	 * @param string $user
	 * @return \OCA\Files_Sharing\Propagation\RecipientPropagator
	 */
	public function getSharePropagator($user) {
		if (isset($this->sharePropagators[$user])) {
			return $this->sharePropagators[$user];
		}
		$this->sharePropagators[$user] = new RecipientPropagator($user, $this->getChangePropagator($user), $this->config);
		return $this->sharePropagators[$user];
	}

	/**
	 * Attach the propagator to the change propagator of a user to listen to changes made to files shared by the user
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
		$watcher = new ChangeWatcher(Filesystem::getView());

		// for marking shares owned by the active user as dirty when a file inside them changes
		$this->listenToOwnerChanges($user->getUID(), $user->getUID());
		\OC_Hook::connect('OC_Filesystem', 'write', $watcher, 'writeHook');
		\OC_Hook::connect('OC_Filesystem', 'delete', $watcher, 'writeHook');
	}
}
