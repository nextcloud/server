<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Migration;

use OCA\User_LDAP\LDAPProviderFactory;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class UnsetDefaultProvider implements IRepairStep {

	public function __construct(
		private IConfig $config,
	) {
	}

	public function getName(): string {
		return 'Unset default LDAP provider';
	}

	public function run(IOutput $output): void {
		$current = $this->config->getSystemValue('ldapProviderFactory', null);
		if ($current === LDAPProviderFactory::class) {
			$this->config->deleteSystemValue('ldapProviderFactory');
		}
	}
}
