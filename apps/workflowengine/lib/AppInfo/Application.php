<?php
/**
 * @copyright Copyright (c) 2016 Morris Jobke <hey@morrisjobke.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
namespace OCA\WorkflowEngine\AppInfo;

use Closure;
use OCA\WorkflowEngine\Helper\LogContext;
use OCA\WorkflowEngine\Listener\LoadAdditionalSettingsScriptsListener;
use OCA\WorkflowEngine\Manager;
use OCA\WorkflowEngine\Service\Logger;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\WorkflowEngine\Events\LoadSettingsScriptsEvent;
use OCP\WorkflowEngine\IEntity;
use OCP\WorkflowEngine\IOperation;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class Application extends App implements IBootstrap {
	public const APP_ID = 'workflowengine';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(
			LoadSettingsScriptsEvent::class,
			LoadAdditionalSettingsScriptsListener::class,
			-100
		);
	}

	public function boot(IBootContext $context): void {
		$context->injectFn(Closure::fromCallable([$this, 'registerRuleListeners']));
	}

	private function registerRuleListeners(IEventDispatcher $dispatcher,
		ContainerInterface $container,
		LoggerInterface $logger): void {
		/** @var Manager $manager */
		$manager = $container->get(Manager::class);
		$configuredEvents = $manager->getAllConfiguredEvents();

		foreach ($configuredEvents as $operationClass => $events) {
			foreach ($events as $entityClass => $eventNames) {
				array_map(function (string $eventName) use ($manager, $container, $dispatcher, $logger, $operationClass, $entityClass) {
					$dispatcher->addListener(
						$eventName,
						function ($event) use ($manager, $container, $eventName, $logger, $operationClass, $entityClass) {
							$ruleMatcher = $manager->getRuleMatcher();
							try {
								/** @var IEntity $entity */
								$entity = $container->get($entityClass);
								/** @var IOperation $operation */
								$operation = $container->get($operationClass);

								$ruleMatcher->setEventName($eventName);
								$ruleMatcher->setEntity($entity);
								$ruleMatcher->setOperation($operation);

								$ctx = new LogContext();
								$ctx
									->setOperation($operation)
									->setEntity($entity)
									->setEventName($eventName);

								/** @var Logger $flowLogger */
								$flowLogger = $container->get(Logger::class);
								$flowLogger->logEventInit($ctx);

								if ($event instanceof Event) {
									$entity->prepareRuleMatcher($ruleMatcher, $eventName, $event);
									$operation->onEvent($eventName, $event, $ruleMatcher);
								} else {
									$logger->debug(
										'Cannot handle event {name} of {event} against entity {entity} and operation {operation}',
										[
											'app' => self::APP_ID,
											'name' => $eventName,
											'event' => get_class($event),
											'entity' => $entityClass,
											'operation' => $operationClass,
										]
									);
								}
								$flowLogger->logEventDone($ctx);
							} catch (ContainerExceptionInterface $e) {
								// Ignore query exceptions since they might occur when an entity/operation were set up before by an app that is disabled now
							}
						}
					);
				}, $eventNames ?? []);
			}
		}
	}
}
