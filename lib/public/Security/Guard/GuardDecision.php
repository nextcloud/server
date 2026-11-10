<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Security\Guard;

/**
 * A request guard's decision to refuse a request.
 *
 * @since 36.0.0
 */
final class GuardDecision {
	private function __construct(
		private readonly int $retryAfter,
		private readonly string $reference,
	) {
	}

	/**
	 * @param int $retryAfter seconds after which the client may try again
	 * @param string $reference short id shown to the client, so an administrator can look the block up
	 * @since 36.0.0
	 */
	public static function deny(int $retryAfter, string $reference): self {
		return new self(max(0, $retryAfter), $reference);
	}

	/**
	 * @since 36.0.0
	 */
	public function getRetryAfter(): int {
		return $this->retryAfter;
	}

	/**
	 * @since 36.0.0
	 */
	public function getReference(): string {
		return $this->reference;
	}
}
