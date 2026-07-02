<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Share\ShareReview;

use OCP\AppFramework\Attribute\Dispatchable;
use OCP\AppFramework\Attribute\Listenable;
use OCP\EventDispatcher\Event;

/**
 * Event dispatched by a share-review app to collect share sources from other
 * apps. Listeners register the class name of their {@see IShareReviewSource}
 * implementation; the share-review app resolves it from the dependency
 * injection container.
 *
 * @since 34.0.2
 */
#[Listenable(since: '34.0.2')]
#[Dispatchable(since: '34.0.2')]
class RegisterShareReviewSourceEvent extends Event {

	/** @var array<int, class-string<IShareReviewSource>> */
	private array $sources = [];

	/**
	 * @param class-string<IShareReviewSource> $source
	 * @since 34.0.2
	 */
	public function registerSource(string $source): void {
		$this->sources[] = $source;
	}

	/**
	 * @return array<int, class-string<IShareReviewSource>>
	 * @since 34.0.2
	 */
	public function getSources(): array {
		return $this->sources;
	}
}
