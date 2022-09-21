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

namespace OCA\Settings\SetupChecks;

use OCA\User_LDAP\Mapping\GroupMapping;
use OCA\User_LDAP\Mapping\UserMapping;
use OCP\App\IAppManager;
use OCP\IL10N;
use OCP\IServerContainer;

class LdapInvalidUuids {

	/** @var IAppManager */
	private $appManager;
	/** @var IL10N */
	private $l10n;
	/** @var IServerContainer */
	private $server;

	public function __construct(IAppManager $appManager, IL10N $l10n, IServerContainer $server) {
		$this->appManager = $appManager;
		$this->l10n = $l10n;
		$this->server = $server;
	}

	public function description(): string {
		return $this->l10n->t('Invalid UUIDs of LDAP accounts or groups have been found. Please review your "Override UUID detection" settings in the Expert part of the LDAP configuration and use "occ ldap:update-uuid" to update them.');
	}

	public function severity(): string {
		return 'warning';
	}

	public function run(): bool {
		if (!$this->appManager->isEnabledForUser('user_ldap')) {
			return true;
		}
		/** @var UserMapping $userMapping */
		$userMapping = $this->server->get(UserMapping::class);
		/** @var GroupMapping $groupMapping */
		$groupMapping = $this->server->get(GroupMapping::class);
		return count($userMapping->getList(0, 1, true)) === 0
			&& count($groupMapping->getList(0, 1, true)) === 0;
	}
}
