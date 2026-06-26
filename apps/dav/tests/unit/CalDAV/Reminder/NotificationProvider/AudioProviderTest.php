<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV\Reminder\NotificationProvider;

use OCA\DAV\CalDAV\Reminder\NotificationProvider\AudioProvider;

class AudioProviderTest extends PushProviderTest {
	public function testNotificationType():void {
		$this->assertEquals(AudioProvider::NOTIFICATION_TYPE, 'AUDIO');
	}
}
