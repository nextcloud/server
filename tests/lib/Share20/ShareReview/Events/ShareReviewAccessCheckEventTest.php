<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace lib\Share20\ShareReview\Events;

use OCP\Share\ShareReview\Events\ShareReviewAccessCheckEvent;
use PHPUnit\Framework\TestCase;

final class ShareReviewAccessCheckEventTest extends TestCase {

	private function makeEvent(): ShareReviewAccessCheckEvent {
		return new ShareReviewAccessCheckEvent('MyApp', '42');
	}

	public function testInitialState(): void {
		$event = $this->makeEvent();

		$this->assertFalse($event->isHandled());
		$this->assertFalse($event->isGranted());
		$this->assertNull($event->getReason());
	}

	public function testConstructorPayload(): void {
		$event = new ShareReviewAccessCheckEvent('Deck', '99');

		$this->assertSame('Deck', $event->getSourceName());
		$this->assertSame('99', $event->getShareId());
	}

	public function testGrantAccess(): void {
		$event = $this->makeEvent();
		$event->grantAccess();

		$this->assertTrue($event->isHandled());
		$this->assertTrue($event->isGranted());
		$this->assertNull($event->getReason());
		$this->assertFalse($event->isPropagationStopped());
	}

	public function testDenyAccess(): void {
		$event = $this->makeEvent();
		$event->denyAccess('not in group');

		$this->assertTrue($event->isHandled());
		$this->assertFalse($event->isGranted());
		$this->assertSame('not in group', $event->getReason());
	}

	public function testDenyStopsPropagation(): void {
		$event = $this->makeEvent();
		$event->denyAccess('no access');

		$this->assertTrue($event->isPropagationStopped());
	}

	public function testGrantDoesNotStopPropagation(): void {
		$event = $this->makeEvent();
		$event->grantAccess();

		$this->assertFalse($event->isPropagationStopped());
	}

	public function testGrantThenDenyIsDenied(): void {
		$event = $this->makeEvent();
		$event->grantAccess();
		$event->denyAccess('revoked');

		$this->assertFalse($event->isGranted());
		$this->assertSame('revoked', $event->getReason());
		$this->assertTrue($event->isPropagationStopped());
	}

	public function testDenyThenGrantRemainesDenied(): void {
		$event = $this->makeEvent();
		$event->denyAccess('not allowed');
		$event->grantAccess(); // must be ignored — deny wins

		$this->assertFalse($event->isGranted());
		$this->assertSame('not allowed', $event->getReason());
	}

	public function testMultipleGrantsAreIdempotent(): void {
		$event = $this->makeEvent();
		$event->grantAccess();
		$event->grantAccess();

		$this->assertTrue($event->isGranted());
		$this->assertFalse($event->isPropagationStopped());
	}
}
