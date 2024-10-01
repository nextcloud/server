<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Events;

use OCA\User_LDAP\GroupPluginManager;
use OCA\User_LDAP\IGroupLDAP;
use OCP\EventDispatcher\Event;

/**
 * This event is triggered right after the LDAP group backend is registered.
 *
 * @since 20.0.0
 */
class GroupBackendRegistered extends Event {

	/** @var GroupPluginManager */
	private $pluginManager;
	/** @var IGroupLDAP */
	private $backend;

	public function __construct(IGroupLDAP $backend, GroupPluginManager $pluginManager) {
		$this->pluginManager = $pluginManager;
		$this->backend = $backend;
	}

	public function getBackend(): IGroupLDAP {
		return $this->backend;
	}

	public function getPluginManager(): GroupPluginManager {
		return $this->pluginManager;
	}
}
