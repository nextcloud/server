<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace lib\Share20\ShareReview;

use OCP\Share\ShareReview\IShareReviewSource;
use OCP\Share\ShareReview\RegisterShareReviewSourceEvent;
use PHPUnit\Framework\TestCase;

final class RegisterShareReviewSourceEventTest extends TestCase {

	public function testNoSourcesRegistered(): void {
		$event = new RegisterShareReviewSourceEvent();

		$this->assertSame([], $event->getSources());
	}

	public function testRegisterSource(): void {
		$sourceClass = $this->createMock(IShareReviewSource::class)::class;

		$event = new RegisterShareReviewSourceEvent();
		$event->registerSource($sourceClass);

		$this->assertSame([$sourceClass], $event->getSources());
	}

	public function testRegisterSourceKeepsDuplicates(): void {
		$sourceClass = $this->createMock(IShareReviewSource::class)::class;

		$event = new RegisterShareReviewSourceEvent();
		$event->registerSource($sourceClass);
		$event->registerSource($sourceClass);

		$this->assertSame([$sourceClass, $sourceClass], $event->getSources());
	}
}
