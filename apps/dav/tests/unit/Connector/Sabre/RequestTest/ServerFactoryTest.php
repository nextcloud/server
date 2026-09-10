<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Connector\Sabre\RequestTest;

use OCA\DAV\Connector\Sabre\PropFindMountAvailabilityPlugin;
use Sabre\DAV\Auth\Plugin;

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class ServerFactoryTest extends RequestTestCase {
	public function testRegistersMountAvailabilityPlugin(): void {
		$server = $this->serverFactory->createServer(
			false,
			'/',
			'dummy',
			$this->createMock(Plugin::class),
			static fn () => throw new \LogicException('View callback must not run while creating the server'),
		);

		$this->assertInstanceOf(
			PropFindMountAvailabilityPlugin::class,
			$server->getPlugin(PropFindMountAvailabilityPlugin::class),
		);
	}
}
