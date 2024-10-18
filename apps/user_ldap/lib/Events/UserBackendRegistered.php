<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Events;

use OCA\User_LDAP\IUserLDAP;
use OCA\User_LDAP\UserPluginManager;
use OCP\EventDispatcher\Event;

/**
 * This event is triggered right after the LDAP user backend is registered.
 *
 * @since 20.0.0
 */
class UserBackendRegistered extends Event {

	public function __construct(
		private IUserLDAP $backend,
		private UserPluginManager $pluginManager,
	) {
	}

	public function getBackend(): IUserLDAP {
		return $this->backend;
	}

	public function getPluginManager(): UserPluginManager {
		return $this->pluginManager;
	}
}
