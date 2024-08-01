<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\App\AppStore\Bundles;

use OC\App\AppStore\Bundles\GroupwareBundle;

class GroupwareBundleTest extends BundleBase {
	protected function setUp(): void {
		parent::setUp();
		$this->bundle = new GroupwareBundle($this->l10n);
		$this->bundleIdentifier = 'GroupwareBundle';
		$this->bundleName = 'Groupware bundle';
		$this->bundleAppIds = [
			'calendar',
			'contacts',
			'deck',
			'mail'
		];
	}
}
