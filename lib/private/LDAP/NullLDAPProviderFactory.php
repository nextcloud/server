<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\LDAP;

use OCP\IServerContainer;
use OCP\LDAP\ILDAPProviderFactory;

class NullLDAPProviderFactory implements ILDAPProviderFactory {
	public function __construct(IServerContainer $serverContainer) {
	}

	public function getLDAPProvider() {
		throw new \Exception('No LDAP provider is available');
	}

	public function isAvailable(): bool {
		return false;
	}
}
