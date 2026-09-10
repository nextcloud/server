<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Core\Listener;

use OC\Core\Listener\PersistentServiceInvalidationListener;
use OCP\App\Events\AppDisableEvent;
use OCP\App\Events\AppEnableEvent;
use OCP\AppFramework\Utility\IPersistentServiceInvalidator;
use OCP\AppFramework\Utility\PersistentServiceGroup;
use OCP\EventDispatcher\Event;

class PersistentServiceInvalidationListenerTest extends \Test\TestCase {
	private IPersistentServiceInvalidator&\PHPUnit\Framework\MockObject\MockObject $invalidator;
	private PersistentServiceInvalidationListener $listener;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->invalidator = $this->createMock(IPersistentServiceInvalidator::class);
		$this->listener = new PersistentServiceInvalidationListener($this->invalidator);
	}

	public function testHandlesAppEnableEvent(): void {
		$this->invalidator->expects($this->once())
			->method('invalidate')
			->with(PersistentServiceGroup::Apps);

		$this->listener->handle(new AppEnableEvent('news'));
	}

	public function testHandlesAppDisableEvent(): void {
		$this->invalidator->expects($this->once())
			->method('invalidate')
			->with(PersistentServiceGroup::Apps);

		$this->listener->handle(new AppDisableEvent('news'));
	}

	public function testIgnoresUnrelatedEvents(): void {
		$this->invalidator->expects($this->never())
			->method('invalidate');

		$this->listener->handle(new Event());
	}
}
