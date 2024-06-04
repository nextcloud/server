<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Session;

class MemoryTest extends Session {
	protected function setUp(): void {
		parent::setUp();
		$this->instance = new \OC\Session\Memory($this->getUniqueID());
	}

	
	public function testThrowsExceptionOnGetId() {
		$this->expectException(\OCP\Session\Exceptions\SessionNotAvailableException::class);

		$this->instance->getId();
	}
}
