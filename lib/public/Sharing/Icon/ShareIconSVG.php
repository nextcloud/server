<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing\Icon;

use OCP\AppFramework\Attribute\Consumable;
use OCP\Sharing\Share;
use RuntimeException;

/**
 * @psalm-import-type SharingIconSVG from Share
 * @since 34.0.0
 */
#[Consumable(since: '34.0.0')]
final readonly class ShareIconSVG {
	public function __construct(
		/** @var non-empty-string $svg */
		public string $svg,
	) {
		if (!str_contains($svg, '<svg')) {
			throw new RuntimeException('The svg is not a valid SVG: ' . $svg);
		}
	}

	/**
	 * @return SharingIconSVG
	 */
	public function format(): array {
		return [
			'svg' => $this->svg,
		];
	}
}
