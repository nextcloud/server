<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\App\AppStore\Bundles;

class EnterpriseBundle extends Bundle {
	/**
	 * {@inheritDoc}
	 */
	public function getName(): string {
		return $this->l10n->t('Enterprise bundle');
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAppIdentifiers(): array {
		return [
			'admin_audit',
			'user_ldap',
			'files_retention',
			'files_automatedtagging',
			'user_saml',
			'files_accesscontrol',
			'terms_of_service',
		];
	}
}
