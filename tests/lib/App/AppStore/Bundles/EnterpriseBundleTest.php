<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\App\AppStore\Bundles;

use OC\App\AppStore\Bundles\EnterpriseBundle;

class EnterpriseBundleTest extends BundleBase {
	protected function setUp(): void {
		parent::setUp();
		$this->bundle = new EnterpriseBundle($this->l10n);
		$this->bundleIdentifier = 'EnterpriseBundle';
		$this->bundleName = 'Enterprise bundle';
		$this->bundleAppIds = [
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
