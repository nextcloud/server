<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\App\AppStore\Bundles;

use OC\App\AppStore\Bundles\EducationBundle;

class EducationBundleTest extends BundleBase {
	protected function setUp(): void {
		parent::setUp();
		$this->bundle = new EducationBundle($this->l10n);
		$this->bundleIdentifier = 'EducationBundle';
		$this->bundleName = 'Education bundle';
		$this->bundleAppIds = [
			'dashboard',
			'circles',
			'groupfolders',
			'announcementcenter',
			'quota_warning',
			'user_saml',
			'whiteboard',
		];
	}
}
