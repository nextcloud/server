<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_LDAP;

use OCP\LDAP\ILDAPProvider;
use OCP\LDAP\ILDAPProviderFactory;
use Psr\Container\ContainerInterface;

class LDAPProviderFactory implements ILDAPProviderFactory {
	public function __construct(
		private ContainerInterface $serverContainer,
	) {
	}

	public function getLDAPProvider(): ILDAPProvider {
		return $this->serverContainer->get(LDAPProvider::class);
	}

	public function isAvailable(): bool {
		return true;
	}
}
