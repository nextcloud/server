<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Settings;

use Exception;
use OC\AppFramework\Bootstrap\Coordinator;
use OC\AppFramework\Middleware\Security\Exceptions\NotAdminException;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\Security\ICrypto;
use OCP\Server;
use OCP\Settings\DeclarativeSettingsTypes;
use OCP\Settings\Events\DeclarativeSettingsGetValueEvent;
use OCP\Settings\Events\DeclarativeSettingsRegisterFormEvent;
use OCP\Settings\Events\DeclarativeSettingsSetValueEvent;
use OCP\Settings\IDeclarativeManager;
use OCP\Settings\IDeclarativeSettingsForm;
use OCP\Settings\IDeclarativeSettingsFormWithHandlers;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type DeclarativeSettingsValueTypes from IDeclarativeSettingsForm
 * @psalm-import-type DeclarativeSettingsStorageType from IDeclarativeSettingsForm
 * @psalm-import-type DeclarativeSettingsSectionType from IDeclarativeSettingsForm
 * @psalm-import-type DeclarativeSettingsFormSchemaWithValues from IDeclarativeSettingsForm
 * @psalm-import-type DeclarativeSettingsFormSchemaWithoutValues from IDeclarativeSettingsForm
 */
class DeclarativeManager implements IDeclarativeManager {

	/** @var array<string, list<IDeclarativeSettingsForm>> */
	private array $declarativeForms = [];

	/**
	 * @var array<string, list<DeclarativeSettingsFormSchemaWithoutValues>>
	 */
	private array $appSchemas = [];

	public function __construct(
		private IEventDispatcher $eventDispatcher,
		private IGroupManager $groupManager,
		private Coordinator $coordinator,
		private IConfig $config,
		private IAppConfig $appConfig,
		private LoggerInterface $logger,
		private ICrypto $crypto,
	) {
	}

	/**
	 * @inheritdoc
	 */
	public function registerSchema(string $app, array $schema): void {
		$this->appSchemas[$app] ??= [];

		if (!$this->validateSchema($app, $schema)) {
			throw new Exception('Invalid schema. Please check the logs for more details.');
		}

		foreach ($this->appSchemas[$app] as $otherSchema) {
			if ($otherSchema['id'] === $schema['id']) {
				throw new Exception('Duplicate form IDs detected: ' . $schema['id']);
			}
		}

		$fieldIDs = array_map(fn ($field) => $field['id'], $schema['fields']);
		$otherFieldIDs = array_merge(...array_map(fn ($schema) => array_map(fn ($field) => $field['id'], $schema['fields']), $this->appSchemas[$app]));
		$intersectionFieldIDs = array_intersect($fieldIDs, $otherFieldIDs);
		if (count($intersectionFieldIDs) > 0) {
			throw new Exception('Non unique field IDs detected: ' . join(', ', $intersectionFieldIDs));
		}

		$this->appSchemas[$app][] = $schema;
	}

	/**
	 * @inheritdoc
	 */
	public function loadSchemas(): void {
		if (empty($this->declarativeForms)) {
			$declarativeSettings = $this->coordinator->getRegistrationContext()->getDeclarativeSettings();
			foreach ($declarativeSettings as $declarativeSetting) {
				$app = $declarativeSetting->getAppId();
				/** @var IDeclarativeSettingsForm $declarativeForm */
				$declarativeForm = Server::get($declarativeSetting->getService());
				$this->registerSchema($app, $declarativeForm->getSchema());
				$this->declarativeForms[$app][] = $declarativeForm;
			}
		}

		$this->eventDispatcher->dispatchTyped(new DeclarativeSettingsRegisterFormEvent($this));
	}

	/**
	 * @inheritdoc
	 */
	public function getFormIDs(IUser $user, string $type, string $section): array {
		$isAdmin = $this->groupManager->isAdmin($user->getUID());
		/** @var array<string, list<string>> $formIds */
		$formIds = [];

		foreach ($this->appSchemas as $app => $schemas) {
			$ids = [];
			usort($schemas, [$this, 'sortSchemasByPriorityCallback']);
			foreach ($schemas as $schema) {
				if ($schema['section_type'] === DeclarativeSettingsTypes::SECTION_TYPE_ADMIN && !$isAdmin) {
					continue;
				}
				if ($schema['section_type'] === $type && $schema['section_id'] === $section) {
					$ids[] = $schema['id'];
				}
			}

			if (!empty($ids)) {
				$formIds[$app] = array_merge($formIds[$app] ?? [], $ids);
			}
		}

		return $formIds;
	}

