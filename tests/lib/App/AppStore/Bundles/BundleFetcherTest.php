<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\App\AppStore\Bundles;

use OC\App\AppStore\Bundles\BundleFetcher;
use OC\App\AppStore\Bundles\EducationBundle;
use OC\App\AppStore\Bundles\EnterpriseBundle;
use OC\App\AppStore\Bundles\GroupwareBundle;
use OC\App\AppStore\Bundles\HubBundle;
use OC\App\AppStore\Bundles\PublicSectorBundle;
use OC\App\AppStore\Bundles\SocialSharingBundle;
use OCP\IL10N;
use Test\TestCase;

class BundleFetcherTest extends TestCase {
	/** @var IL10N|\PHPUnit\Framework\MockObject\MockObject */
	private $l10n;
	/** @var BundleFetcher */
	private $bundleFetcher;

	protected function setUp(): void {
		parent::setUp();

		$this->l10n = $this->createMock(IL10N::class);

		$this->bundleFetcher = new BundleFetcher(
			$this->l10n
		);
	}

	public function testGetBundles(): void {
		$expected = [
			new EnterpriseBundle($this->l10n),
			new HubBundle($this->l10n),
			new GroupwareBundle($this->l10n),
			new SocialSharingBundle($this->l10n),
			new EducationBundle($this->l10n),
			new PublicSectorBundle($this->l10n),
		];
		$this->assertEquals($expected, $this->bundleFetcher->getBundles());
	}

	public function testGetBundleByIdentifier(): void {
		$this->assertEquals(new EnterpriseBundle($this->l10n), $this->bundleFetcher->getBundleByIdentifier('EnterpriseBundle'));
		$this->assertEquals(new GroupwareBundle($this->l10n), $this->bundleFetcher->getBundleByIdentifier('GroupwareBundle'));
	}


	public function testGetBundleByIdentifierWithException(): void {
		$this->expectException(\BadMethodCallException::class);
		$this->expectExceptionMessage('Bundle with specified identifier does not exist');

		$this->bundleFetcher->getBundleByIdentifier('NotExistingBundle');
	}
}
