<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Sharing\Icon;

use NCU\Sharing\Share;
use OCP\AppFramework\Attribute\Consumable;
use RuntimeException;

/**
 * @psalm-import-type SharingIconSVG from Share
 * @experimental 35.0.0
 */
#[Consumable(since: '35.0.0')]
final readonly class ShareIconSVG {
	/**
	 * @experimental 35.0.0
	 */
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
	 * @experimental 35.0.0
	 */
	public function format(): array {
		return [
			'svg' => $this->svg,
		];
	}
}
