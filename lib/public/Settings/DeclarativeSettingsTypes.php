<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Settings;

/**
 * Declarative settings types supported in the IDeclarativeSettingsForm forms
 *
 * @since 29.0.0
 */
final class DeclarativeSettingsTypes {
	/**
	 * IDeclarativeSettingsForm section_type which is determines where the form is displayed
	 *
	 * @since 29.0.0
	 */
	public const SECTION_TYPE_ADMIN = 'admin';

	/**
	 * IDeclarativeSettingsForm section_type which is determines where the form is displayed
	 *
	 * @since 29.0.0
	 */
	public const SECTION_TYPE_PERSONAL = 'personal';

	/**
	 * IDeclarativeSettingsForm storage_type which is determines where and how the config value is stored
	 *
	 * For `external` storage_type the app needs to either implement event listeners for \OCP\Settings\SetDeclarativeSettingsValueEvent
	 * and \OCP\Settings\GetDeclarativeSettingsValueEvent or the IDeclarativeSettingsForm also needs to implement
	 * IDeclarativeSettingsFormWithHandlers for storing and retrieving the config value.
	 *
	 * @since 29.0.0
	 */
	public const STORAGE_TYPE_EXTERNAL = 'external';

	/**
	 * IDeclarativeSettingsForm storage_type which is determines where and how the config value is stored
	 *
	 * For `internal` storage_type the config value is stored in default `appconfig` and `preferences` tables.
	 *
	 * @since 29.0.0
	 */
	public const STORAGE_TYPE_INTERNAL = 'internal';

	/**
	 * NcInputField type text
	 *
	 * @since 29.0.0
	 */
	public const TEXT = 'text';

	/**
	 * NcInputField type password
	 *
	 * @since 29.0.0
	 */
	public const PASSWORD = 'password';

	/**
	 * NcInputField type email
	 *
	 * @since 29.0.0
	 */
	public const EMAIL = 'email';

	/**
	 * NcInputField type tel
	 *
	 * @since 29.0.0
	 */
	public const TEL = 'tel';

	/**
	 * NcInputField type url
	 *
	 * @since 29.0.0
	 */
	public const URL = 'url';

	/**
	 * NcInputField type number
	 *
	 * @since 29.0.0
	 */
	public const NUMBER = 'number';

	/**
	 * NcCheckboxRadioSwitch type checkbox
	 *
	 * @since 29.0.0
	 */
	public const CHECKBOX = 'checkbox';

	/**
	 * Multiple NcCheckboxRadioSwitch type checkbox representing a one config value (saved as JSON object)
	 *
	 * @since 29.0.0
	 */
	public const MULTI_CHECKBOX = 'multi-checkbox';

	/**
	 * NcCheckboxRadioSwitch type radio
	 *
	 * @since 29.0.0
	 */
	public const RADIO = 'radio';

	/**
	 * NcSelect
	 *
	 * @since 29.0.0
	 */
	public const SELECT = 'select';

	/**
	 * Multiple NcSelect representing a one config value (saved as JSON array)
	 *
	 * @since 29.0.0
	 */
	public const MULTI_SELECT = 'multi-select';
}
