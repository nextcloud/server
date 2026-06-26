<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorBackupCodes\Tests\Unit\Event;

use OCA\TwoFactorBackupCodes\Event\CodesGenerated;
use OCP\IUser;
use Test\TestCase;

class CodesGeneratedTest extends TestCase {
	public function testCodeGeneratedEvent(): void {
		$user = $this->createMock(IUser::class);

		$event = new CodesGenerated($user);

		$this->assertSame($user, $event->getUser());
	}
}
