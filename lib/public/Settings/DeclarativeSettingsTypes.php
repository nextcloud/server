<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Andrey Borysenko <andrey.borysenko@nextcloud.com>
 *
 * @author Andrey Borysenko <andrey.borysenko@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
	 *
	 * For `external` storage_type the app implementing \OCP\Settings\SetDeclarativeSettingsValueEvent and \OCP\Settings\GetDeclarativeSettingsValueEvent events is responsible for storing and retrieving the config value.
	 *
	 * @since 29.0.0
	 */
	public const STORAGE_TYPE_EXTERNAL = 'external';

	/**
	 * IDeclarativeSettingsForm storage_type which is determines where and how the config value is stored
	 *
	 * For `internal` storage_type the config value is stored in default `appconfig` and `preferences` tables.
	 * For `external` storage_type the app implementing \OCP\Settings\SetDeclarativeSettingsValueEvent and \OCP\Settings\GetDeclarativeSettingsValueEvent events is responsible for storing and retrieving the config value.
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
