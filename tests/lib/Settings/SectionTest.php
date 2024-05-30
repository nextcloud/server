<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Tests\AppInfo;

use OC\Settings\Section;
use Test\TestCase;

class SectionTest extends TestCase {
	public function testGetID() {
		$this->assertSame('ldap', (new Section('ldap', 'name', 1))->getID());
	}
	public function testGetName() {
		$this->assertSame('name', (new Section('ldap', 'name', 1))->getName());
	}
	public function testGetPriority() {
		$this->assertSame(1, (new Section('ldap', 'name', 1))->getPriority());
	}
}
