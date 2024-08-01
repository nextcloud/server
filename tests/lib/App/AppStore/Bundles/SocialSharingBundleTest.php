<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\App\AppStore\Bundles;

use OC\App\AppStore\Bundles\SocialSharingBundle;

class SocialSharingBundleTest extends BundleBase {
	protected function setUp(): void {
		parent::setUp();
		$this->bundle = new SocialSharingBundle($this->l10n);
		$this->bundleIdentifier = 'SocialSharingBundle';
		$this->bundleName = 'Social sharing bundle';
		$this->bundleAppIds = [
			'socialsharing_twitter',
			'socialsharing_facebook',
			'socialsharing_email',
			'socialsharing_diaspora',
		];
	}
}
