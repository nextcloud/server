<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Sharing\Property;

use OCP\AppFramework\Attribute\Implementable;
use OCP\Sharing\Share;

/**
 * @psalm-import-type SharingProperty from Share
 * @psalm-import-type SharingPropertyBoolean from Share
 * @psalm-import-type SharingPropertyDate from Share
 * @psalm-import-type SharingPropertyEnum from Share
 * @psalm-import-type SharingPropertyPassword from Share
 * @psalm-import-type SharingPropertyString from Share
 * @since 34.0.0
 */
#[Implementable(since: '34.0.0')]
interface ISharePropertyType {
	/**
	 * Returns a user friendly display name for this property.
	 *
	 * @return non-empty-string
	 */
	public function getDisplayName(): string;

	/**
	 * Returns a user friendly hint for this property.
	 *
	 * @return ?non-empty-string
	 */
	public function getHint(): ?string;

	/**
	 * Returns a priority used for sorting the properties for the user interface.
	 * A higher value means the property will be shown further up in the list of properties.
	 *
	 * @return int<1, 100>
	 */
	public function getPriority(): int;

	/**
	 * Whether this property is required to be set.
	 */
	public function getRequired(): bool;

	/**
	 * The default value if the user hasn't provided one.
	 */
	public function getDefaultValue(): ?string;

	/**
	 * Validates the value when the share is created or updated in the database.
	 *
	 * Returns a user friendly error message if the value is not valid.
	 */
	public function validateValue(string $value): true|string;

	/**
	 * @param SharingProperty $property
	 * @return SharingPropertyBoolean|SharingPropertyDate|SharingPropertyEnum|SharingPropertyPassword|SharingPropertyString
	 */
	public function format(array $property): array;
}
