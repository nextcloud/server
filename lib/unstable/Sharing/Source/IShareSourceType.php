<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Sharing\Source;

use OCP\AppFramework\Attribute\Implementable;
use OCP\Interaction\InteractionResource;
use OCP\IUser;
use OCP\L10N\IFactory;

/**
 * @experimental 35.0.0
 */
#[Implementable(since: '35.0.0')]
interface IShareSourceType {
	/**
	 * Returns a user friendly display name for this source type.
	 *
	 * @return non-empty-string
	 * @experimental 35.0.0
	 */
	public function getDisplayName(IFactory $l10nFactory): string;

	/**
	 * Validate that a source exists.
	 *
	 * Any check if the source is allowed to be accessed and shared, must be implemented through {@see RestrictInteractionEvent}.
	 *
	 * @param non-empty-string $source
	 * @experimental 35.0.0
	 */
	public function validateSource(string $source): bool;

	/**
	 * @param non-empty-string $source
	 * @experimental 35.0.0
	 */
	public function getSourceMetadata(string $source): ?IShareSourceMetadata;

	/**
	 * @param non-empty-string[] $sources
	 * @return array<non-empty-string, IShareSourceMetadata>
	 * @experimental 35.0.0
	 */
	public function getSourcesMetadata(array $sources): array;

	/**
	 * @param non-empty-string $source
	 * @experimental 35.0.0
	 */
	public function getSourceInteractionResource(IUser $user, string $source): InteractionResource;

	/**
	 * Check if a user has access to the specified source without taking sharing into account, and has sufficient permissions to create shares.
	 *
	 * All users with "direct" access to the source will be able to see and manage shares made by other users for the source.
	 *
	 * @experimental 35.0.0
	 * @param non-empty-string $source
	 */
	public function userHasDirectSharingAccessToSource(IUser $user, string $source): bool;
}