	/**
	 * @inheritdoc
	 * @throws Exception
	 */
	public function getFormsWithValues(IUser $user, ?string $type, ?string $section): array {
		$isAdmin = $this->groupManager->isAdmin($user->getUID());
		$forms = [];

		foreach ($this->appSchemas as $app => $schemas) {
			foreach ($schemas as $schema) {
				if ($type !== null && $schema['section_type'] !== $type) {
					continue;
				}
				if ($section !== null && $schema['section_id'] !== $section) {
					continue;
				}
				// If listing all fields skip the admin fields which a non-admin user has no access to
				if ($type === null && $schema['section_type'] === 'admin' && !$isAdmin) {
					continue;
				}

				$s = $schema;
				$s['app'] = $app;

				foreach ($s['fields'] as &$field) {
					$field['value'] = $this->getValue($user, $app, $schema['id'], $field['id']);
				}
				unset($field);

				/** @var DeclarativeSettingsFormSchemaWithValues $s */
				$forms[] = $s;
			}
		}

		usort($forms, [$this, 'sortSchemasByPriorityCallback']);

		return $forms;
	}

	private function sortSchemasByPriorityCallback(mixed $a, mixed $b): int {
		if ($a['priority'] === $b['priority']) {
			return 0;
		}
		return $a['priority'] > $b['priority'] ? -1 : 1;
	}

	/**
	 * @return DeclarativeSettingsStorageType
	 */
	private function getStorageType(string $app, string $fieldId): string {
		if (array_key_exists($app, $this->appSchemas)) {
			foreach ($this->appSchemas[$app] as $schema) {
				foreach ($schema['fields'] as $field) {
					if ($field['id'] == $fieldId) {
						if (array_key_exists('storage_type', $field)) {
							return $field['storage_type'];
						}
					}
				}

				if (array_key_exists('storage_type', $schema)) {
					return $schema['storage_type'];
				}
			}
		}

		return DeclarativeSettingsTypes::STORAGE_TYPE_INTERNAL;
	}

	/**
	 * @return DeclarativeSettingsSectionType
	 * @throws Exception
	 */
	private function getSectionType(string $app, string $fieldId): string {
		if (array_key_exists($app, $this->appSchemas)) {
			foreach ($this->appSchemas[$app] as $schema) {
				foreach ($schema['fields'] as $field) {
					if ($field['id'] == $fieldId) {
						return $schema['section_type'];
					}
				}
			}
		}

		throw new Exception('Unknown fieldId "' . $fieldId . '"');
	}

	/**
	 * @psalm-param DeclarativeSettingsSectionType $sectionType
	 * @throws NotAdminException
	 */
	private function assertAuthorized(IUser $user, string $sectionType): void {
		if ($sectionType === 'admin' && !$this->groupManager->isAdmin($user->getUID())) {
			throw new NotAdminException('Logged in user does not have permission to access these settings.');
		}
	}

	/**
	 * @return DeclarativeSettingsValueTypes
	 * @throws Exception
	 * @throws NotAdminException
	 */
	private function getValue(IUser $user, string $app, string $formId, string $fieldId): mixed {
		$sectionType = $this->getSectionType($app, $fieldId);
		$this->assertAuthorized($user, $sectionType);

		$storageType = $this->getStorageType($app, $fieldId);
		switch ($storageType) {
			case DeclarativeSettingsTypes::STORAGE_TYPE_EXTERNAL:
				$form = $this->getForm($app, $formId);
				if ($form !== null && $form instanceof IDeclarativeSettingsFormWithHandlers) {
					return $form->getValue($fieldId, $user);
				}
				$event = new DeclarativeSettingsGetValueEvent($user, $app, $formId, $fieldId);
				$this->eventDispatcher->dispatchTyped($event);
				return $event->getValue();
			case DeclarativeSettingsTypes::STORAGE_TYPE_INTERNAL:
				return $this->getInternalValue($user, $app, $formId, $fieldId);
			default:
				throw new Exception('Unknown storage type "' . $storageType . '"');
		}
	}

	/**
	 * @inheritdoc
	 */
	public function setValue(IUser $user, string $app, string $formId, string $fieldId, mixed $value): void {
		$sectionType = $this->getSectionType($app, $fieldId);
		$this->assertAuthorized($user, $sectionType);

		$storageType = $this->getStorageType($app, $fieldId);
		switch ($storageType) {
			case DeclarativeSettingsTypes::STORAGE_TYPE_EXTERNAL:
				$form = $this->getForm($app, $formId);
				if ($form !== null && $form instanceof IDeclarativeSettingsFormWithHandlers) {
					$form->setValue($fieldId, $value, $user);
					break;
				}
				// fall back to event handling
				$this->eventDispatcher->dispatchTyped(new DeclarativeSettingsSetValueEvent($user, $app, $formId, $fieldId, $value));
				break;
			case DeclarativeSettingsTypes::STORAGE_TYPE_INTERNAL:
				$this->saveInternalValue($user, $app, $formId, $fieldId, $value);
				break;
			default:
				throw new Exception('Unknown storage type "' . $storageType . '"');
		}
	}

