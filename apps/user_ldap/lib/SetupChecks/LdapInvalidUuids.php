<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_LDAP\SetupChecks;

use OCA\User_LDAP\Mapping\GroupMapping;
use OCA\User_LDAP\Mapping\UserMapping;
use OCP\IL10N;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class LdapInvalidUuids implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private UserMapping $userMapping,
		private GroupMapping $groupMapping,
	) {
	}

	public function getCategory(): string {
		return 'ldap';
	}

	public function getName(): string {
		return $this->l10n->t('Invalid LDAP UUIDs');
	}

	public function run(): SetupResult {
		if (count($this->userMapping->getList(0, 1, true)) === 0
			&& count($this->groupMapping->getList(0, 1, true)) === 0) {
			return SetupResult::success($this->l10n->t('None found'));
		} else {
			return SetupResult::warning($this->l10n->t('Invalid UUIDs of LDAP accounts or groups have been found. Please review your "Override UUID detection" settings in the Expert part of the LDAP configuration and use "occ ldap:update-uuid" to update them.'));
		}
	}
}
