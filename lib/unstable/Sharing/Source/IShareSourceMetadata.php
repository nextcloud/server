<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Sharing\Source;

use NCU\Sharing\Icon\ShareIconSVG;
use NCU\Sharing\Icon\ShareIconURL;
use OCP\AppFramework\Attribute\Implementable;

/**
 * @experimental 35.0.0
 */
#[Implementable(since: '35.0.0')]
interface IShareSourceMetadata {
	/**
	 * Get a user friendly display name for thew source
	 *
	 * @return non-empty-string
	 * @experimental 35.0.0
	 */
	public function getDisplayName(): string;

	/**
	 * Get the icon for for the source
	 *
	 * @experimental 35.0.0
	 */
	public function getIcon(): null|ShareIconSVG|ShareIconURL;
}
