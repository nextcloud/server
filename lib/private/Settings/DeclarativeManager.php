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

namespace OC\Settings;

use Exception;
use OC\AppFramework\Bootstrap\Coordinator;
use OC\AppFramework\Middleware\Security\Exceptions\NotAdminException;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\Server;
use OCP\Settings\DeclarativeSettingsTypes;
use OCP\Settings\Events\DeclarativeSettingsGetValueEvent;
use OCP\Settings\Events\DeclarativeSettingsRegisterFormEvent;
use OCP\Settings\Events\DeclarativeSettingsSetValueEvent;
use OCP\Settings\IDeclarativeManager;
use OCP\Settings\IDeclarativeSettingsForm;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type DeclarativeSettingsValueTypes from IDeclarativeSettingsForm
 * @psalm-import-type DeclarativeSettingsStorageType from IDeclarativeSettingsForm
 * @psalm-import-type DeclarativeSettingsSectionType from IDeclarativeSettingsForm
 * @psalm-import-type DeclarativeSettingsFormSchemaWithValues from IDeclarativeSettingsForm
 * @psalm-import-type DeclarativeSettingsFormSchemaWithoutValues from IDeclarativeSettingsForm
 */
class DeclarativeManager implements IDeclarativeManager {
	public function __construct(
		private IEventDispatcher $eventDispatcher,
		private IGroupManager    $groupManager,
		private Coordinator      $coordinator,
		private IConfig          $config,
		private IAppConfig       $appConfig,
		private LoggerInterface  $logger,
	) {
	}

	/**
	 * @var array<string, list<DeclarativeSettingsFormSchemaWithoutValues>>
	 */
	private array $appSchemas = [];

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
		$declarativeSettings = $this->coordinator->getRegistrationContext()->getDeclarativeSettings();
		foreach ($declarativeSettings as $declarativeSetting) {
			/** @var IDeclarativeSettingsForm $declarativeSettingObject */
			$declarativeSettingObject = Server::get($declarativeSetting->getService());
			$this->registerSchema($declarativeSetting->getAppId(), $declarativeSettingObject->getSchema());
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
				$this->eventDispatcher->dispatchTyped(new DeclarativeSettingsSetValueEvent($user, $app, $formId, $fieldId, $value));
				break;
			case DeclarativeSettingsTypes::STORAGE_TYPE_INTERNAL:
				$this->saveInternalValue($user, $app, $fieldId, $value);
				break;
			default:
				throw new Exception('Unknown storage type "' . $storageType . '"');
		}
	}

	private function getInternalValue(IUser $user, string $app, string $formId, string $fieldId): mixed {
		$sectionType = $this->getSectionType($app, $fieldId);
		$defaultValue = $this->getDefaultValue($app, $formId, $fieldId);
		switch ($sectionType) {
			case DeclarativeSettingsTypes::SECTION_TYPE_ADMIN:
				return $this->config->getAppValue($app, $fieldId, $defaultValue);
			case DeclarativeSettingsTypes::SECTION_TYPE_PERSONAL:
				return $this->config->getUserValue($user->getUID(), $app, $fieldId, $defaultValue);
			default:
				throw new Exception('Unknown section type "' . $sectionType . '"');
		}
	}

	private function saveInternalValue(IUser $user, string $app, string $fieldId, mixed $value): void {
		$sectionType = $this->getSectionType($app, $fieldId);
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
