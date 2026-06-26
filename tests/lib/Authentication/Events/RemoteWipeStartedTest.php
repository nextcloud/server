<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Authentication\Events;

use OC\Authentication\Events\RemoteWipeStarted;
use OC\Authentication\Token\IToken;
use Test\TestCase;

class RemoteWipeStartedTest extends TestCase {
	public function testGetToken(): void {
		$token = $this->createMock(IToken::class);
		$event = new RemoteWipeStarted($token);

		$this->assertSame($token, $event->getToken());
	}
}
