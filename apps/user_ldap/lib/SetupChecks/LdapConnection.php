<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace OCA\User_LDAP\SetupChecks;

use OCA\User_LDAP\AccessFactory;
use OCA\User_LDAP\ConnectionFactory;
use OCA\User_LDAP\Helper;
use OCP\IL10N;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class LdapConnection implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private Helper $helper,
		private ConnectionFactory $connectionFactory,
		private AccessFactory $accessFactory,
	) {
	}

	public function getCategory(): string {
		return 'ldap';
	}

	public function getName(): string {
		return $this->l10n->t('LDAP Connection');
	}

	public function run(): SetupResult {
		$availableConfigs = $this->helper->getServerConfigurationPrefixes();
		$inactiveConfigurations = [];
		$bindFailedConfigurations = [];
		$searchFailedConfigurations = [];
		foreach ($availableConfigs as $configID) {
			$connection = $this->connectionFactory->get($configID);
			if (!$connection->ldapConfigurationActive) {
				$inactiveConfigurations[] = $configID;
				continue;
			}
			if (!$connection->bind()) {
				$bindFailedConfigurations[] = $configID;
				continue;
			}
			$access = $this->accessFactory->get($connection);
			$result = $access->countObjects(1);
			if (!is_int($result) || ($result <= 0)) {
				$searchFailedConfigurations[] = $configID;
			}
		}
		$output = '';
		if (!empty($bindFailedConfigurations)) {
			$output .= $this->l10n->n(
				'Binding failed for this LDAP configuration: %s',
				'Binding failed for these LDAP configurations: %s',
				count($bindFailedConfigurations),
				[implode(',', $bindFailedConfigurations)]
			)."\n";
		}
		if (!empty($searchFailedConfigurations)) {
			$output .= $this->l10n->n(
				'Searching failed for this LDAP configuration: %s',
				'Searching failed for these LDAP configurations: %s',
				count($searchFailedConfigurations),
				[implode(',', $searchFailedConfigurations)]
			)."\n";
		}
		if (!empty($inactiveConfigurations)) {
			$output .= $this->l10n->n(
				'There is an inactive LDAP configuration: %s',
				'There are inactive LDAP configurations: %s',
				count($inactiveConfigurations),
				[implode(',', $inactiveConfigurations)]
			)."\n";
		}
		if (!empty($bindFailedConfigurations) || !empty($searchFailedConfigurations)) {
			return SetupResult::error($output);
		} elseif (!empty($inactiveConfigurations)) {
			return SetupResult::warning($output);
		}
		return SetupResult::success($this->l10n->n(
			'Binding and searching works on the configured LDAP connection (%s)',
			'Binding and searching works on all of the configured LDAP connections (%s)',
			count($availableConfigs),
			[implode(',', $availableConfigs)]
		));
	}
}
