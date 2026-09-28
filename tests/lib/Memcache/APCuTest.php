<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Memcache;

use OC\Memcache\APCu;

/**
 * @group Memcache
 * @group APCu
 */
class APCuTest extends Cache {
	protected function setUp(): void {
		parent::setUp();

		if (!APCu::isAvailable()) {
			$this->markTestSkipped('The APCu extension is not available.');
			return;
		}
		$this->instance = new APCu($this->getUniqueID());
	}

	public function testCasIntChanged(): void {
		$this->instance->set('foo', 1);
		$this->assertTrue($this->instance->cas('foo', 1, 2));
		$this->assertEquals(2, $this->instance->get('foo'));
	}

	public function testCasIntNotChanged(): void {
		$this->instance->set('foo', 1);
		$this->assertFalse($this->instance->cas('foo', 2, 3));
		$this->assertEquals(1, $this->instance->get('foo'));
	}
}
