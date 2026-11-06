<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace lib\Share20\ShareReview;

use OCP\Share\ShareReview\Events\ShareReviewAccessCheckEvent;
use OCP\Share\ShareReview\ShareReviewActionContext;
use PHPUnit\Framework\TestCase;

final class ShareReviewActionContextTest extends TestCase {

	public function testDefaultsToTheSessionUserActingAsOperator(): void {
		$context = new ShareReviewActionContext();

		$this->assertNull($context->actingUserId);
		$this->assertSame(ShareReviewAccessCheckEvent::SCOPE_OPERATOR, $context->scope);
	}

	public function testHoldsActingUserAndScope(): void {
		$context = new ShareReviewActionContext('alice', ShareReviewAccessCheckEvent::SCOPE_SELF);

		$this->assertSame('alice', $context->actingUserId);
		$this->assertSame(ShareReviewAccessCheckEvent::SCOPE_SELF, $context->scope);
	}

	public function testRejectsUnknownScope(): void {
		$this->expectException(\InvalidArgumentException::class);

		new ShareReviewActionContext(null, 'admin');
	}

	public function testForwardsIntoTheEventUnchanged(): void {
		$context = new ShareReviewActionContext('alice', ShareReviewAccessCheckEvent::SCOPE_SELF);

		$event = new ShareReviewAccessCheckEvent('Deck', '3', ShareReviewAccessCheckEvent::ACTION_DELETE, $context->actingUserId, $context->scope);

		$this->assertSame('alice', $event->getActingUserId());
		$this->assertSame(ShareReviewAccessCheckEvent::SCOPE_SELF, $event->getScope());
	}
}
