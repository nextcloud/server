<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_LDAP\Migration;

use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class RearrangeMarkRemnantsAsDisabled implements IRepairStep {
	public function __construct(
		protected IAppConfig $appConfig,
		protected IDBConnection $dbc,
	) {

	}
	public function getName(): string {
		return 'Rearrange the configuration of ldap_mark_remnants_as_disabled';
	}

	public function run(IOutput $output): void {
		$allKeys = $this->appConfig->getKeys('user_ldap');

		if (in_array('backend_mark_remnants_as_disabled', $allKeys, true)) {
			return;
		}

		// if it was enabled for at least one configuration, use it as global configuration
		$filteredKeys = array_filter($allKeys, static function (string $key): bool {
			return str_ends_with($key, 'ldap_mark_remnants_as_disabled');
		});
		$newValue = false;
		foreach ($filteredKeys as $filteredKey) {
			$newValue = $newValue || $this->appConfig->getValueBool('user_ldap', $filteredKey);
		}

		// set the new value
		$this->appConfig->setValueBool('user_ldap', 'backend_mark_remnants_as_disabled', $newValue);
		if ($newValue) {
			$output->info('The option "Disable missing users from LDAP" is activated.');
		}

		// clean up now that the new value is saved
		foreach ($filteredKeys as $filteredKey) {
			$this->appConfig->deleteKey('user_ldap', $filteredKey);
		}
	}
}
