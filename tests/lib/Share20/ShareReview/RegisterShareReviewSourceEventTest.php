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

	/** @return class-string<IShareReviewSource> */
	private function makeSourceClass(string $name): string {
		$source = new class($name) implements IShareReviewSource {
			public function __construct(
				private readonly string $name = '',
			) {
			}

			public function getName(): string {
				return $this->name;
			}

			public function getShares(): array {
				return [];
			}

			public function deleteShare(string $shareId): bool {
				return false;
			}
		};
		return $source::class;
	}

	public function testNoSourcesRegistered(): void {
		$event = new RegisterShareReviewSourceEvent();

		$this->assertSame([], $event->getSources());
	}

	public function testRegisterSource(): void {
		$sourceClass = $this->makeSourceClass('MyApp');

		$event = new RegisterShareReviewSourceEvent();
		$event->registerSource($sourceClass);

		$this->assertSame([$sourceClass], $event->getSources());
	}

	public function testRegisterSourceKeepsDuplicates(): void {
		$sourceClass = $this->makeSourceClass('MyApp');

		$event = new RegisterShareReviewSourceEvent();
		$event->registerSource($sourceClass);
		$event->registerSource($sourceClass);

		$this->assertSame([$sourceClass, $sourceClass], $event->getSources());
	}
}
