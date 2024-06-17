<?php
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use OC\EventSourceFactory;
use OCP\IEventSource;
use OCP\IRequest;
use OCP\Security\CSRF\ICsrfValidator;

class EventSourceFactoryTest extends TestCase {
	public function testCreate(): void {
		$request = $this->createMock(IRequest::class);
		$csrfValidator = $this->createMock(ICsrfValidator::class);
		$factory = new EventSourceFactory($request, $csrfValidator);

		$instance = $factory->create();
		$this->assertInstanceOf(IEventSource::class, $instance);
	}
}
