<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2019 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
	private IL10N $l10n;
	private string $appName;
	private IEventDispatcher $eventDispatcher;
	protected Manager $manager;
	private IInitialState $initialStateService;
	private IConfig $config;
	private IURLGenerator $urlGenerator;

	public function __construct(
		string $appName,
		IL10N $l,
		IEventDispatcher $eventDispatcher,
		Manager $manager,
		IInitialState $initialStateService,
		IConfig $config,
		IURLGenerator $urlGenerator
	) {
		$this->appName = $appName;
		$this->l10n = $l;
		$this->eventDispatcher = $eventDispatcher;
		$this->manager = $manager;
		$this->initialStateService = $initialStateService;
		$this->config = $config;
		$this->urlGenerator = $urlGenerator;
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
	 * the admin section. The forms are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
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
