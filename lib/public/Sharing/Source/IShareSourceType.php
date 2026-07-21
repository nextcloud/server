<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing\Source;

use OCP\AppFramework\Attribute\Implementable;
use OCP\Interaction\InteractionResource;
use OCP\L10N\IFactory;
use OCP\Sharing\Icon\ShareIconSVG;
use OCP\Sharing\Icon\ShareIconURL;

/**
 * @since 35.0.0
 */
#[Implementable(since: '35.0.0')]
interface IShareSourceType {
	/**
	 * Returns a user friendly display name for this source type.
	 *
	 * @return non-empty-string
	 * @since 35.0.0
	 */
	public function getDisplayName(IFactory $l10nFactory): string;

	/**
	 * Validate that a source exists.
	 *
	 * Any check if the source is allowed to be accessed and shared, must be implemented through {@see RestrictInteractionEvent}.
	 *
	 * @param non-empty-string $source
	 * @since 35.0.0
	 */
	public function validateSource(string $source): bool;

	/**
	 * @param non-empty-string $source
	 * @return ?non-empty-string
	 * @since 35.0.0
	 */
	public function getSourceDisplayName(string $source): ?string;

	/**
	 * @param non-empty-string $source
	 * @since 35.0.0
	 */
	public function getSourceIcon(string $source): null|ShareIconSVG|ShareIconURL;

	/**
	 * @param non-empty-string $userId
	 * @param non-empty-string $source
	 * @since 35.0.0
	 */
	public function getSourceInteractionResource(string $userId, string $source): InteractionResource;
}
