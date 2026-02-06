<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Session;

use OC\Session\Memory;
use OCP\Session\Exceptions\SessionNotAvailableException;

/**
 * Concrete test case for OC\Session\Memory (in-memory session storage).
 * Reuses session contract tests and adds in-memory specific assertions.
 */
class MemoryTest extends Session {
	protected function setUp(): void {
		parent::setUp();
		$this->instance = new Memory();
	}


	public function testThrowsExceptionOnGetId(): void {
		$this->expectException(SessionNotAvailableException::class);

		$this->instance->getId();
	}
}
