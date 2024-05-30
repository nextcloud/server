<?php
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use OC\EventSourceFactory;
use OCP\IEventSource;
use OCP\IRequest;

class EventSourceFactoryTest extends TestCase {
	public function testCreate(): void {
		$request = $this->createMock(IRequest::class);
		$factory = new EventSourceFactory($request);

		$instance = $factory->create();
		$this->assertInstanceOf(IEventSource::class, $instance);
	}
}
