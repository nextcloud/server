<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Kate Döen <kate.doeen@nextcloud.com>
 *
 * @author Kate Döen <kate.doeen@nextcloud.com>
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

use Exception;
use OC\AppFramework\Middleware\Security\Exceptions\NotAdminException;
use OCP\IUser;

/**
 * @since 29.0.0
 *
 * @psalm-import-type DeclarativeSettingsValueTypes from IDeclarativeSettingsForm
 * @psalm-import-type DeclarativeSettingsSectionType from IDeclarativeSettingsForm
 * @psalm-import-type DeclarativeSettingsFormSchemaWithValues from IDeclarativeSettingsForm
 * @psalm-import-type DeclarativeSettingsFormSchemaWithoutValues from IDeclarativeSettingsForm
 */
interface IDeclarativeManager {
	/**
	 * Registers a new declarative settings schema.
	 *
	 * @param DeclarativeSettingsFormSchemaWithoutValues $schema
	 * @since 29.0.0
	 */
	public function registerSchema(string $app, array $schema): void;

	/**
	 * Load all schemas from the registration context and events.
	 *
	 * @since 29.0.0
	 */
	public function loadSchemas(): void;

	/**
	 * Gets the IDs of the forms for the given type and section.
	 *
	 * @param DeclarativeSettingsSectionType $type
	 * @param string $section
	 * @return array<string, list<string>>
	 *
	 * @since 29.0.0
	 */
	public function getFormIDs(IUser $user, string $type, string $section): array;

	/**
	 * Gets the forms including the field values for the given type and section.
	 *
	 * @param IUser $user Used for reading values from the personal section or for authorization for the admin section.
	 * @param ?DeclarativeSettingsSectionType $type If it is null the forms will not be filtered by type.
	 * @param ?string $section If it is null the forms will not be filtered by section.
	 * @return list<DeclarativeSettingsFormSchemaWithValues>
	 *
	 * @since 29.0.0
	 */
	public function getFormsWithValues(IUser $user, ?string $type, ?string $section): array;

	/**
	 * Sets a value for the given field ID.
	 *
	 * @param IUser $user Used for storing values in the personal section or for authorization for the admin section.
	 * @param DeclarativeSettingsValueTypes $value
	 *
	 * @throws Exception
	 * @throws NotAdminException
	 *
	 * @since 29.0.0
	 */
	public function setValue(IUser $user, string $app, string $formId, string $fieldId, mixed $value): void;
}
