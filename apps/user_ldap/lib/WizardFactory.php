<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_LDAP;

class WizardFactory {
	public function __construct(
		private ILDAPWrapper $ldap,
		private AccessFactory $accessFactory,
	) {
	}

	public function get(string $configID): Wizard {
		$configuration = new Configuration($configID);

		$connection = new Connection($this->ldap, $configID, null);
		$connection->setConfiguration($configuration->getConfiguration());
		$connection->ldapConfigurationActive = (string)true;
		$connection->setIgnoreValidation(true);

		$access = $this->accessFactory->get($connection);

		return new Wizard($configuration, $this->ldap, $access);
	}
}
