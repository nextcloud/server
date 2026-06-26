<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Settings;

/**
 * @since 29.0.0
 *
 * @psalm-type DeclarativeSettingsSectionType = 'admin'|'personal'
 *
 * @psalm-type DeclarativeSettingsStorageType = 'internal'|'external'
 *
 * @psalm-type DeclarativeSettingsValueTypes = string|int|float|bool|list<string>
 *
 * @psalm-type DeclarativeSettingsFormField = array{
 *   id: string,
 *   title: string,
 *   description?: string,
 *   type: 'text'|'password'|'email'|'tel'|'url'|'number'|'checkbox'|'multi-checkbox'|'radio'|'select'|'multi-select',
 *   placeholder?: string,
 *   label?: string,
 *   default: mixed,
 *   options?: list<string|array{name: string, value: mixed}>,
 *   sensitive?: boolean,
 * }
 *
 * @psalm-type DeclarativeSettingsFormFieldWithValue = DeclarativeSettingsFormField&array{
 *     value: DeclarativeSettingsValueTypes,
 * }
 *
 * @psalm-type DeclarativeSettingsFormSchema = array{
 *   id: string,
 *   priority: int,
 *   section_type: DeclarativeSettingsSectionType,
 *   section_id: string,
 *   storage_type: DeclarativeSettingsStorageType,
 *   title: string,
 *   description?: string,
 *   doc_url?: string,
 * }
 *
 * @psalm-type DeclarativeSettingsFormSchemaWithValues = DeclarativeSettingsFormSchema&array{
 *   app: string,
 *   fields: list<DeclarativeSettingsFormFieldWithValue>,
 * }
 *
 * @psalm-type DeclarativeSettingsFormSchemaWithoutValues = DeclarativeSettingsFormSchema&array{
 *   fields: list<DeclarativeSettingsFormField>,
 * }
 */
interface IDeclarativeSettingsForm {
	/**
	 * Gets the schema that defines the declarative settings form
	 *
	 * @return DeclarativeSettingsFormSchemaWithoutValues
	 * @since 29.0.0
	 */
	public function getSchema(): array;
}