	/**
	 * If a declarative setting was registered as a form and not just a schema
	 * then this will yield the registering form.
	 */
	private function getForm(string $app, string $formId): ?IDeclarativeSettingsForm {
		$allForms = $this->declarativeForms[$app] ?? [];
		foreach ($allForms as $form) {
			if ($form->getSchema()['id'] === $formId) {
				return $form;
			}
		}
		return null;
	}

	private function getInternalValue(IUser $user, string $app, string $formId, string $fieldId): mixed {
		$sectionType = $this->getSectionType($app, $fieldId);
		$defaultValue = $this->getDefaultValue($app, $formId, $fieldId);

		$field = $this->getSchemaField($app, $formId, $fieldId);
		$isSensitive = $field !== null && isset($field['sensitive']) && $field['sensitive'] === true;

		switch ($sectionType) {
			case DeclarativeSettingsTypes::SECTION_TYPE_ADMIN:
				$value = $this->config->getAppValue($app, $fieldId, $defaultValue);
				break;
			case DeclarativeSettingsTypes::SECTION_TYPE_PERSONAL:
				$value = $this->config->getUserValue($user->getUID(), $app, $fieldId, $defaultValue);
				break;
			default:
				throw new Exception('Unknown section type "' . $sectionType . '"');
		}
		if ($isSensitive && $value !== '') {
			try {
				$value = $this->crypto->decrypt($value);
			} catch (Exception $e) {
				$this->logger->warning('Failed to decrypt sensitive value for field {field} in app {app}: {message}', [
					'field' => $fieldId,
					'app' => $app,
					'message' => $e->getMessage(),
				]);
				$value = $defaultValue;
			}
		}
		return $value;
	}

	private function saveInternalValue(IUser $user, string $app, string $formId, string $fieldId, mixed $value): void {
		$sectionType = $this->getSectionType($app, $fieldId);

		$field = $this->getSchemaField($app, $formId, $fieldId);
		if ($field !== null && isset($field['sensitive']) && $field['sensitive'] === true && $value !== '' && $value !== 'dummySecret') {
			try {
				$value = $this->crypto->encrypt($value);
			} catch (Exception $e) {
				$this->logger->warning('Failed to decrypt sensitive value for field {field} in app {app}: {message}', [
					'field' => $fieldId,
					'app' => $app,
					'message' => $e->getMessage()]
				);
				throw new Exception('Failed to encrypt sensitive value');
			}
		}

		switch ($sectionType) {
			case DeclarativeSettingsTypes::SECTION_TYPE_ADMIN:
				$this->appConfig->setValueString($app, $fieldId, $value);
				break;
			case DeclarativeSettingsTypes::SECTION_TYPE_PERSONAL:
				$this->config->setUserValue($user->getUID(), $app, $fieldId, $value);
				break;
			default:
				throw new Exception('Unknown section type "' . $sectionType . '"');
		}
	}

	private function getSchemaField(string $app, string $formId, string $fieldId): ?array {
		$form = $this->getForm($app, $formId);
		if ($form !== null) {
			foreach ($form->getSchema()['fields'] as $field) {
				if ($field['id'] === $fieldId) {
					return $field;
				}
			}
		}
		foreach ($this->appSchemas[$app] ?? [] as $schema) {
			if ($schema['id'] === $formId) {
				foreach ($schema['fields'] as $field) {
					if ($field['id'] === $fieldId) {
						return $field;
					}
				}
			}
		}
		return null;
	}

	private function getDefaultValue(string $app, string $formId, string $fieldId): mixed {
		foreach ($this->appSchemas[$app] as $schema) {
			if ($schema['id'] === $formId) {
				foreach ($schema['fields'] as $field) {
					if ($field['id'] === $fieldId) {
						if (isset($field['default'])) {
							if (is_array($field['default'])) {
								return json_encode($field['default']);
							}
							return $field['default'];
						}
					}
				}
			}
		}
		return null;
	}

