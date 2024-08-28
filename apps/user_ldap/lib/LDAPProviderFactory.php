<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP;

use OCP\IServerContainer;
use OCP\LDAP\ILDAPProvider;
use OCP\LDAP\ILDAPProviderFactory;

class LDAPProviderFactory implements ILDAPProviderFactory {
	/** * @var IServerContainer */
	private $serverContainer;

	public function __construct(IServerContainer $serverContainer) {
		$this->serverContainer = $serverContainer;
	}

	public function getLDAPProvider(): ILDAPProvider {
		return $this->serverContainer->get(LDAPProvider::class);
	}

	public function isAvailable(): bool {
		return true;
	}
}
