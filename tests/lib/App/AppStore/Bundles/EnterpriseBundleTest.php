<?php
/**
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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
