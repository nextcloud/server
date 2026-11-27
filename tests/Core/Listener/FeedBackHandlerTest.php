<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Core\Listener;

use OC\Core\Listener\FeedBackHandler;
use OC\Repair\Events\RepairWarningEvent;
use OCP\IEventSource;
use OCP\IL10N;
use Test\TestCase;

class FeedBackHandlerTest extends TestCase {
	public function testRepairWarningIsSentAsWarning(): void {
		$source = $this->createMock(IEventSource::class);
		$source->expects($this->once())
			->method('send')
			->with('warning', 'Repair warning: Unable to clear the frontend cache');
		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnCallback(static fn (string $text): string => $text);

		$handler = new FeedBackHandler($source, $l10n);
		$handler->handleRepairFeedback(new RepairWarningEvent('Unable to clear the frontend cache'));
	}
}
