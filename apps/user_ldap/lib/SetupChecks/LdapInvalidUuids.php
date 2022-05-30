<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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

use OCA\User_LDAP\Mapping\GroupMapping;
use OCA\User_LDAP\Mapping\UserMapping;
use OCP\App\IAppManager;
use OCP\IL10N;
use OCP\IServerContainer;
use OCP\SetupCheck\ISetupCheck;

class LdapInvalidUuids implements ISetupCheck {
	private IL10N $l10n;
	private IServerContainer $server;
	private UserMapping $userMapping;
	private GroupMapping $groupMapping;

	public function __construct(IL10N $l10n, UserMapping $userMapping, GroupMapping $groupMapping) {
		$this->l10n = $l10n;
		$this->userMapping = $userMapping;
		$this->groupMapping = $groupMapping;
	}

	public function getCategory(): string {
		return 'ldap';
	}

	public function description(): string {
		return $this->l10n->t('Invalid UUIDs of LDAP users or groups have been found. Please review your "Override UUID detection" settings in the Expert part of the LDAP configuration and use "occ ldap:update-uuid" to update them.');
	}

	public function severity(): string {
		return 'warning';
	}

	public function run(): bool {
		return count($this->userMapping->getList(0, 1, true)) === 0
			&& count($this->groupMapping->getList(0, 1, true)) === 0;
	}
}
