<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Settings;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\AppFramework\Bootstrap\RegistrationContext;
use OC\AppFramework\Bootstrap\ServiceRegistration;
use OC\Settings\DeclarativeManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\Security\ICrypto;
use OCP\Settings\DeclarativeSettingsTypes;
use OCP\Settings\Events\DeclarativeSettingsSetValueEvent;
use OCP\Settings\IDeclarativeManager;
use OCP\Settings\IDeclarativeSettingsForm;
use OCP\Settings\IDeclarativeSettingsFormWithHandlers;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class DeclarativeManagerTest extends TestCase {

	/** @var IDeclarativeManager|MockObject */
	private $declarativeManager;

	/** @var IEventDispatcher|MockObject */
	private $eventDispatcher;

	/** @var IGroupManager|MockObject */
	private $groupManager;

	/** @var Coordinator|MockObject */
	private $coordinator;

	/** @var IConfig|MockObject */
	private $config;

	/** @var IAppConfig|MockObject */
	private $appConfig;

	/** @var LoggerInterface|MockObject */
	private $logger;

	/** @var ICrypto|MockObject */
	private $crypto;

	/** @var IUser|MockObject */
	private $user;

	/** @var IUser|MockObject */
	private $adminUser;

	private IDeclarativeSettingsForm&MockObject $closureForm;

	public const validSchemaAllFields = [
		'id' => 'test_form_1',
		'priority' => 10,
		'section_type' => DeclarativeSettingsTypes::SECTION_TYPE_ADMIN, // admin, personal
		'section_id' => 'additional',
		'storage_type' => DeclarativeSettingsTypes::STORAGE_TYPE_INTERNAL, // external, internal (handled by core to store in appconfig and preferences)
		'title' => 'Test declarative settings', // NcSettingsSection name
		'description' => 'These fields are rendered dynamically from declarative schema', // NcSettingsSection description
		'doc_url' => '', // NcSettingsSection doc_url for documentation or help page, empty string if not needed
		'fields' => [
			[
				'id' => 'test_field_7', // configkey
				'title' => 'Multi-selection', // name or label
				'description' => 'Select some option setting', // hint
				'type' => DeclarativeSettingsTypes::MULTI_SELECT,
				'options' => ['foo', 'bar', 'baz'], // simple options for select, radio, multi-select
				'placeholder' => 'Select some multiple options', // input placeholder
				'default' => ['foo', 'bar'],
			],
			[
				'id' => 'some_real_setting',
				'title' => 'Select single option',
				'description' => 'Single option radio buttons',
				'type' => DeclarativeSettingsTypes::RADIO, // radio (NcCheckboxRadioSwitch type radio)
				'placeholder' => 'Select single option, test interval',
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
				'id' => 'test_field_1', // configkey
				'title' => 'Default text field', // label
				'description' => 'Set some simple text setting', // hint
				'type' => DeclarativeSettingsTypes::TEXT,
				'placeholder' => 'Enter text setting', // placeholder
				'default' => 'foo',
			],
			[
				'id' => 'test_field_1_1',
				'title' => 'Email field',
				'description' => 'Set email config',
				'type' => DeclarativeSettingsTypes::EMAIL,
				'placeholder' => 'Enter email',
				'default' => '',
			],
			[
				'id' => 'test_field_1_2',
				'title' => 'Tel field',
				'description' => 'Set tel config',
				'type' => DeclarativeSettingsTypes::TEL,
				'placeholder' => 'Enter your tel',
				'default' => '',
			],
			[
				'id' => 'test_field_1_3',
				'title' => 'Url (website) field',
				'description' => 'Set url config',
				'type' => 'url',
				'placeholder' => 'Enter url',
				'default' => '',
			],
			[
				'id' => 'test_field_1_4',
				'title' => 'Number field',
				'description' => 'Set number config',
				'type' => DeclarativeSettingsTypes::NUMBER,
				'placeholder' => 'Enter number value',
				'default' => 0,
			],
			[
				'id' => 'test_field_2',
				'title' => 'Password',
				'description' => 'Set some secure value setting',
				'type' => 'password',
				'placeholder' => 'Set secure value',
				'default' => '',
			],
			[
				'id' => 'test_field_3',
				'title' => 'Selection',
				'description' => 'Select some option setting',
				'type' => DeclarativeSettingsTypes::SELECT,
				'options' => ['foo', 'bar', 'baz'],
				'placeholder' => 'Select some option setting',
				'default' => 'foo',
			],
			[
				'id' => 'test_field_4',
				'title' => 'Toggle something',
				'description' => 'Select checkbox option setting',
				'type' => DeclarativeSettingsTypes::CHECKBOX,
				'label' => 'Verify something if enabled',
				'default' => false,
			],
			[
				'id' => 'test_field_5',
				'title' => 'Multiple checkbox toggles, describing one setting, checked options are saved as an JSON object {foo: true, bar: false}',
				'description' => 'Select checkbox option setting',
				'type' => DeclarativeSettingsTypes::MULTI_CHECKBOX,
				'default' => ['foo' => true, 'bar' => true],
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
				'id' => 'test_field_6',
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
						'name' => 'Second radio',
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

	public static bool $testSetInternalValueAfterChange = false;

	protected function setUp(): void {
		parent::setUp();

		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->coordinator = $this->createMock(Coordinator::class);
		$this->config = $this->createMock(IConfig::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->crypto = $this->createMock(ICrypto::class);

		$this->declarativeManager = new DeclarativeManager(
			$this->eventDispatcher,
			$this->groupManager,
			$this->coordinator,
			$this->config,
			$this->appConfig,
			$this->logger,
			$this->crypto,
		);

		$this->user = $this->createMock(IUser::class);
		$this->user->expects($this->any())
			->method('getUID')
			->willReturn('test_user');

		$this->adminUser = $this->createMock(IUser::class);
		$this->adminUser->expects($this->any())
			->method('getUID')
			->willReturn('admin_test_user');

		$this->groupManager->expects($this->any())
			->method('isAdmin')
			->willReturnCallback(function ($userId) {
				return $userId === 'admin_test_user';
			});
	}

	public function testRegisterSchema(): void {
		$app = 'testing';
		$schema = self::validSchemaAllFields;
		$this->declarativeManager->registerSchema($app, $schema);
		$formIds = $this->declarativeManager->getFormIDs($this->adminUser, $schema['section_type'], $schema['section_id']);
		$this->assertTrue(isset($formIds[$app]) && in_array($schema['id'], $formIds[$app]));
	}

	/**
	 * Simple test to verify that exception is thrown when trying to register schema with duplicate id
	 */
	public function testRegisterDuplicateSchema(): void {
		$this->declarativeManager->registerSchema('testing', self::validSchemaAllFields);
		$this->expectException(\Exception::class);
		$this->declarativeManager->registerSchema('testing', self::validSchemaAllFields);
	}

	/**
	 * It's not allowed to register schema with duplicate fields ids for the same app
	 */
	public function testRegisterSchemaWithDuplicateFields(): void {
		// Register first valid schema
		$this->declarativeManager->registerSchema('testing', self::validSchemaAllFields);
		// Register second schema with duplicate fields, but different schema id
		$this->expectException(\Exception::class);
		$schema = self::validSchemaAllFields;
		$schema['id'] = 'test_form_2';
		$this->declarativeManager->registerSchema('testing', $schema);
	}

	public function testRegisterMultipleSchemasAndDuplicate(): void {
		$app = 'testing';
		$schema = self::validSchemaAllFields;
		$this->declarativeManager->registerSchema($app, $schema);
		$formIds = $this->declarativeManager->getFormIDs($this->adminUser, $schema['section_type'], $schema['section_id']);
		// 1. Check that form is registered for the app
		$this->assertTrue(isset($formIds[$app]) && in_array($schema['id'], $formIds[$app]));
		$app = 'testing2';
		$this->declarativeManager->registerSchema($app, $schema);
		$formIds = $this->declarativeManager->getFormIDs($this->adminUser, $schema['section_type'], $schema['section_id']);
		// 2. Check that form is registered for the second app
		$this->assertTrue(isset($formIds[$app]) && in_array($schema['id'], $formIds[$app]));
		$app = 'testing';
		$this->expectException(\Exception::class); // expecting duplicate form id and duplicate fields ids exception
		$this->declarativeManager->registerSchema($app, $schema);
		$schemaDuplicateFields = self::validSchemaAllFields;
		$schemaDuplicateFields['id'] = 'test_form_2'; // change form id to test duplicate fields
		$this->declarativeManager->registerSchema($app, $schemaDuplicateFields);
		// 3. Check that not valid form with duplicate fields is not registered
		$formIds = $this->declarativeManager->getFormIDs($this->adminUser, $schemaDuplicateFields['section_type'], $schemaDuplicateFields['section_id']);
		$this->assertFalse(isset($formIds[$app]) && in_array($schemaDuplicateFields['id'], $formIds[$app]));
	}

	/**
	 * @dataProvider dataValidateSchema
	 */
	public function testValidateSchema(bool $expected, bool $expectException, string $app, array $schema): void {
		if ($expectException) {
			$this->expectException(\Exception::class);
		}
		$this->declarativeManager->registerSchema($app, $schema);
		$formIds = $this->declarativeManager->getFormIDs($this->adminUser, $schema['section_type'], $schema['section_id']);
		$this->assertEquals($expected, isset($formIds[$app]) && in_array($schema['id'], $formIds[$app]));
	}

	public static function dataValidateSchema(): array {
		return [
			'valid schema with all supported fields' => [
				true,
				false,
				'testing',
				self::validSchemaAllFields,
			],
			'invalid schema with missing id' => [
				false,
				true,
				'testing',
				[
					'priority' => 10,
					'section_type' => DeclarativeSettingsTypes::SECTION_TYPE_ADMIN,
					'section_id' => 'additional',
					'storage_type' => DeclarativeSettingsTypes::STORAGE_TYPE_INTERNAL,
					'title' => 'Test declarative settings',
					'description' => 'These fields are rendered dynamically from declarative schema',
					'doc_url' => '',
					'fields' => [
						[
							'id' => 'test_field_7',
							'title' => 'Multi-selection',
							'description' => 'Select some option setting',
							'type' => DeclarativeSettingsTypes::MULTI_SELECT,
							'options' => ['foo', 'bar', 'baz'],
							'placeholder' => 'Select some multiple options',
							'default' => ['foo', 'bar'],
						],
					],
				],
			],
			'invalid schema with invalid field' => [
				false,
				true,
				'testing',
				[
					'id' => 'test_form_1',
					'priority' => 10,
					'section_type' => DeclarativeSettingsTypes::SECTION_TYPE_ADMIN,
					'section_id' => 'additional',
					'storage_type' => DeclarativeSettingsTypes::STORAGE_TYPE_INTERNAL,
					'title' => 'Test declarative settings',
					'description' => 'These fields are rendered dynamically from declarative schema',
					'doc_url' => '',
					'fields' => [
						[
							'id' => 'test_invalid_field',
							'title' => 'Invalid field',
							'description' => 'Some invalid setting description',
							'type' => 'some_invalid_type',
							'placeholder' => 'Some invalid field placeholder',
							'default' => null,
						],
					],
				],
			],
		];
	}

	public function testGetFormIDs(): void {
		$app = 'testing';
		$schema = self::validSchemaAllFields;
		$this->declarativeManager->registerSchema($app, $schema);
		$formIds = $this->declarativeManager->getFormIDs($this->adminUser, $schema['section_type'], $schema['section_id']);
		$this->assertTrue(isset($formIds[$app]) && in_array($schema['id'], $formIds[$app]));
		$app = 'testing2';
		$this->declarativeManager->registerSchema($app, $schema);
		$formIds = $this->declarativeManager->getFormIDs($this->adminUser, $schema['section_type'], $schema['section_id']);
		$this->assertTrue(isset($formIds[$app]) && in_array($schema['id'], $formIds[$app]));
	}

	/**
	 * Check that form with default values is returned with internal storage_type
	 */
	public function testGetFormsWithDefaultValues(): void {
		$app = 'testing';
		$schema = self::validSchemaAllFields;
		$this->declarativeManager->registerSchema($app, $schema);

		$this->config->expects($this->any())
			->method('getAppValue')
			->willReturnCallback(fn ($app, $configkey, $default) => $default);

		$forms = $this->declarativeManager->getFormsWithValues($this->adminUser, $schema['section_type'], $schema['section_id']);
		$this->assertNotEmpty($forms);
		$this->assertTrue(array_search($schema['id'], array_column($forms, 'id')) !== false);
		// Check some_real_setting field default value
		$someRealSettingField = array_values(array_filter(array_filter($forms, fn ($form) => $form['id'] === $schema['id'])[0]['fields'], fn ($field) => $field['id'] === 'some_real_setting'))[0];
		$schemaSomeRealSettingField = array_values(array_filter($schema['fields'], fn ($field) => $field['id'] === 'some_real_setting'))[0];
		$this->assertEquals($schemaSomeRealSettingField['default'], $someRealSettingField['default']);
	}

	/**
	 * Check values in json format to ensure that they are properly encoded
	 */
	public function testGetFormsWithDefaultValuesJson(): void {
		$app = 'testing';
		$schema = [
			'id' => 'test_form_1',
			'priority' => 10,
			'section_type' => DeclarativeSettingsTypes::SECTION_TYPE_PERSONAL,
			'section_id' => 'additional',
			'storage_type' => DeclarativeSettingsTypes::STORAGE_TYPE_INTERNAL,
			'title' => 'Test declarative settings',
			'description' => 'These fields are rendered dynamically from declarative schema',
			'doc_url' => '',
			'fields' => [
				[
					'id' => 'test_field_json',
					'title' => 'Multi-selection',
					'description' => 'Select some option setting',
					'type' => DeclarativeSettingsTypes::MULTI_SELECT,
					'options' => ['foo', 'bar', 'baz'],
					'placeholder' => 'Select some multiple options',
					'default' => ['foo', 'bar'],
				],
			],
		];
		$this->declarativeManager->registerSchema($app, $schema);

		// config->getUserValue() should be called with json encoded default value
		$this->config->expects($this->once())
			->method('getUserValue')
			->with($this->adminUser->getUID(), $app, 'test_field_json', json_encode($schema['fields'][0]['default']))
			->willReturn(json_encode($schema['fields'][0]['default']));

		$forms = $this->declarativeManager->getFormsWithValues($this->adminUser, $schema['section_type'], $schema['section_id']);
		$this->assertNotEmpty($forms);
		$this->assertTrue(array_search($schema['id'], array_column($forms, 'id')) !== false);
		$testFieldJson = array_values(array_filter(array_filter($forms, fn ($form) => $form['id'] === $schema['id'])[0]['fields'], fn ($field) => $field['id'] === 'test_field_json'))[0];
		$this->assertEquals(json_encode($schema['fields'][0]['default']), $testFieldJson['value']);
	}

	/**
	 * Check that saving value for field with internal storage_type is handled by core
	 */
	public function testSetInternalValue(): void {
		$app = 'testing';
		$schema = self::validSchemaAllFields;
		$this->declarativeManager->registerSchema($app, $schema);
		self::$testSetInternalValueAfterChange = false;

		$this->config->expects($this->any())
			->method('getAppValue')
			->willReturnCallback(function ($app, $configkey, $default) {
				if ($configkey === 'some_real_setting' && self::$testSetInternalValueAfterChange) {
					return '120m';
				}
				return $default;
			});

		$this->appConfig->expects($this->once())
			->method('setValueString')
			->with($app, 'some_real_setting', '120m');

		$forms = $this->declarativeManager->getFormsWithValues($this->adminUser, $schema['section_type'], $schema['section_id']);
		$someRealSettingField = array_values(array_filter(array_filter($forms, fn ($form) => $form['id'] === $schema['id'])[0]['fields'], fn ($field) => $field['id'] === 'some_real_setting'))[0];
		$this->assertEquals('40m', $someRealSettingField['value']); // first check that default value (40m) is returned

		// Set new value for some_real_setting field
		$this->declarativeManager->setValue($this->adminUser, $app, $schema['id'], 'some_real_setting', '120m');
		self::$testSetInternalValueAfterChange = true;

		$forms = $this->declarativeManager->getFormsWithValues($this->adminUser, $schema['section_type'], $schema['section_id']);
		$this->assertNotEmpty($forms);
		$this->assertTrue(array_search($schema['id'], array_column($forms, 'id')) !== false);
		// Check some_real_setting field default value
		$someRealSettingField = array_values(array_filter(array_filter($forms, fn ($form) => $form['id'] === $schema['id'])[0]['fields'], fn ($field) => $field['id'] === 'some_real_setting'))[0];
		$this->assertEquals('120m', $someRealSettingField['value']);
	}

	public function testSetExternalValue(): void {
		$app = 'testing';
		$schema = self::validSchemaAllFields;
		// Change storage_type to external and section_type to personal
		$schema['storage_type'] = DeclarativeSettingsTypes::STORAGE_TYPE_EXTERNAL;
		$schema['section_type'] = DeclarativeSettingsTypes::SECTION_TYPE_PERSONAL;
		$this->declarativeManager->registerSchema($app, $schema);

		$setDeclarativeSettingsValueEvent = new DeclarativeSettingsSetValueEvent(
			$this->adminUser,
			$app,
			$schema['id'],
			'some_real_setting',
			'120m'
		);

		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with($setDeclarativeSettingsValueEvent);
		$this->declarativeManager->setValue($this->adminUser, $app, $schema['id'], 'some_real_setting', '120m');
	}

	public function testAdminFormUserUnauthorized(): void {
		$app = 'testing';
		$schema = self::validSchemaAllFields;
		$this->declarativeManager->registerSchema($app, $schema);

		$this->expectException(\Exception::class);
		$this->declarativeManager->getFormsWithValues($this->user, $schema['section_type'], $schema['section_id']);
	}

	/**
	 * Ensure that the `setValue` method is called if the form implements the handler interface.
	 */
	public function testSetValueWithHandler(): void {
		$schema = self::validSchemaAllFields;
		$schema['storage_type'] = DeclarativeSettingsTypes::STORAGE_TYPE_EXTERNAL;

		$form = $this->createMock(IDeclarativeSettingsFormWithHandlers::class);
		$form->expects(self::atLeastOnce())
			->method('getSchema')
			->willReturn($schema);
		// The setter should be called once!
		$form->expects(self::once())
			->method('setValue')
			->with('test_field_2', 'some password', $this->adminUser);

		\OC::$server->registerService('OCA\\Testing\\Settings\\DeclarativeForm', fn () => $form, false);

		$context = $this->createMock(RegistrationContext::class);
		$context->expects(self::atLeastOnce())
			->method('getDeclarativeSettings')
			->willReturn([new ServiceRegistration('testing', 'OCA\\Testing\\Settings\\DeclarativeForm')]);

		$this->coordinator->expects(self::atLeastOnce())
			->method('getRegistrationContext')
			->willReturn($context);

		$this->declarativeManager->loadSchemas();

		$this->eventDispatcher->expects(self::never())
			->method('dispatchTyped');

		$this->declarativeManager->setValue($this->adminUser, 'testing', 'test_form_1', 'test_field_2', 'some password');
	}

	public function testGetValueWithHandler(): void {
		$schema = self::validSchemaAllFields;
		$schema['storage_type'] = DeclarativeSettingsTypes::STORAGE_TYPE_EXTERNAL;

		$form = $this->createMock(IDeclarativeSettingsFormWithHandlers::class);
		$form->expects(self::atLeastOnce())
			->method('getSchema')
			->willReturn($schema);
		// The setter should be called once!
		$form->expects(self::once())
			->method('getValue')
			->with('test_field_2', $this->adminUser)
			->willReturn('very secret password');

		\OC::$server->registerService('OCA\\Testing\\Settings\\DeclarativeForm', fn () => $form, false);

		$context = $this->createMock(RegistrationContext::class);
		$context->expects(self::atLeastOnce())
			->method('getDeclarativeSettings')
			->willReturn([new ServiceRegistration('testing', 'OCA\\Testing\\Settings\\DeclarativeForm')]);

		$this->coordinator->expects(self::atLeastOnce())
			->method('getRegistrationContext')
			->willReturn($context);

		$this->declarativeManager->loadSchemas();

		$this->eventDispatcher->expects(self::never())
			->method('dispatchTyped');

		$password = $this->invokePrivate($this->declarativeManager, 'getValue', [$this->adminUser, 'testing', 'test_form_1', 'test_field_2']);
		self::assertEquals('very secret password', $password);
	}

}
