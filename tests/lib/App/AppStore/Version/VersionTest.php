<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\App\AppStore\Version;

use OC\App\AppStore\Version\Version;
use Test\TestCase;

class VersionTest extends TestCase {
	public function testGetMinimumVersion(): void {
		$version = new Version('9', '10');
		$this->assertSame('9', $version->getMinimumVersion());
	}

	public function testGetMaximumVersion(): void {
		$version = new Version('9', '10');
		$this->assertSame('10', $version->getMaximumVersion());
	}
}
