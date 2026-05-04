<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing\Property;

use OCP\AppFramework\Attribute\Implementable;

/**
 * @since 34.0.0
 */
#[Implementable(since: '34.0.0')]
interface ISharePropertyTypeModifyValue extends ISharePropertyType {
	/**
	 * Modify the value whenever a share is created or updated in the database.
	 *
	 * The value has been passed to {@see self::validateValue()} before the invocation of this method.
	 */
	public function modifyValueOnSave(?string $oldValue, ?string $newValue): ?string;

	/**
	 * Modify the value whenever a share is fetched from the database.
	 */
	public function modifyValueOnLoad(?string $value): ?string;
}
