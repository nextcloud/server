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
use OCP\L10N\IFactory;
use OCP\Sharing\Share;

/**
 * @psalm-import-type SharingProperty from Share
 * @psalm-import-type SharingPropertyBoolean from Share
 * @psalm-import-type SharingPropertyDate from Share
 * @psalm-import-type SharingPropertyEnum from Share
 * @psalm-import-type SharingPropertyPassword from Share
 * @psalm-import-type SharingPropertyString from Share
 * @since 35.0.0
 */
#[Implementable(since: '35.0.0')]
interface ISharePropertyType {
	/**
	 * Returns a user friendly display name for this property.
	 *
	 * @return non-empty-string
	 * @since 35.0.0
	 */
	public function getDisplayName(IFactory $l10nFactory): string;

	/**
	 * Returns a user friendly hint for this property.
	 *
	 * @return ?non-empty-string
	 * @since 35.0.0
	 */
	public function getHint(IFactory $l10nFactory): ?string;

	/**
	 * Returns a priority used for sorting the properties for the user interface.
	 * A higher value means the property will be shown further up in the list of properties.
	 *
	 * @return int<1, 100>
	 * @since 35.0.0
	 */
	public function getPriority(): int;

	/**
	 * Whether clients should show it in the advanced settings section.
	 *
	 * @since 35.0.0
	 */
	public function isAdvanced(): bool;

	/**
	 * Whether this property is required to be set.
	 *
	 * @since 35.0.0
	 */
	public function isRequired(): bool;

	/**
	 * The default value if the user hasn't provided one.
	 *
	 * A default value must be returned, if {@see self::isRequired()} returns true.
	 *
	 * If the class also implements {@see ISharePropertyTypeModifyValue}, {@see ISharePropertyTypeModifyValue::modifyValueOnSave()} will be called when the value is saved to the database, but the value will be returned to the user as-is.
	 *
	 * @since 35.0.0
	 */
	public function getDefaultValue(): ?string;

	/**
	 * Validates the value when the share is created or updated in the database.
	 *
	 * Returns a user friendly error message if the value is not valid.
	 *
	 * @since 35.0.0
	 */
	public function validateValue(IFactory $l10nFactory, string $value): true|string;

	/**
	 * @param SharingProperty $property
	 * @return SharingPropertyBoolean|SharingPropertyDate|SharingPropertyEnum|SharingPropertyPassword|SharingPropertyString
	 * @since 35.0.0
	 */
	public function format(array $property): array;
}
