<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Settings;

use OCA\WorkflowEngine\AppInfo\Application;
use OCA\WorkflowEngine\Manager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\ISettings;
use OCP\WorkflowEngine\Events\LoadSettingsScriptsEvent;
use OCP\WorkflowEngine\ICheck;
use OCP\WorkflowEngine\IComplexOperation;
use OCP\WorkflowEngine\IEntity;
use OCP\WorkflowEngine\IEntityEvent;
use OCP\WorkflowEngine\IOperation;
use OCP\WorkflowEngine\ISpecificOperation;

abstract class ASettings implements ISettings {
	public function __construct(
		private string $appName,
		private IL10N $l10n,
		private IEventDispatcher $eventDispatcher,
		protected Manager $manager,
		private IInitialState $initialStateService,
		private IConfig $config,
		private IURLGenerator $urlGenerator,
	) {
	}

	abstract public function getScope(): int;

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		// @deprecated in 20.0.0: retire this one in favor of the typed event
		$this->eventDispatcher->dispatch(
			'OCP\WorkflowEngine::loadAdditionalSettingScripts',
			new LoadSettingsScriptsEvent()
		);
		$this->eventDispatcher->dispatchTyped(new LoadSettingsScriptsEvent());

		$entities = $this->manager->getEntitiesList();
		$this->initialStateService->provideInitialState(
			'entities',
			$this->entitiesToArray($entities)
		);

		$operators = $this->manager->getOperatorList();
		$this->initialStateService->provideInitialState(
			'operators',
			$this->operatorsToArray($operators)
		);

		$checks = $this->manager->getCheckList();
		$this->initialStateService->provideInitialState(
			'checks',
			$this->checksToArray($checks)
		);

		$this->initialStateService->provideInitialState(
			'scope',
			$this->getScope()
		);

		$this->initialStateService->provideInitialState(
			'appstoreenabled',
			$this->config->getSystemValueBool('appstoreenabled', true)
		);

		$this->initialStateService->provideInitialState(
			'doc-url',
			$this->urlGenerator->linkToDocs('admin-workflowengine')
		);

		return new TemplateResponse(Application::APP_ID, 'settings', [], 'blank');
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection(): ?string {
		return 'workflow';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 *             the admin section. The forms are arranged in ascending order of the
	 *             priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	public function getPriority(): int {
		return 0;
	}

	private function entitiesToArray(array $entities) {
		return array_map(function (IEntity $entity) {
			$events = array_map(function (IEntityEvent $entityEvent) {
				return [
					'eventName' => $entityEvent->getEventName(),
					'displayName' => $entityEvent->getDisplayName()
				];
			}, $entity->getEvents());

			return [
				'id' => get_class($entity),
				'icon' => $entity->getIcon(),
				'name' => $entity->getName(),
				'events' => $events,
			];
		}, $entities);
	}

	private function operatorsToArray(array $operators) {
		$operators = array_filter($operators, function (IOperation $operator) {
			return $operator->isAvailableForScope($this->getScope());
		});

		return array_map(function (IOperation $operator) {
			return [
				'id' => get_class($operator),
				'icon' => $operator->getIcon(),
				'name' => $operator->getDisplayName(),
				'description' => $operator->getDescription(),
				'fixedEntity' => $operator instanceof ISpecificOperation ? $operator->getEntityId() : '',
				'isComplex' => $operator instanceof IComplexOperation,
				'triggerHint' => $operator instanceof IComplexOperation ? $operator->getTriggerHint() : '',
			];
		}, $operators);
	}

	private function checksToArray(array $checks) {
		$checks = array_filter($checks, function (ICheck $check) {
			return $check->isAvailableForScope($this->getScope());
		});

		return array_map(function (ICheck $check) {
			return [
				'id' => get_class($check),
				'supportedEntities' => $check->supportedEntities(),
			];
		}, $checks);
	}
}
