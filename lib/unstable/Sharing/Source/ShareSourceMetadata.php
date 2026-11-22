<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Sharing\Source;

use NCU\Sharing\Icon\ShareIconSVG;
use NCU\Sharing\Icon\ShareIconURL;
use OCP\AppFramework\Attribute\Consumable;

/**
 * @experimental 35.0.0
 */
#[Consumable(since: '35.0.0')]
final readonly class ShareSourceMetadata implements IShareSourceMetadata {
	/**
	 * @experimental 35.0.0
	 */
	public function __construct(
		/** @var non-empty-string $displayName */
		private string $displayName,
		private null|ShareIconSVG|ShareIconURL $icon,
	) {
	}

	/**
	 * Get the human-readable name for the source
	 *
	 * @experimental 35.0.0
	 */
	#[\Override]
	public function getDisplayName(): string {
		return $this->displayName;
	}

	/**
	 * Get the icon for the share source
	 *
	 * @experimental 35.0.0
	 */
	#[\Override]
	public function getIcon(): null|ShareIconSVG|ShareIconURL {
		return $this->icon;
	}
}