	private function validateSchema(string $appId, array $schema): bool {
		if (!isset($schema['id'])) {
			$this->logger->warning('Attempt to register a declarative settings schema with no id', ['app' => $appId]);
			return false;
		}
		$formId = $schema['id'];
		if (!isset($schema['section_type'])) {
			$this->logger->warning('Declarative settings: missing section_type', ['app' => $appId, 'form_id' => $formId]);
			return false;
		}
		if (!in_array($schema['section_type'], [DeclarativeSettingsTypes::SECTION_TYPE_ADMIN, DeclarativeSettingsTypes::SECTION_TYPE_PERSONAL])) {
			$this->logger->warning('Declarative settings: invalid section_type', ['app' => $appId, 'form_id' => $formId, 'section_type' => $schema['section_type']]);
			return false;
		}
		if (!isset($schema['section_id'])) {
			$this->logger->warning('Declarative settings: missing section_id', ['app' => $appId, 'form_id' => $formId]);
			return false;
		}
		if (!isset($schema['storage_type'])) {
			$this->logger->warning('Declarative settings: missing storage_type', ['app' => $appId, 'form_id' => $formId]);
			return false;
		}
		if (!in_array($schema['storage_type'], [DeclarativeSettingsTypes::STORAGE_TYPE_EXTERNAL, DeclarativeSettingsTypes::STORAGE_TYPE_INTERNAL])) {
			$this->logger->warning('Declarative settings: invalid storage_type', ['app' => $appId, 'form_id' => $formId, 'storage_type' => $schema['storage_type']]);
			return false;
		}
		if (!isset($schema['title'])) {
			$this->logger->warning('Declarative settings: missing title', ['app' => $appId, 'form_id' => $formId]);
			return false;
		}
		if (!isset($schema['fields']) || !is_array($schema['fields'])) {
			$this->logger->warning('Declarative settings: missing or invalid fields', ['app' => $appId, 'form_id' => $formId]);
			return false;
		}
		foreach ($schema['fields'] as $field) {
			if (!isset($field['id'])) {
				$this->logger->warning('Declarative settings: missing field id', ['app' => $appId, 'form_id' => $formId, 'field' => $field]);
				return false;
			}
			$fieldId = $field['id'];
			if (!isset($field['title'])) {
				$this->logger->warning('Declarative settings: missing field title', ['app' => $appId, 'form_id' => $formId, 'field_id' => $fieldId]);
				return false;
			}
			if (!isset($field['type'])) {
				$this->logger->warning('Declarative settings: missing field type', ['app' => $appId, 'form_id' => $formId, 'field_id' => $fieldId]);
				return false;
			}
			if (!in_array($field['type'], [
				DeclarativeSettingsTypes::MULTI_SELECT, DeclarativeSettingsTypes::MULTI_CHECKBOX, DeclarativeSettingsTypes::RADIO,
				DeclarativeSettingsTypes::SELECT, DeclarativeSettingsTypes::CHECKBOX,
				DeclarativeSettingsTypes::URL, DeclarativeSettingsTypes::EMAIL, DeclarativeSettingsTypes::NUMBER,
				DeclarativeSettingsTypes::TEL, DeclarativeSettingsTypes::TEXT, DeclarativeSettingsTypes::PASSWORD,
			])) {
				$this->logger->warning('Declarative settings: invalid field type', [
					'app' => $appId, 'form_id' => $formId, 'field_id' => $fieldId, 'type' => $field['type'],
				]);
				return false;
			}
			if (isset($field['sensitive']) && $field['sensitive'] === true && !in_array($field['type'], [DeclarativeSettingsTypes::TEXT, DeclarativeSettingsTypes::PASSWORD])) {
				$this->logger->warning('Declarative settings: sensitive field type is supported only for TEXT and PASSWORD types ({app}, {form_id}, {field_id})', [
					'app' => $appId, 'form_id' => $formId, 'field_id' => $fieldId,
				]);
				return false;
			}
			if (!$this->validateField($appId, $formId, $field)) {
				return false;
			}
		}

		return true;
	}

	private function validateField(string $appId, string $formId, array $field): bool {
		$fieldId = $field['id'];
		if (in_array($field['type'], [
			DeclarativeSettingsTypes::MULTI_SELECT, DeclarativeSettingsTypes::MULTI_CHECKBOX, DeclarativeSettingsTypes::RADIO,
			DeclarativeSettingsTypes::SELECT
		])) {
			if (!isset($field['options'])) {
				$this->logger->warning('Declarative settings: missing field options', ['app' => $appId, 'form_id' => $formId, 'field_id' => $fieldId]);
				return false;
			}
			if (!is_array($field['options'])) {
				$this->logger->warning('Declarative settings: field options should be an array', ['app' => $appId, 'form_id' => $formId, 'field_id' => $fieldId]);
				return false;
			}
		}
		return true;
	}
}
