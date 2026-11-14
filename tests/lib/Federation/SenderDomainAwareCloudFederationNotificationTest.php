<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Federation;

use OC\Federation\SenderDomainAwareCloudFederationNotification;
use Test\TestCase;

class SenderDomainAwareCloudFederationNotificationTest extends TestCase {
	public function testGetMessageIncludesSenderDomainAndShareType(): void {
		$notification = new SenderDomainAwareCloudFederationNotification('remote.example', 'user');
		$notification->setMessage('USER_REMOVED', 'circle', '', ['key' => 'value']);

		$this->assertSame([
			'notificationType' => 'USER_REMOVED',
			'resourceType' => 'circle',
			'providerId' => '',
			'notification' => ['key' => 'value'],
			'senderDomain' => 'remote.example',
			'shareType' => 'user',
		], $notification->getMessage());
	}

	public function testGetMessageOmitsUnsetShareType(): void {
		$notification = new SenderDomainAwareCloudFederationNotification('remote.example', null);
		$notification->setMessage('USER_REMOVED', 'circle', '', []);

		$this->assertArrayNotHasKey('shareType', $notification->getMessage());
	}
}
