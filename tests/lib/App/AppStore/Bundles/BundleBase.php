<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\App\AppStore\Bundles;

use OC\App\AppStore\Bundles\Bundle;
use OCP\IL10N;
use Test\TestCase;

abstract class BundleBase extends TestCase {
	/** @var IL10N|\PHPUnit\Framework\MockObject\MockObject */
	protected $l10n;
	/** @var Bundle */
	protected $bundle;
	/** @var string */
	protected $bundleIdentifier;
	/** @var string */
	protected $bundleName;
	/** @var array */
	protected $bundleAppIds;

	protected function setUp(): void {
		parent::setUp();
		$this->l10n = $this->createMock(IL10N::class);
		$this->l10n->method('t')
			->willReturnCallback(function ($text, $parameters = []) {
				return vsprintf($text, $parameters);
			});
	}

	public function testGetIdentifier(): void {
		$this->assertSame($this->bundleIdentifier, $this->bundle->getIdentifier());
	}

	public function testGetName(): void {
		$this->assertSame($this->bundleName, $this->bundle->getName());
	}

	public function testGetAppIdentifiers(): void {
		$this->assertSame($this->bundleAppIds, $this->bundle->getAppIdentifiers());
	}
}
