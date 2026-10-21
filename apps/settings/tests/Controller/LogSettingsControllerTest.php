<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Tests\Controller;

use OC\Log;
use OCA\Settings\Controller\LogSettingsController;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IRequest;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use Test\TestCase;

class LogSettingsControllerTest extends TestCase {
	public function testDownloadIsAudited(): void {
		$log = $this->createMock(Log::class);
		$log->method('getLogPath')->willReturn('/var/log/nextcloud.log');
		$eventDispatcher = $this->createMock(IEventDispatcher::class);
		$eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($this->callback(fn (CriticalActionPerformedEvent $event): bool => $event->getOperation() === 'settings.log.downloaded'
				&& $event->getParameters() === []
				&& $event->getLogMessage() === 'Log file was downloaded'));

		$controller = new LogSettingsController('settings', $this->createMock(IRequest::class), $log, $eventDispatcher);
		$controller->download();
	}
}
