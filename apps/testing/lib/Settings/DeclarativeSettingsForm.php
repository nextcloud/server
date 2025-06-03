<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Testing\Settings;

use OCP\Settings\DeclarativeSettingsTypes;
use OCP\Settings\IDeclarativeSettingsForm;

class DeclarativeSettingsForm implements IDeclarativeSettingsForm {
	public function getSchema(): array {
		return [
			'id' => 'test_declarative_form',
			'priority' => 10,
			'section_type' => DeclarativeSettingsTypes::SECTION_TYPE_ADMIN, // admin, personal
			'section_id' => 'additional',
			'storage_type' => DeclarativeSettingsTypes::STORAGE_TYPE_INTERNAL, // external, internal (handled by core to store in appconfig and preferences)
			'title' => 'Test declarative settings class', // NcSettingsSection name
			'description' => 'This form is registered with a DeclarativeSettingsForm class', // NcSettingsSection description
			'doc_url' => '', // NcSettingsSection doc_url for documentation or help page, empty string if not needed
			'fields' => [
				[
					'id' => 'test_ex_app_field_7', // configkey
					'title' => 'Multi-selection', // name or label
					'description' => 'Select some option setting', // hint
					'type' => DeclarativeSettingsTypes::MULTI_SELECT, // select, radio, multi-select
					'options' => ['foo', 'bar', 'baz'], // simple options for select, radio, multi-select
					'placeholder' => 'Select some multiple options', // input placeholder
					'default' => ['foo', 'bar'],
				],
				[
					'id' => 'some_real_setting',
					'title' => 'Choose init status check background job interval',
					'description' => 'How often AppAPI should check for initialization status',
					'type' => DeclarativeSettingsTypes::RADIO, // radio (NcCheckboxRadioSwitch type radio)
					'placeholder' => 'Choose init status check background job interval',
					'default' => '40m',
					'options' => [
						[
							'name' => 'Each 40 minutes', // NcCheckboxRadioSwitch display name
							'value' => '40m' // NcCheckboxRadioSwitch value
						],
						[
							'name' => 'Each 60 minutes',
							'value' => '60m'
						],
						[
							'name' => 'Each 120 minutes',
							'value' => '120m'
						],
						[
							'name' => 'Each day',
							'value' => 60 * 24 . 'm'
						],
					],
				],
				[
					'id' => 'test_ex_app_field_1', // configkey
					'title' => 'Default text field', // label
					'description' => 'Set some simple text setting', // hint
					'type' => DeclarativeSettingsTypes::TEXT, // text, password, email, tel, url, number
					'placeholder' => 'Enter text setting', // placeholder
					'default' => 'foo',
				],
				[
					'id' => 'test_ex_app_field_1_1',
					'title' => 'Email field',
					'description' => 'Set email config',
					'type' => DeclarativeSettingsTypes::EMAIL,
					'placeholder' => 'Enter email',
					'default' => '',
				],
				[
					'id' => 'test_ex_app_field_1_2',
					'title' => 'Tel field',
					'description' => 'Set tel config',
					'type' => DeclarativeSettingsTypes::TEL,
					'placeholder' => 'Enter your tel',
					'default' => '',
				],
				[
					'id' => 'test_ex_app_field_1_3',
					'title' => 'Url (website) field',
					'description' => 'Set url config',
					'type' => 'url',
					'placeholder' => 'Enter url',
					'default' => '',
				],
				[
					'id' => 'test_ex_app_field_1_4',
					'title' => 'Number field',
					'description' => 'Set number config',
					'type' => DeclarativeSettingsTypes::NUMBER,
					'placeholder' => 'Enter number value',
					'default' => 0,
				],
				[
					'id' => 'test_ex_app_field_2',
					'title' => 'Password',
					'description' => 'Set some secure value setting',
					'type' => 'password',
					'placeholder' => 'Set secure value',
					'default' => '',
				],
				[
					'id' => 'test_ex_app_field_3',
					'title' => 'Selection',
					'description' => 'Select some option setting',
					'type' => DeclarativeSettingsTypes::SELECT, // select, radio, multi-select
					'options' => ['foo', 'bar', 'baz'],
					'placeholder' => 'Select some option setting',
					'default' => 'foo',
				],
				[
					'id' => 'test_ex_app_field_4',
					'title' => 'Toggle something',
					'description' => 'Select checkbox option setting',
					'type' => DeclarativeSettingsTypes::CHECKBOX, // checkbox, multiple-checkbox
					'label' => 'Verify something if enabled',
					'default' => false,
				],
				[
					'id' => 'test_ex_app_field_5',
					'title' => 'Multiple checkbox toggles, describing one setting, checked options are saved as an JSON object {foo: true, bar: false}',
					'description' => 'Select checkbox option setting',
					'type' => DeclarativeSettingsTypes::MULTI_CHECKBOX, // checkbox, multi-checkbox
					'default' => ['foo' => true, 'bar' => true, 'baz' => true],
					'options' => [
						[
							'name' => 'Foo',
							'value' => 'foo', // multiple-checkbox configkey
						],
						[
							'name' => 'Bar',
							'value' => 'bar',
						],
						[
							'name' => 'Baz',
							'value' => 'baz',
						],
						[
							'name' => 'Qux',
							'value' => 'qux',
						],
					],
				],
				[
					'id' => 'test_ex_app_field_6',
					'title' => 'Radio toggles, describing one setting like single select',
					'description' => 'Select radio option setting',
					'type' => DeclarativeSettingsTypes::RADIO, // radio (NcCheckboxRadioSwitch type radio)
					'label' => 'Select single toggle',
					'default' => 'foo',
					'options' => [
						[
							'name' => 'First radio', // NcCheckboxRadioSwitch display name
							'value' => 'foo' // NcCheckboxRadioSwitch value
						],
						[
							'name' => 'Second radio',
							'value' => 'bar'
						],
						[
							'name' => 'Third radio',
							'value' => 'baz'
						],
					],
				],
				[
					'id' => 'test_sensitive_field',
					'title' => 'Sensitive text field',
					'description' => 'Set some secure value setting that is stored encrypted',
					'type' => DeclarativeSettingsTypes::TEXT,
					'label' => 'Sensitive field',
					'placeholder' => 'Set secure value',
					'default' => '',
					'sensitive' => true, // only for TEXT, PASSWORD types
				],
				[
					'id' => 'test_sensitive_field_2',
					'title' => 'Sensitive password field',
					'description' => 'Set some password setting that is stored encrypted',
					'type' => DeclarativeSettingsTypes::PASSWORD,
					'label' => 'Sensitive field',
					'placeholder' => 'Set secure value',
					'default' => '',
					'sensitive' => true, // only for TEXT, PASSWORD types
				],
				[
					'id' => 'test_non_sensitive_field',
					'title' => 'Password field',
					'description' => 'Set some password setting',
					'type' => DeclarativeSettingsTypes::PASSWORD,
					'label' => 'Password field',
					'placeholder' => 'Set secure value',
					'default' => '',
					'sensitive' => false,
				],
			],
		];
	}
}
