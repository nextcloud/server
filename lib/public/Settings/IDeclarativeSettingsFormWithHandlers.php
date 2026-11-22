<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Settings;

use OCP\IUser;

/**
 * @since 31.0.0
 */
interface IDeclarativeSettingsFormWithHandlers extends IDeclarativeSettingsForm {

	/**
	 * This function is called to get the current value of a specific forms field.
	 * @since 31.0.0
	 */
	public function getValue(string $fieldId, IUser $user): mixed;

	/**
	 * This function is called when a user updated a form field to persist the setting.
	 * @since 31.0.0
	 */
	public function setValue(string $fieldId, mixed $value, IUser $user): void;

}
