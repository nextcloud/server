<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing\Source;

use OCP\AppFramework\Attribute\Implementable;
use OCP\IUser;

/**
 * @since 34.0.0
 */
#[Implementable(since: '34.0.0')]
interface IShareSourceType {
	/**
	 * Returns a user friendly display name for this source type.
	 *
	 * @return non-empty-string
	 */
	public function getDisplayName(): string;

	/**
	 * Validate that a source exists.
	 *
	 * @param non-empty-string $source
	 */
	public function validateSource(IUser $owner, string $source): bool;

	/**
	 * @param non-empty-string $source
	 * @return ?non-empty-string
	 */
	public function getSourceDisplayName(string $source): ?string;
}
