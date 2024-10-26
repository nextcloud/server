<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\IntegrityCheck\Helpers;

use OC\IntegrityCheck\Helpers\AppLocator;
use Test\TestCase;

class AppLocatorTest extends TestCase {
	/** @var AppLocator */
	private $locator;

	protected function setUp(): void {
		parent::setUp();
		$this->locator = new AppLocator();
	}

	public function testGetAppPath(): void {
		$this->assertSame(\OC_App::getAppPath('files'), $this->locator->getAppPath('files'));
	}

	
	public function testGetAppPathNotExistentApp(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('App not found');

		$this->locator->getAppPath('aTotallyNotExistingApp');
	}
}
