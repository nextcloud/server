<?php
/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2015 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use OC\Session\Memory;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;

/**
 * Starts a new session before each test execution
 */
class StartSessionListener implements TestListener {
	use TestListenerDefaultImplementation;

	public function endTest(Test $test, float $time): void {
		// reopen the session - only allowed for memory session
		if (\OC::$server->getSession() instanceof Memory) {
			/** @var $session Memory */
			$session = \OC::$server->getSession();
			$session->reopen();
		}
	}
}
