<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Migration;

use OCA\User_LDAP\Helper;
use OCA\User_LDAP\LDAPProviderFactory;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class SetDefaultProvider implements IRepairStep {

	public function __construct(
		private IConfig $config,
		private Helper $helper,
	) {
	}

	public function getName(): string {
		return 'Set default LDAP provider';
	}

	public function run(IOutput $output): void {
		$current = $this->config->getSystemValue('ldapProviderFactory', null);
		if ($current === null) {
			$this->config->setSystemValue('ldapProviderFactory', LDAPProviderFactory::class);
		}
	}
}
